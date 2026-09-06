<?php
require_once 'config/database.php';
$done=false; $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  $name=trim($_POST['name']); $username=trim($_POST['username']); $password=$_POST['password'];
  if(!$name||!$username||strlen($password)<6) throw new Exception('Preencha todos os campos. A senha deve ter 6 caracteres ou mais.');
  $hash=password_hash($password,PASSWORD_DEFAULT);
  $st=$pdo->prepare("INSERT INTO staff_users(name,username,password_hash,role) VALUES(?,?,?,'master')");
  $st->execute([$name,$username,$hash]); $done=true;
 }catch(Throwable $e){$error=$e->getMessage();}
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Instalação</title><link rel="stylesheet" href="assets/css/style.css"></head>
<body class="auth-page"><div class="auth-card"><div class="logo">MORNINGFALL</div><div class="logo-sub">CREATORS</div>
<?php if($done): ?><h2>Instalação concluída</h2><p>Usuário Master criado. Apague o arquivo <b>install.php</b> antes de usar o sistema.</p><a class="btn primary" href="login.php">Ir para Login</a>
<?php else: ?><h2>Primeiro acesso</h2><?php if($error): ?><div class="alert danger"><?=htmlspecialchars($error)?></div><?php endif; ?><form method="post">
<input name="name" placeholder="Nome da Staff" required><input name="username" placeholder="Usuário" required><input name="password" type="password" placeholder="Senha (mínimo 6 caracteres)" required><button class="btn primary">Criar usuário Master</button>
</form><?php endif; ?></div></body></html>
