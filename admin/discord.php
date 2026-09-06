<?php
require_once '../config/auth.php';
require_once '../config/database.php';

if (($_SESSION['staff_role'] ?? '') !== 'master') {
    http_response_code(403);
    ?>
    <!doctype html>
    <html lang="pt-BR">
    <head>
        <meta charset="utf-8">
        <title>Acesso restrito</title>
        <link rel="stylesheet" href="../assets/css/style.css">
    </head>
    <body>
        <main class="container">
            <div class="panel">
                <h2>🔒 Acesso restrito</h2>
                <p class="muted">Somente o Staff Master pode acessar as configurações do Discord / Cloudflare Relay.</p>
                <a class="btn" href="index.php">← Voltar</a>
            </div>
        </main>
    </body>
    </html>
    <?php
    exit;
}

$error = '';
$success = '';

$currentRelayUrlFallback = 'https://morningfall-relay.santourso.workers.dev';
$currentRelaySecretFallback = 'MorningfallRelay_2026_X7p9K2mQ8vL4';

function getDiscordSetting(PDO $pdo, string $key, string $fallback = ''): string {
    try {
        $st = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
        $st->execute([$key]);
        $value = $st->fetchColumn();
        if ($value === false || trim((string)$value) === '') return $fallback;
        return trim((string)$value);
    } catch (Throwable $e) {
        return $fallback;
    }
}

function saveDiscordSetting(PDO $pdo, string $key, string $value, string $description): void {
    $st = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE setting_key=?');
    $st->execute([$key]);

    if ((int)$st->fetchColumn() > 0) {
        $up = $pdo->prepare('UPDATE settings SET setting_value=? WHERE setting_key=?');
        $up->execute([$value, $key]);
    } else {
        $ins = $pdo->prepare('INSERT INTO settings(setting_key,setting_value,description) VALUES(?,?,?)');
        $ins->execute([$key, $value, $description]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $url = trim((string)($_POST['discord_relay_url'] ?? ''));
        $secret = trim((string)($_POST['discord_relay_secret'] ?? ''));

        if ($url === '') {
            throw new RuntimeException('Informe o Cloudflare Relay URL.');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('A Cloudflare Relay URL não é válida.');
        }

        saveDiscordSetting(
            $pdo,
            'discord_relay_url',
            rtrim($url, '/'),
            'URL base do Cloudflare Worker usado pelo Discord Relay.'
        );

        /*
         * O Secret só é alterado se o Master realmente informar um novo valor.
         * Se vier vazio, o Secret atual é preservado.
         */
        if ($secret !== '') {
            saveDiscordSetting(
                $pdo,
                'discord_relay_secret',
                $secret,
                'Mesmo valor do Secret RELAY_SECRET configurado no Cloudflare Worker.'
            );
        }

        $success = 'Configuração do Discord / Cloudflare Relay salva com sucesso.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$relayUrl = getDiscordSetting($pdo, 'discord_relay_url', $currentRelayUrlFallback);
$relaySecret = getDiscordSetting($pdo, 'discord_relay_secret', $currentRelaySecretFallback);
$secretConfigured = ($relaySecret !== '');
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Discord / Cloudflare Relay</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.relay-danger{border:1px solid #5b2d3b;background:#17121a}
.relay-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.relay-card{background:#111722;border:1px solid #29344a;border-radius:15px;padding:20px}
.relay-card label{display:block;font-weight:800;margin-bottom:8px}
.relay-field{display:flex;gap:8px;align-items:stretch}
.relay-field input{flex:1;min-width:0;box-sizing:border-box}
.relay-view-btn{white-space:nowrap;min-width:120px}
.secret-state{display:inline-flex;margin-top:10px;padding:6px 10px;border-radius:999px;background:#173b2c;color:#8ce8b9;font-size:12px;font-weight:800}
@media(max-width:800px){.relay-grid{grid-template-columns:1fr}}
</style>
</head>
<body>

<header class="topbar">
    <div><b>MORNINGFALL</b> <span>CREATORS</span></div>
    <div><?=htmlspecialchars($_SESSION['staff_name'] ?? 'Master')?> · <a href="../logout.php">Sair</a></div>
</header>

<main class="container">
    <div class="page-head">
        <div>
            <a href="index.php">← Dashboard</a>
            <p class="eyebrow">MASTER • ÁREA SENSÍVEL</p>
            <h1>🔗 Discord / Cloudflare Relay</h1>
            <p class="muted">Configuração separada das demais configurações do sistema.</p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert-success">✅ <?=htmlspecialchars($success)?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-error">❌ <?=htmlspecialchars($error)?></div>
    <?php endif; ?>

    <section class="settings-section relay-danger">
        <h2>🔐 Configuração protegida</h2>
        <p class="section-help">Somente o <strong>Master</strong> consegue visualizar e alterar estes dados.</p>

        <form method="post" id="relayForm">
            <div class="relay-grid">

                <div class="relay-card">
                    <label for="discord_relay_url">Cloudflare Relay URL</label>

                    <div class="relay-field">
                        <input
                            id="discord_relay_url"
                            name="discord_relay_url"
                            type="password"
                            value="<?=htmlspecialchars($relayUrl, ENT_QUOTES, 'UTF-8')?>"
                            autocomplete="off"
                            required
                        >

                        <button
                            type="button"
                            class="btn relay-view-btn"
                            id="viewRelayUrl"
                            onclick="toggleRelayField('discord_relay_url','viewRelayUrl')"
                        >👁️ Visualizar</button>
                    </div>

                    <small>O valor atual já está carregado. Clique no olho para visualizar.</small>
                </div>

                <div class="relay-card">
                    <label for="discord_relay_secret">Relay Secret</label>

                    <div class="relay-field">
                        <input
                            id="discord_relay_secret"
                            name="discord_relay_secret"
                            type="password"
                            value="<?=htmlspecialchars($relaySecret, ENT_QUOTES, 'UTF-8')?>"
                            autocomplete="new-password"
                            spellcheck="false"
                        >

                        <button
                            type="button"
                            class="btn relay-view-btn"
                            id="viewRelaySecret"
                            onclick="toggleRelayField('discord_relay_secret','viewRelaySecret')"
                        >👁️ Visualizar</button>
                    </div>

                    <small>O valor atual já está carregado. Clique no olho para visualizar.</small>

                    <?php if ($secretConfigured): ?>
                        <span class="secret-state">● Secret configurado</span>
                    <?php endif; ?>
                </div>

            </div>

            <div class="rules-note" style="margin-top:16px">
                <strong>⚠️ Área sensível:</strong>
                os valores acima são os atualmente configurados.
                Se o Secret for deixado vazio em uma futura alteração, o Secret atual será preservado.
            </div>

            <div style="display:flex;gap:12px;align-items:center;margin-top:22px">
                <button class="btn primary" type="submit">💾 Salvar configuração do Discord</button>
                <a class="btn" href="index.php">Cancelar</a>
            </div>
        </form>
    </section>

    <section class="settings-section">
        <h2>☁️ Cloudflare</h2>
        <p class="section-help">No Worker, mantenha estes Secrets:</p>

        <div class="rules-note">
            <strong>RELAY_SECRET</strong> — deve ser igual ao Relay Secret acima.<br>
            <strong>DISCORD_BOT_TOKEN</strong> — fica somente no Cloudflare e nunca deve ser colocado no site.
        </div>
    </section>
</main>

<footer class="site-footer">
    Morningfall Creators V1 <span>•</span>
    Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a>
</footer>

<script>
function toggleRelayField(inputId, buttonId) {
    const input = document.getElementById(inputId);
    const button = document.getElementById(buttonId);

    if (!input || !button) {
        return;
    }

    if (input.type === 'password') {
        input.type = 'text';
        button.innerHTML = '🙈 Ocultar';
    } else {
        input.type = 'password';
        button.innerHTML = '👁️ Visualizar';
    }
}
</script>

</body>
</html>
