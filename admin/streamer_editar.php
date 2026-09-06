<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';
require_once __DIR__.'/../classes/PlatformSync.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$st = $pdo->prepare("SELECT * FROM streamers WHERE id=?");
$st->execute([$id]);
$s = $st->fetch();
$beforeStreamer = $s ? ['name'=>$s['name'],'discord'=>$s['discord'],'platform'=>$s['platform'],'channel_url'=>$s['channel_url'],'category'=>$s['category'],'joined_at'=>$s['joined_at'],'notes'=>$s['notes']] : null;
if (!$s) exit('Streamer não encontrado.');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $discord = trim($_POST['discord'] ?? '');
    $discordId = preg_replace('/\D+/', '', trim($_POST['discord_id'] ?? ''));
    $discordAvatar = trim($_POST['discord_avatar'] ?? '');
    $platform = trim($_POST['platform'] ?? '');
    $channel = trim($_POST['channel_url'] ?? '');
    $category = $_POST['category'] ?? 'novato';
    $code = trim($_POST['access_code'] ?? '');
    $joined = $_POST['joined_at'] ?? null;
    $notes = trim($_POST['notes'] ?? '');
    $webhook = trim($_POST['webhook_url'] ?? '');

    if ($name === '') {
        $error = 'Informe o nome do streamer.';
    } elseif (!in_array($category, ['novato','oficial','afiliado'], true)) {
        $error = 'Categoria inválida.';
    } elseif ($code === '') {
        $error = 'O código de acesso não pode ficar vazio.';
    } else {
        try {
            $up = $pdo->prepare("UPDATE streamers SET name=?,discord=?,discord_id=?,discord_avatar=?,platform=?,channel_url=?,category=?,access_code=?,joined_at=?,notes=?,webhook_url=? WHERE id=?");
            $up->execute([$name,$discord,$discordId !== '' ? $discordId : null,$discordAvatar !== '' ? $discordAvatar : null,$platform,$channel,$category,$code,$joined ?: null,$notes,$webhook !== '' ? $webhook : null,$id]);

            if ($channel !== '') {
                $syncPlatform = $platform;
                try {
                    $platformSync = new PlatformSync();
                    $result = $platformSync->resolve($channel, $platform !== '' ? $platform : null);
                    $syncPlatform = (string)$result['platform'];
                    $platformSync->storeResolvedChannel($pdo, $id, $result);
                } catch (Throwable $syncException) {
                    $syncError = substr($syncException->getMessage(), 0, 500);
                    try {
                        if ($syncPlatform === '') {
                            $platformSync = new PlatformSync();
                            $syncPlatform = $platformSync->detectPlatform($channel);
                        }
                        $platformSync->storeChannelError($pdo, $id, $syncPlatform, $channel, $syncError);
                    } catch (Throwable $ignored) {
                    }
                }
            }

            auditLog($pdo,'Alteração de streamer','Usuário',$id,$name,'Perfil do streamer alterado.', $beforeStreamer, ['name'=>$name,'discord'=>$discord,'platform'=>$platform,'channel_url'=>$channel,'category'=>$category,'joined_at'=>$joined,'notes'=>$notes]);
            header('Location: streamer.php?id='.$id.'&updated=1');
            exit;
        } catch (PDOException $e) {
            $error = ($e->getCode() === '23000')
                ? 'Esse código de acesso já está sendo usado por outro streamer.'
                : 'Não foi possível salvar as alterações.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Editar Streamer</title><link rel="stylesheet" href="../assets/css/style.css"><script src="../assets/js/discord-avatar.js" defer></script></head>
<body>
<main class="container">
<a href="streamer.php?id=<?=$id?>">← Perfil do streamer</a>
<h1>Editar Streamer</h1>
<?php if ($error): ?><div class="alert danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<form class="form" method="post">
<input type="hidden" name="id" value="<?=$id?>">
<label>Nome do streamer</label>
<input name="name" value="<?=htmlspecialchars($s['name'])?>" required>
<label>Discord</label>
<input name="discord" value="<?=htmlspecialchars($s['discord'] ?? '')?>">
<div class="discord-avatar-box">
<label>ID do Discord</label>
<div class="field-with-action"><input id="discord_id_field" name="discord_id" inputmode="numeric" placeholder="ID numérico do Discord" value="<?=htmlspecialchars($s['discord_id']??'')?>"><button type="button" class="btn secondary" id="discord_avatar_btn" onclick="buscarAvatarDiscord({idInput:'discord_id_field',avatarInput:'discord_avatar_field',buttonId:'discord_avatar_btn',previewId:'discord_avatar_preview',statusId:'discord_avatar_status',type:'streamer',entityId:<?= (int)$id ?>})">🔎 Buscar avatar</button></div>
<label>Avatar do Discord</label>
<input type="url" id="discord_avatar_field" name="discord_avatar" placeholder="Preenchido automaticamente" value="<?=htmlspecialchars($s['discord_avatar']??'')?>">
<div><img id="discord_avatar_preview" class="discord-avatar-preview" src="<?=htmlspecialchars($s['discord_avatar']??'')?>" style="<?=empty($s['discord_avatar']??'')?'display:none':''?>" alt="Avatar"></div>
<small id="discord_avatar_status" class="avatar-status">Digite o ID e clique em Buscar avatar.</small>
</div>
<label>Plataforma</label>
<input name="platform" value="<?=htmlspecialchars($s['platform'] ?? '')?>" placeholder="Twitch / Kick / YouTube">
<label>Link do canal</label>
<input name="channel_url" value="<?=htmlspecialchars($s['channel_url'] ?? '')?>">
<label>Categoria</label>
<select name="category">
<option value="novato" <?=$s['category']==='novato'?'selected':''?>>Novato</option>
<option value="oficial" <?=$s['category']==='oficial'?'selected':''?>>Oficial</option>
<option value="afiliado" <?=$s['category']==='afiliado'?'selected':''?>>Afiliado</option>
</select>
<label>Código de acesso</label>
<div class="field-with-action"><input name="access_code" value="<?=htmlspecialchars($s['access_code'])?>" required><button class="btn secondary" type="button" onclick="this.form.access_code.value=Math.random().toString(36).slice(2,10).toUpperCase()">Gerar</button></div>
<label>Data de entrada</label>
<input type="date" name="joined_at" value="<?=htmlspecialchars($s['joined_at'] ?? '')?>">
<label>Observações</label>
<textarea name="notes"><?=htmlspecialchars($s['notes'] ?? '')?></textarea>
<label>Webhook individual do Discord</label>
<input type="url" name="webhook_url" placeholder="https://discord.com/api/webhooks/..." value="<?=htmlspecialchars($s['webhook_url'] ?? '')?>">
<p class="muted">Deixe vazio para desativar. A webhook é usada somente para o histórico deste streamer.</p>
<div class="actions">
<button class="btn primary">Salvar alterações</button>
<a class="btn secondary" href="streamer.php?id=<?=$id?>">Cancelar</a>
</div>
</form>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>