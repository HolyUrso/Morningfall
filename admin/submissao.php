<?php
require_once '../config/auth.php'; require_once '../config/database.php';
require_once '../config/audit.php'; require_once '../config/content_points.php'; require_once '../config/streamer_webhook.php';
$id=(int)($_GET['id']??$_POST['id']??0);
$st=$pdo->prepare("SELECT c.*,s.name streamer_name,s.category streamer_category,cs.name collab_name FROM content_submissions c JOIN streamers s ON s.id=c.streamer_id LEFT JOIN streamers cs ON cs.id=c.collab_streamer_id WHERE c.id=?");$st->execute([$id]);$c=$st->fetch();if(!$c)exit('Envio não encontrado.');
$error='';
$bonus=0;
$imgQ=$pdo->prepare('SELECT * FROM content_submission_images WHERE submission_id=? ORDER BY id ASC'); $imgQ->execute([$id]); $proofImages=$imgQ->fetchAll();
if($_SERVER['REQUEST_METHOD']==='POST'){
 $action=$_POST['action']??'';
 if($action==='unapprove' && $c['status']==='approved'){
  try{
   $sid=(int)$c['streamer_id'];
   $staffId=(int)$_SESSION['staff_id'];
   $pdo->beginTransaction();
   if($c['content_type']==='vod'){
    $vodId=(int)($c['vod_id']??0);
    $date=$c['submission_date'];
    if($vodId){
      $pdo->prepare("DELETE FROM point_transactions WHERE reference_type='vod' AND reference_id=?")->execute([$vodId]);
      $pdo->prepare("DELETE FROM vods WHERE id=? AND streamer_id=?")->execute([$vodId,$sid]);
    }
    // Remove a possible Collab/Raid bonus tied to this submission.
    $pdo->prepare("DELETE FROM point_transactions WHERE streamer_id=? AND reference_type='collab' AND reference_id=?")->execute([$sid,$id]);
    recalculateVodPointsForDate($pdo,$sid,$date,$staffId);

    // If the monthly 20-day bonus is no longer justified, remove it.
    $monthStart=date('Y-m-01',strtotime($date)); $monthEnd=date('Y-m-t',strtotime($date));
    $target=contentPoint($pdo,'monthly_live_days_target',20);
    $daysQ=$pdo->prepare("SELECT COUNT(DISTINCT vod_date) FROM vods WHERE streamer_id=? AND vod_date BETWEEN ? AND ?");
    $daysQ->execute([$sid,$monthStart,$monthEnd]);
    $days=(int)$daysQ->fetchColumn();
    if($days<$target){
      $ref=(int)date('Ym',strtotime($monthStart));
      $bonusQ=$pdo->prepare("SELECT COALESCE(SUM(points),0) FROM point_transactions WHERE streamer_id=? AND reference_type='monthly_live_days' AND reference_id=?");
      $bonusQ->execute([$sid,$ref]);
      $bonus=(int)$bonusQ->fetchColumn();
      if($bonus>0){
        $pdo->prepare("DELETE FROM point_transactions WHERE streamer_id=? AND reference_type='monthly_live_days' AND reference_id=?")->execute([$sid,$ref]);
        $pdo->prepare("UPDATE streamers SET points=GREATEST(0,points-?) WHERE id=?")->execute([$bonus,$sid]);
      }
    }
    $weeklyBonusRemoved=removeWeeklyLiveDaysBonusIfNeeded($pdo,$sid,$date);
    $weeklyHoursBonusRemoved=removeWeeklyLiveHoursBonusIfNeeded($pdo,$sid,$date);
    $pdo->prepare("UPDATE content_submissions SET status='pending', points_awarded=0, vod_id=NULL, duration_minutes=NULL, reviewed_by=NULL, reviewed_at=NULL, staff_notes=CONCAT(COALESCE(staff_notes,''), CASE WHEN COALESCE(staff_notes,'')='' THEN '' ELSE ' | ' END, 'Aprovação revertida pela Staff.') WHERE id=?")->execute([$id]);
    $pdo->commit();
    sendStreamerWebhook($pdo,$sid,'↩️ VOD desaprovada','A Staff reverteu a aprovação da sua VOD. Os pontos desta aprovação foram retirados e a VOD voltou para análise.','warning',['Data'=>date('d/m/Y',strtotime($date)),'Duração'=> $c['duration_minutes']!==null ? sprintf('%02d:%02d',intdiv((int)$c['duration_minutes'],60),(int)$c['duration_minutes']%60) : '—','Status'=>'Pendente novamente','Bônus dias semanal removido'=>$weeklyBonusRemoved?'-'.$weeklyBonusRemoved.' pts':'Não','Bônus horas semanal removido'=>$weeklyHoursBonusRemoved?'-'.$weeklyHoursBonusRemoved.' pts':'Não','Staff'=>$_SESSION['staff_name']??'Staff']);
   } else {
    $tx=$pdo->prepare("SELECT COALESCE(SUM(points),0) FROM point_transactions WHERE streamer_id=? AND reference_type='content' AND reference_id=?");
    $tx->execute([$sid,$id]); $points=(int)$tx->fetchColumn();
    $pdo->prepare("DELETE FROM point_transactions WHERE streamer_id=? AND reference_type='content' AND reference_id=?")->execute([$sid,$id]);
    if($points>0) $pdo->prepare("UPDATE streamers SET points=GREATEST(0,points-?) WHERE id=?")->execute([$points,$sid]);
    $pdo->prepare("UPDATE content_submissions SET status='pending', points_awarded=0, reviewed_by=NULL, reviewed_at=NULL, staff_notes=CONCAT(COALESCE(staff_notes,''), CASE WHEN COALESCE(staff_notes,'')='' THEN '' ELSE ' | ' END, 'Aprovação revertida pela Staff.') WHERE id=?")->execute([$id]);
    $pdo->commit();
    sendStreamerWebhook($pdo,$sid,'↩️ Conteúdo desaprovado','A Staff reverteu a aprovação do seu conteúdo. Os pontos foram retirados e o envio voltou para análise.','warning',['Tipo'=>ctLabel($c['content_type']),'Pontos removidos'=>'-'.$points,'Status'=>'Pendente novamente','Bônus dias semanal removido'=>$weeklyBonusRemoved?'-'.$weeklyBonusRemoved.' pts':'Não','Bônus horas semanal removido'=>$weeklyHoursBonusRemoved?'-'.$weeklyHoursBonusRemoved.' pts':'Não','Staff'=>$_SESSION['staff_name']??'Staff']);
   }
   header('Location: submissao.php?id='.$id); exit;
  }catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); $error=$e->getMessage(); }
 }
 if($c['status']==='pending'){
  $action=$_POST['action']??'';$notes=trim($_POST['staff_notes']??'');
 try{
  if($action==='reject'){
   $up=$pdo->prepare("UPDATE content_submissions SET status='rejected',staff_notes=?,reviewed_by=?,reviewed_at=NOW() WHERE id=? AND status='pending'");$up->execute([$notes,(int)$_SESSION['staff_id'],$id]);
   auditLog($pdo,'VOD/Conteúdo recusado','VOD',(int)$c['streamer_id'],$c['streamer_name'],'Envio recusado pela Staff. Comprovantes removidos.', ['status'=>'pending'], ['status'=>'rejected','notes'=>$notes]);
   deleteSubmissionProofs($pdo,$id);
   sendStreamerWebhook($pdo,(int)$c['streamer_id'],'❌ Conteúdo recusado','A Staff recusou o conteúdo enviado.', 'danger',['Tipo'=>ctLabel($c['content_type']),'Motivo'=>$notes?:'Não informado','Staff'=>$_SESSION['staff_name']??'Staff','Envio'=>'#'.$id]);
   header('Location: submissoes.php');exit;
  }
  if($action!=='approve')throw new RuntimeException('Ação inválida.');
  $date=$_POST['submission_date']??$c['submission_date'];$type=$c['content_type']; if($type!=='vod') $type=$_POST['content_type']??$type;$collab=!empty($_POST['collab'])?1:0;$collabId=$collab?(int)($_POST['collab_streamer_id']??0):null;
  if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))throw new RuntimeException('Data inválida.');
  if($type==='vod'){
   $hm=trim($_POST['duration_hm']??'');
   if(!preg_match('/^(\d{1,3}):([0-5]\d)$/',$hm,$m))throw new RuntimeException('Informe a duração oficial em HH:MM para aprovar a VOD.');
   $minutes=((int)$m[1]*60)+(int)$m[2]; if($minutes<=0)throw new RuntimeException('A duração deve ser maior que zero.');
   if($collab && (!$collabId || $collabId===(int)$c['streamer_id']))throw new RuntimeException('Selecione outro streamer para validar a Collab/Raid.');
   $pdo->beginTransaction();
   $ins=$pdo->prepare("INSERT INTO vods(streamer_id,url,vod_date,duration_minutes,points_awarded) VALUES(?,?,?,?,0)");$ins->execute([(int)$c['streamer_id'],$c['url'],$date,$minutes]);$vodId=(int)$pdo->lastInsertId();
   recalculateVodPointsForDate($pdo,(int)$c['streamer_id'],$date,(int)$_SESSION['staff_id']);
   $collabPoints=0;
   if($collab){$collabPoints=contentPoint($pdo,'collab_raid_points',3);$tx=$pdo->prepare("INSERT INTO point_transactions(streamer_id,type,description,points,reference_type,reference_id,created_by) VALUES(?,'earn',?,?,?,?,?)");$tx->execute([(int)$c['streamer_id'],'Collab/Raid validada',$collabPoints,'collab',$id,(int)$_SESSION['staff_id']]);$pdo->prepare("UPDATE streamers SET points=points+? WHERE id=?")->execute([$collabPoints,(int)$c['streamer_id']]);}
   // Meta semanal: ao atingir a quantidade configurada de dias distintos com live, concede o bônus uma vez na semana.
   $weeklyBonus=applyWeeklyLiveDaysBonus($pdo,(int)$c['streamer_id'],$date,(int)$_SESSION['staff_id']);
   $weeklyHoursBonus=applyWeeklyLiveHoursBonus($pdo,(int)$c['streamer_id'],$date,(int)$_SESSION['staff_id']);
   // Meta mensal = elegibilidade ao sorteio. O prêmio só é concedido ao(s) sorteado(s).
   $up=$pdo->prepare("UPDATE content_submissions SET content_type='vod',submission_date=?,collab=?,collab_streamer_id=?,status='approved',vod_id=?,duration_minutes=?,points_awarded=?,staff_notes=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?");
   $up->execute([$date,$collab,$collabId,$vodId,$minutes,$collabPoints,$notes,(int)$_SESSION['staff_id'],$id]);
   auditLog($pdo,'VOD aprovada','VOD',(int)$c['streamer_id'],$c['streamer_name'],'VOD aprovada pela Staff.', ['status'=>'pending','duration_minutes'=>$c['duration_minutes']??null], ['status'=>'approved','duration_minutes'=>$minutes,'points'=>$collabPoints]);
   $pdo->commit();
   $dayQ=$pdo->prepare("SELECT COALESCE(SUM(points_awarded),0) FROM vods WHERE streamer_id=? AND vod_date=?");$dayQ->execute([(int)$c['streamer_id'],$date]);$dayPoints=(int)$dayQ->fetchColumn();
   sendStreamerWebhook($pdo,(int)$c['streamer_id'],'✅ VOD aprovada','A Staff aprovou sua VOD e a duração oficial foi registrada.','success',['Data'=>date('d/m/Y',strtotime($date)),'Duração'=>sprintf('%02d:%02d',intdiv($minutes,60),$minutes%60),'Pontos da VOD'=>'Recalculados pela regra diária','Collab/Raid'=>$collab?'+'.$collabPoints.' pts':'Não','Bônus meta semanal (dias)'=>$weeklyBonus?'+'.$weeklyBonus.' pts':'Não','Bônus meta semanal (horas)'=>$weeklyHoursBonus?'+'.$weeklyHoursBonus.' pts':'Não', 'Bônus 20 dias'=>$bonus?'+'.$bonus.' pts':'Não','Staff'=>$_SESSION['staff_name']??'Staff']);
  } else {
   $result=approveSocialContent($pdo,$id,(int)$_SESSION['staff_id'],$type,$notes);
   sendStreamerWebhook($pdo,(int)$c['streamer_id'],'✅ Conteúdo aprovado','A Staff aprovou seu conteúdo de rede social.', 'success',['Tipo'=>ctLabel($type),'Pontos'=>'+'.$result['points'],'Limite semanal'=>$result['used_before'].' + '.$result['points'].' / '.$result['limit'],'Staff'=>$_SESSION['staff_name']??'Staff']);
  }
  header('Location: submissoes.php');exit;
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();$error=$e->getMessage();}
}
}
$others=$pdo->prepare("SELECT id,name FROM streamers WHERE active=1 AND id<>? ORDER BY name");$others->execute([(int)$c['streamer_id']]);$others=$others->fetchAll();
function ctLabel($t){return ['vod'=>'VOD de Live','link_comum'=>'Link Comum','video_humor'=>'Vídeo Humorístico','video_novidades'=>'Vídeo de Novidades'][$t]??$t;}
function proofDurationLabel($minutes){ return ($minutes!==null && (int)$minutes>0) ? sprintf('%02d:%02d', intdiv((int)$minutes,60), (int)$minutes%60) : ''; }
function deleteSubmissionProofs(PDO $pdo, int $submissionId): void {
    $q=$pdo->prepare('SELECT file_path FROM content_submission_images WHERE submission_id=?');
    $q->execute([$submissionId]);
    foreach($q->fetchAll() as $img){
        $path=__DIR__.'/../'.ltrim((string)$img['file_path'],'/');
        if(is_file($path)) @unlink($path);
    }
    $pdo->prepare('DELETE FROM content_submission_images WHERE submission_id=?')->execute([$submissionId]);
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Analisar Conteúdo</title><link rel="stylesheet" href="../assets/css/style.css"></head><body><main class="container"><a href="submissoes.php">← Conteúdos enviados</a><div class="page-head"><div><p class="eyebrow">ANÁLISE DA STAFF</p><h1><?=ctLabel($c['content_type'])?></h1><p class="muted">Streamer: <strong><?=htmlspecialchars($c['streamer_name'])?></strong> · Envio #<?=$c['id']?></p></div><span class="status <?=htmlspecialchars($c['status'])?>"><?=ucfirst($c['status'])?></span></div>
<?php if($error):?><div class="alert error"><?=htmlspecialchars($error)?></div><?php endif;?>
<div class="panel dashboard-panel">
<?php if(trim((string)$c['url'])!==''): $vodUrl=trim((string)$c['url']); $vodHost=parse_url($vodUrl,PHP_URL_HOST) ?: 'Domínio não identificado'; $vodHost=preg_replace('/^www\./i','',$vodHost); $trustedHosts=['youtube.com'=>'YouTube','youtu.be'=>'YouTube','twitch.tv'=>'Twitch','tiktok.com'=>'TikTok','kick.com'=>'Kick','facebook.com'=>'Facebook','instagram.com'=>'Instagram']; $platform='Domínio não reconhecido'; foreach($trustedHosts as $host=>$label){ if(strtolower($vodHost)===$host || str_ends_with(strtolower($vodHost),'.'.$host)){ $platform=$label; break; }} ?>
<p style="margin-top:0"><strong>🔗 Link da VOD</strong></p>
<div style="padding:12px;border:1px solid #252e3e;border-radius:10px;background:#0d1119;word-break:break-all">
<div style="font-size:12px;color:#9aa6bb;margin-bottom:6px">Domínio: <strong style="color:#fff"><?=htmlspecialchars($vodHost)?></strong> · Plataforma: <strong style="color:#fff"><?=htmlspecialchars($platform)?></strong></div>
<div style="font-family:monospace;font-size:13px;color:#d9e2f2"><?=htmlspecialchars($vodUrl)?></div>
<div style="margin-top:10px"><a class="btn small-btn primary" href="<?=htmlspecialchars($vodUrl)?>" target="_blank" rel="noopener noreferrer">Abrir link ↗</a></div>
</div>
<p class="muted" style="font-size:12px;margin-bottom:0">Confira o domínio acima antes de abrir. O sistema não considera um link automaticamente confiável apenas por ser de uma plataforma conhecida.</p>
<?php else: ?><p style="margin-top:0"><strong>🔗 Link da VOD:</strong> <span class="muted">Não informado — envio realizado por comprovante.</span></p><?php endif; ?>
<p><strong>Tipo informado:</strong> <?=ctLabel($c['content_type'])?></p><p><strong>Data informada:</strong> <?=date('d/m/Y',strtotime($c['submission_date']))?></p><p><strong>Collab/Raid informado:</strong> <?=$c['collab']?'Sim':'Não'?><?= $c['collab_name']?' · '.htmlspecialchars($c['collab_name']):'' ?></p></div>
<?php if($proofImages): ?><div class="panel dashboard-panel"><h3 style="margin-top:0">📸 Comprovantes enviados</h3><div style="display:flex;flex-wrap:wrap;gap:12px"><?php foreach($proofImages as $img): ?><a href="../<?=htmlspecialchars($img['file_path'])?>" target="_blank" rel="noopener noreferrer" title="Clique para abrir em tamanho original" style="display:block;width:140px"><img src="../<?=htmlspecialchars($img['file_path'])?>" alt="Comprovante" style="display:block;width:140px;height:100px;object-fit:cover;border-radius:10px;border:1px solid #252e3e;background:#0d1119;cursor:pointer"><span class="muted" style="display:block;font-size:11px;margin-top:4px;text-align:center">Clique para ampliar</span></a><?php endforeach; ?></div><p class="muted" style="font-size:12px;margin-bottom:0">Até 3 prints/fotos enviados pelo streamer. Os arquivos são armazenados em WebP.</p></div><?php endif; ?>
<?php if($c['status']==='pending'):?>
<form method="post" class="form"><input type="hidden" name="id" value="<?=$id?>">
<?php if($c['content_type']==='vod'):?>
<label>Data oficial da live (Staff pode corrigir)</label><input type="date" name="submission_date" value="<?=htmlspecialchars($c['submission_date'])?>" required>
<label>Duração informada pelo streamer (HH:MM)</label><input type="text" name="duration_hm" pattern="[0-9]{1,3}:[0-5][0-9]" placeholder="03:04" value="<?=htmlspecialchars(proofDurationLabel($c['duration_minutes']))?>"><small class="muted">A Staff pode corrigir antes de aprovar. Para recusar, a duração não é obrigatória nem validada.</small>
<label class="check-row"><input type="checkbox" name="collab" value="1" <?=$c['collab']?'checked':''?>> Collab/Raid validada (+<?=contentPoint($pdo,'collab_raid_points',3)?> pontos)</label>
<div><label>Streamer da Collab/Raid</label><select name="collab_streamer_id"><option value="">Selecione</option><?php foreach($others as $o):?><option value="<?=$o['id']?>" <?=$c['collab_streamer_id']==$o['id']?'selected':''?>><?=htmlspecialchars($o['name'])?></option><?php endforeach;?></select></div>
<?php else:?>
<label>Tipo confirmado pela Staff (pode corrigir)</label><select name="content_type"><option value="link_comum">Link Comum — +<?=contentPoint($pdo,'social_common_points',3)?></option><option value="video_humor" <?=$c['content_type']==='video_humor'?'selected':''?>>Vídeo Humorístico — +<?=contentPoint($pdo,'social_humor_points',20)?></option><option value="video_novidades" <?=$c['content_type']==='video_novidades'?'selected':''?>>Vídeo de Novidades — +<?=contentPoint($pdo,'social_news_points',10)?></option></select>
<?php endif;?>
<label>Observação da Staff</label><textarea name="staff_notes" placeholder="Correção, justificativa ou observação..."></textarea>
<div class="actions"><button class="btn primary" name="action" value="approve">✅ Aprovar</button><button class="btn danger-btn" name="action" value="reject" onclick="return confirm('Recusar este conteúdo?')">❌ Recusar</button></div></form>
<?php elseif($c['status']==='approved'):?>
<div class="panel"><p>Este envio está <strong>aprovado</strong>. A Staff pode reverter a aprovação a qualquer momento.</p><p><?=nl2br(htmlspecialchars($c['staff_notes']??''))?></p>
<form method="post" class="actions" onsubmit="return confirm('Desaprovar este conteúdo? Os pontos gerados por esta aprovação serão retirados e o envio voltará para análise.');"><input type="hidden" name="id" value="<?=$id?>"><button class="btn danger-btn" name="action" value="unapprove">↩️ Desaprovar e retirar pontos</button></form></div>
<?php else:?><div class="panel"><p>Este envio já foi analisado.</p><p><?=nl2br(htmlspecialchars($c['staff_notes']??''))?></p></div><?php endif;?>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>