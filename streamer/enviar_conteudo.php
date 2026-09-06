<?php
require_once '../config/streamer_auth.php'; require_once '../config/database.php'; require_once '../config/content_points.php'; require_once '../config/streamer_webhook.php';
$id=(int)$_SESSION['streamer_id']; $st=$pdo->prepare("SELECT * FROM streamers WHERE id=? AND active=1");$st->execute([$id]);$s=$st->fetch();if(!$s){session_destroy();header('Location: ../streamer-login.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 $type=$_POST['content_type']??'';$url=trim($_POST['url']??'');$date=$_POST['submission_date']??date('Y-m-d');
 if(!in_array($type,['link_comum','video_humor','video_novidades'],true))$error='Selecione um tipo válido.';
 elseif(!filter_var($url,FILTER_VALIDATE_URL))$error='Informe um link válido.';
 else{
  $chk=$pdo->prepare("SELECT COUNT(*) FROM content_submissions WHERE streamer_id=? AND url=? AND status IN ('pending','approved')");$chk->execute([$id,$url]);
  if((int)$chk->fetchColumn())$error='Este link já foi enviado.';
  else{$ins=$pdo->prepare("INSERT INTO content_submissions(streamer_id,content_type,url,submission_date) VALUES(?,?,?,?)");$ins->execute([$id,$type,$url,$date]);$sid=(int)$pdo->lastInsertId();sendStreamerWebhook($pdo,$id,'📱 Conteúdo enviado para análise','Seu conteúdo foi enviado para a Staff. Os pontos só entram após aprovação.','info',['Tipo informado'=>['link_comum'=>'Link Comum','video_humor'=>'Vídeo Humorístico','video_novidades'=>'Vídeo de Novidades'][$type],'Data'=>date('d/m/Y',strtotime($date)),'Status'=>'Aguardando análise','Envio'=>'#'.$sid]);header('Location: index.php?submission=success');exit;}
 }
}
$weekUsed=socialPointsUsed($pdo,$id,date('Y-m-d'));$limit=contentPoint($pdo,'weekly_social_points_limit',30);
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Conteúdo Extra</title><link rel="stylesheet" href="../assets/css/style.css"></head><body class="streamer-area"><main class="container"><a href="index.php">← Meu painel</a><h1>Conteúdo para Redes Sociais</h1><p class="muted">Todo conteúdo é analisado pela Staff antes de gerar pontos.</p><?php if($error):?><div class="alert error"><?=htmlspecialchars($error)?></div><?php endif;?>
<div class="stats"><div class="stat"><small>LIMITE SEMANAL</small><strong><?=min($weekUsed,$limit)?>/<?=$limit?></strong><span class="stat-note">pontos de conteúdo</span></div></div>
<form class="form" method="post"><label>Tipo</label><select name="content_type" required><option value="link_comum">Link Comum — +<?=contentPoint($pdo,'social_common_points',3)?> pts</option><option value="video_humor">Vídeo Humorístico — +<?=contentPoint($pdo,'social_humor_points',20)?> pts</option><option value="video_novidades">Vídeo de Novidades — +<?=contentPoint($pdo,'social_news_points',10)?> pts</option></select><label>Link</label><input type="url" name="url" required placeholder="https://..."><label>Data</label><input type="date" name="submission_date" value="<?=date('Y-m-d')?>" required><button class="btn primary">Enviar para análise</button></form>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>