<?php
require_once '../config/auth.php';
require_once '../config/discord_ranking.php';

if (($_SESSION['staff_role'] ?? '') !== 'master') {
    http_response_code(403);
    exit('Acesso restrito ao Master.');
}

$error = '';
$success = '';

$key = discordRankingSetting($pdo, 'discord_ranking_key', '');
if ($key === '') {
    $key = bin2hex(random_bytes(24));
    discordRankingSave($pdo, 'discord_ranking_key', $key, 'Chave privada do endpoint de atualização do ranking Discord.');
}

$webhook = discordRankingSetting($pdo, 'discord_ranking_webhook', '');
$bottomImage = discordRankingSetting($pdo, 'discord_ranking_bottom_image', '');
$messageId = discordRankingSetting($pdo, 'discord_ranking_message_id', '');

function e($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function validateDiscordWebhook(string $url): void {
    if ($url === '') {
        throw new RuntimeException('Informe o Webhook do Discord.');
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        throw new RuntimeException('A URL informada não é válida.');
    }

    $parsed = parse_url($url);
    $host = strtolower((string)($parsed['host'] ?? ''));
    $path = (string)($parsed['path'] ?? '');

    if (!in_array($host, ['discord.com', 'discordapp.com'], true)) {
        throw new RuntimeException('O Webhook precisa ser do Discord.');
    }

    if (!preg_match('~^/api/webhooks/[^/]+/[^/]+/?$~', $path)) {
        throw new RuntimeException('A URL não possui o formato de um Webhook do Discord.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        // =====================================================
        // SALVAR CONFIGURAÇÃO — NÃO ENVIA NADA AO DISCORD
        // =====================================================
        if ($action === 'save') {
            $url = trim((string)($_POST['webhook'] ?? ''));
            validateDiscordWebhook($url);

            // Se o Webhook mudou, a mensagem antiga pertence ao Webhook anterior.
            // Limpamos o message_id para que o próximo envio crie uma nova mensagem.
            $oldWebhook = discordRankingSetting($pdo, 'discord_ranking_webhook', '');

            discordRankingSave(
                $pdo,
                'discord_ranking_webhook',
                $url,
                'Webhook do ranking público Carmesim Creators.'
            );

            if ($oldWebhook !== $url) {
                discordRankingSave(
                    $pdo,
                    'discord_ranking_message_id',
                    '',
                    'ID da mensagem do ranking no Discord.'
                );
                $messageId = '';
            }

            $webhook = $url;
            $success = $oldWebhook !== $url
                ? 'Webhook salvo. A próxima publicação criará uma nova mensagem no Discord.'
                : 'Webhook do Discord salvo. Nenhuma mensagem foi enviada.';
        }

        // =====================================================
        // UPLOAD DA IMAGEM GRANDE
        // =====================================================
        elseif ($action === 'upload_bottom_image') {
            if (!isset($_FILES['bottom_image']) || $_FILES['bottom_image']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Selecione uma imagem.');
            }

            $file = $_FILES['bottom_image'];

            if ((int)$file['size'] > 8 * 1024 * 1024) {
                throw new RuntimeException('A imagem deve ter no máximo 8 MB.');
            }

            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
            $allowed = [
                'image/png'  => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp'
            ];

            if (!isset($allowed[$mime])) {
                throw new RuntimeException('Use PNG, JPG ou WEBP.');
            }

            $dir = __DIR__ . '/../assets/uploads/discord';
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new RuntimeException('Não foi possível criar a pasta de imagens.');
            }

            $name = 'ranking_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$mime];
            $destination = $dir . '/' . $name;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                throw new RuntimeException('Não foi possível salvar a imagem.');
            }

            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'] ?? '';

            if ($host === '') {
                throw new RuntimeException('Não foi possível identificar o endereço do site.');
            }

            $publicUrl = $scheme . '://' . $host . '/assets/uploads/discord/' . $name;

            discordRankingSave(
                $pdo,
                'discord_ranking_bottom_image',
                $publicUrl,
                'Imagem grande do ranking Carmesim Creators.'
            );

            $bottomImage = $publicUrl;
            $success = 'Imagem grande salva. Ela será enviada ao Discord como anexo no próximo teste/atualização.';
        }

        // =====================================================
        // REMOVER IMAGEM
        // =====================================================
        elseif ($action === 'clear_bottom_image') {
            discordRankingSave(
                $pdo,
                'discord_ranking_bottom_image',
                '',
                'Imagem grande do ranking Carmesim Creators.'
            );

            $bottomImage = '';
            $success = 'Imagem grande removida da configuração.';
        }

        // =====================================================
        // ATUALIZAR AGORA
        // =====================================================
        elseif ($action === 'update_now') {
            // O botão de publicação usa exclusivamente o Webhook já salvo acima.
            // Não há um segundo campo de Webhook nesta etapa.
            $result = discordRankingUpdate($pdo);
            $messageId = $result['message_id'];

            $success = ($result['mode'] === 'created'
                ? 'Ranking enviado ao Discord agora.'
                : 'Ranking atualizado no Discord agora.')
                . ' Top ' . $result['top_count'] . '.';
        }

        // =====================================================
        // NOVA MENSAGEM
        // =====================================================
        elseif ($action === 'reset_message') {
            discordRankingSave(
                $pdo,
                'discord_ranking_message_id',
                '',
                'ID da mensagem do ranking no Discord.'
            );

            $messageId = '';
            $success = 'Mensagem vinculada removida. A próxima atualização criará uma nova.';
        }

    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$cronUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . ($_SERVER['HTTP_HOST'] ?? '')
    . '/api/discord_ranking.php?key=' . urlencode($key);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Ranking Carmesim Creators</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.ranking-config-grid{display:grid;grid-template-columns:1fr;gap:16px}
.ranking-card{background:#111722;border:1px solid #29344a;border-radius:15px;padding:20px}
.ranking-card label{display:block;font-weight:800;margin-bottom:8px}
.ranking-help{margin-top:7px;display:block}
.ranking-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:18px}
.ranking-preview{display:block;width:100%;max-width:760px;max-height:360px;object-fit:contain;border-radius:12px;border:1px solid rgba(255,255,255,.12);background:#080b10}
.ranking-status{padding:12px 14px;border-radius:12px;background:#101722;border:1px solid #29344a}
.ranking-code{display:block;overflow:auto;padding:12px;border-radius:10px;background:#090d14;border:1px solid #273149;word-break:break-all}
@media(max-width:700px){.ranking-card{padding:16px}}
</style>
</head>
<body>

<header class="topbar">
    <div><b>MORNINGFALL</b> <span>CREATORS</span></div>
    <div><?=e($_SESSION['staff_name'] ?? 'Master')?> · <a href="../logout.php">Sair</a></div>
</header>

<main class="container">
    <div class="page-head">
        <div>
            <a href="index.php">← Dashboard</a>
            <p class="eyebrow">MASTER • DISCORD</p>
            <h1>🏆 Ranking Carmesim Creators</h1>
            <p class="muted">
                Top 10 de Streamers, Histórico de Lives, Horas de Live e Conteúdo.
                Nenhum ranking exibe pontuação.
            </p>
        </div>
    </div>

    <?php if ($success): ?>
        <div class="alert-success">✅ <?=e($success)?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert-error">❌ <?=e($error)?></div>
    <?php endif; ?>

    <!-- ===================================================
         CONFIGURAÇÃO SIMPLIFICADA
    ==================================================== -->
    <section class="panel">
        <h2>⚙️ Configuração do Ranking</h2>
        <p class="muted">
            Nesta tela você precisa configurar somente o <strong>Webhook do Discord</strong>
            e, opcionalmente, a <strong>imagem grande do ranking</strong>.
        </p>

        <div class="ranking-config-grid">
            <div class="ranking-card">
                <form method="post">
                    <input type="hidden" name="action" value="save">

                    <label for="webhook">🔗 Webhook do Discord</label>
                    <input
                        class="form-control"
                        id="webhook"
                        type="password"
                        name="webhook"
                        value="<?=e($webhook)?>"
                        placeholder="https://discord.com/api/webhooks/..."
                        autocomplete="off"
                        required
                    >
                    <small class="muted ranking-help">
                        Cole aqui o Webhook do canal do Discord onde o ranking será publicado.
                    </small>

                    <div class="ranking-actions">
                        <button class="btn primary" type="submit">💾 Salvar configuração</button>
                    </div>

                    <small class="muted ranking-help">
                        <strong>Salvar configuração não envia nada para o Discord.</strong>
                    </small>
                </form>
            </div>

            <div class="ranking-card">
                <form method="post" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_bottom_image">

                    <label for="bottom_image">🖼️ Imagem grande do Ranking</label>
                    <input
                        class="form-control"
                        id="bottom_image"
                        type="file"
                        name="bottom_image"
                        accept="image/png,image/jpeg,image/webp"
                        required
                    >
                    <small class="muted ranking-help">
                        PNG, JPG ou WEBP, máximo de 8 MB.
                        A imagem será enviada pelo Cloudflare Relay como anexo do Discord.
                    </small>

                    <div class="ranking-actions">
                        <button class="btn primary" type="submit">⬆️ Salvar imagem</button>
                    </div>
                </form>

                <?php if ($bottomImage): ?>
                    <div style="margin-top:18px">
                        <p class="muted"><strong>Imagem atualmente configurada:</strong></p>
                        <img class="ranking-preview" src="<?=e($bottomImage)?>" alt="Imagem do Ranking Carmesim Creators">

                        <form method="post" style="margin-top:10px">
                            <input type="hidden" name="action" value="clear_bottom_image">
                            <button class="btn danger-btn" type="submit">🗑️ Remover imagem</button>
                        </form>
                    </div>
                <?php else: ?>
                    <p class="muted" style="margin-top:14px">Nenhuma imagem configurada.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- ===================================================
         TESTE / MENSAGEM
    ==================================================== -->
    <section class="panel" style="margin-top:18px">
        <h2>🚀 Publicar no Discord</h2>
        <p class="muted">
            Use este botão somente quando quiser testar ou atualizar os quatro rankings imediatamente.
            O sistema tenta editar a mesma mensagem sempre que houver um ID salvo.
        </p>

        <div class="ranking-status">
            <strong>ID da mensagem atual:</strong>
            <?=e($messageId ?: 'Nenhuma mensagem vinculada ainda.')?>
        </div>

        <form method="post" style="margin-top:16px">
            <input type="hidden" name="action" value="update_now">
            <div class="ranking-actions">
                <button class="btn primary" type="submit">🚀 Testar e enviar agora</button>
            </div>
        </form>

        <form method="post" style="margin-top:10px">
            <input type="hidden" name="action" value="reset_message">
            <button class="btn" type="submit">♻️ Criar uma nova mensagem no próximo envio</button>
        </form>
    </section>

    <!-- ===================================================
         CRON
    ==================================================== -->
    <section class="panel" style="margin-top:18px">
        <h2>⏱️ Atualização automática</h2>
        <p class="muted">
            O cron deve chamar este endereço <strong>1 vez por hora</strong> para atualizar os quatro rankings.
            Ele não usa o Webhook diretamente: o site envia para o Cloudflare Relay.
        </p>

        <code class="ranking-code"><?=e($cronUrl)?></code>

        <p class="muted" style="margin-top:12px">
            <strong>Cloudflare Relay:</strong>
            configurado separadamente em <strong>Master → Discord / Relay</strong>.
        </p>
    </section>
</main>

</body>
</html>
