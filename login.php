<?php
session_start();
if (!empty($_SESSION['staff_id'])) { header('Location: admin/index.php'); exit; }
$error = isset($_GET['error']);
?>
<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Morningfall Creators</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="auth-page">
<div class="auth-card">
<div class="logo">MORNINGFALL</div><div class="logo-sub">CREATORS</div>
<h2>Painel da Staff</h2>
<?php if($error): ?><div class="alert danger">Usuário ou senha inválidos.</div><?php endif; ?>
<form method="post" action="api/staff_login.php">
<input name="username" placeholder="Usuário" required>
<input name="password" type="password" placeholder="Senha" required>
<button class="btn primary">Entrar</button>
</form>
<a class="secondary-link" href="streamer-login.php">Área do Streamer</a>
<a class="secondary-link" href="regras.php">📜 Regras dos Criadores</a>
</div><footer class="site-footer">Morningfal Creatos V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>
