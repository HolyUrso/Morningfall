<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';

$error = '';
$successCode = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $discord = trim($_POST['discord'] ?? '');
    $discordId = preg_replace('/\D+/', '', trim($_POST['discord_id'] ?? ''));
    $discordAvatar = trim($_POST['discord_avatar'] ?? '');
    $platform = trim($_POST['platform'] ?? '');
    $channel = trim($_POST['channel_url'] ?? '');
    $category = $_POST['category'] ?? 'novato';
    $code = trim($_POST['access_code'] ?? '');
    $joined = $_POST['joined_at'] ?? date('Y-m-d');
    $notes = trim($_POST['notes'] ?? '');
    $webhook = trim($_POST['webhook_url'] ?? '');

    if ($name === '') {
        $error = 'Informe o nome do streamer.';
    } elseif (!in_array($category, ['novato','oficial','afiliado'], true)) {
        $error = 'Categoria inválida.';
    } else {
        if ($code === '') $code = strtoupper(bin2hex(random_bytes(4)));
        try {
            $st = $pdo->prepare("INSERT INTO streamers(name,discord,discord_id,discord_avatar,platform,channel_url,category,access_code,joined_at,notes,webhook_url) VALUES(?,?,?,?,?,?,?,?,?,?,?)");
            $st->execute([$name,$discord,$discordId !== '' ? $discordId : null,$discordAvatar !== '' ? $discordAvatar : null,$platform,$channel,$category,$code,$joined ?: null,$notes,$webhook !== '' ? $webhook : null]);
            $newId=(int)$pdo->lastInsertId();
            auditLog($pdo,'Cadastro de streamer','Usuário',$newId,$name,'Novo streamer cadastrado.',null,['name'=>$name,'category'=>$category,'platform'=>$platform,'joined_at'=>$joined]);
            header('Location: streamer.php?id='.$newId.'&created=1');
            exit;
        } catch (PDOException $e) {
            $error = ($e->getCode() === '23000')
                ? 'Esse código de acesso já está sendo usado. Gere outro ou informe um diferente.'
                : 'Não foi possível cadastrar o streamer.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Cadastrar Streamer</title><link rel="stylesheet" href="../assets/css/style.css"><script src="../assets/js/discord-avatar.js" defer></script></head>
<body>
<main class="container">
<a href="streamers.php">← Streamers</a>
<h1>Cadastrar Streamer</h1>
<p class="muted">Cadastre o criador para liberar o perfil, acesso e acompanhamento de VODs e pontos.</p>
<?php if ($error): ?><div class="alert danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form class="form" method="post">
<label>Nome do streamer *</label>
<input name="name" placeholder="Nome do streamer" required>
<label>Discord</label>
<input name="discord" placeholder="Ex.: Lua#0001">
<div class="discord-avatar-box">
<label>ID do Discord</label>
<div class="field-with-action"><input id="discord_id_field" name="discord_id" inputmode="numeric" placeholder="ID numérico do Discord" value="<?=htmlspecialchars($_POST['discord_id']??'')?>"><button type="button" class="btn secondary" id="discord_avatar_btn" onclick="buscarAvatarDiscord({idInput:'discord_id_field',avatarInput:'discord_avatar_field',buttonId:'discord_avatar_btn',previewId:'discord_avatar_preview',statusId:'discord_avatar_status',type:'streamer',entityId:''})">🔎 Buscar avatar</button></div>
<label>Avatar do Discord</label>
<input type="url" id="discord_avatar_field" name="discord_avatar" placeholder="Preenchido automaticamente" value="<?=htmlspecialchars($_POST['discord_avatar']??'')?>">
<div><img id="discord_avatar_preview" class="discord-avatar-preview" src="<?=htmlspecialchars($_POST['discord_avatar']??'')?>" style="<?=empty($_POST['discord_avatar'])?'display:none':''?>" alt="Avatar"></div>
<small id="discord_avatar_status" class="avatar-status">Digite o ID e clique em Buscar avatar.</small>
</div>
<label>Plataforma</label>
<input name="platform" placeholder="Twitch / Kick / YouTube">
<label>Link do canal</label>
<input name="channel_url" placeholder="https://...">
<label>Categoria *</label>
<select name="category"><option value="novato">Novato</option><option value="oficial">Oficial</option><option value="afiliado">Afiliado</option></select>
<label>Código de acesso</label>
<div class="field-with-action"><input name="access_code" placeholder="Vazio = gerar automaticamente"><button class="btn secondary" type="button" onclick="this.form.access_code.value=Math.random().toString(36).slice(2,10).toUpperCase()">Gerar</button></div>
<label>Data de entrada</label>
<input type="date" name="joined_at" value="<?=date('Y-m-d')?>">
<label>Observações</label>
<textarea name="notes" placeholder="Observações internas"></textarea>
<label>Webhook individual do Discord</label>
<input type="url" name="webhook_url" placeholder="https://discord.com/api/webhooks/..." value="<?=htmlspecialchars($_POST['webhook_url']??'')?>">
<p class="muted">Opcional. Cada streamer pode ter sua própria webhook para receber o histórico de ações.</p>
<div class="actions"><button class="btn primary">Cadastrar Streamer</button><a class="btn secondary" href="streamers.php">Cancelar</a></div>
</form>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>