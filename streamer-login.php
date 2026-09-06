<?php
session_start();
require_once 'config/database.php';
function streamerSetting(PDO $pdo, string $key, string $default=''): string { $st=$pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1'); $st->execute([$key]); $v=$st->fetchColumn(); return $v===false?$default:(string)$v; }
if (!empty($_SESSION['streamer_id'])) { header('Location: streamer/index.php'); exit; }
$streamerLogo=streamerSetting($pdo,'streamer_logo','');
$streamerBg=streamerSetting($pdo,'streamer_background','assets/img/streamer-background-default.png');
$error = isset($_GET['error']);
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Área do Streamer</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="auth-page streamer-auth" style="--streamer-bg: url('<?=htmlspecialchars($streamerBg, ENT_QUOTES, 'UTF-8')?>');"><div class="auth-card streamer-auth-card">
<?php if($streamerLogo): ?><div class="streamer-brand"><img src="<?=htmlspecialchars($streamerLogo)?>" alt="Logo"></div><?php else: ?><div class="logo">CARMESIM</div><div class="logo-sub">CREATORS</div><?php endif; ?>
<h2>Área do Streamer</h2>
<p class="muted">Informe seu código de acesso para consultar seus dados.</p>
<?php if($error): ?><div class="alert danger">Código inválido.</div><?php endif; ?>
<form method="post" action="api/streamer_login.php">
<input name="access_code" placeholder="Código de acesso" required>
<button class="btn primary">Consultar</button>
</form>
<a class="secondary-link" href="regras.php">📜 Ver Regras dos Criadores</a>
</div><footer class="site-footer">Morningfal Creatos V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>
