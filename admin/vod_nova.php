<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/vod_points.php';
require_once '../config/streamer_webhook.php';

$streamers=$pdo->query("SELECT id,name,category FROM streamers WHERE active=1 ORDER BY name")->fetchAll();

if($_SERVER['REQUEST_METHOD']==='POST'){
 $sid=(int)$_POST['streamer_id'];
 $duration=trim($_POST['duration_hm'] ?? '');
 $vodDate=$_POST['vod_date'] ?? date('Y-m-d');

 if(!preg_match('/^(?:[0-9]{1,3}):([0-5][0-9])$/',$duration,$m)){
  $error='Informe a duração no formato HH:MM. Exemplo: 01:30.';
 } else {
  [$hh,$mm]=array_map('intval',explode(':',$duration));
  $minutes=($hh*60)+$mm;
  if($minutes<=0){ $error='A duração deve ser maior que 00:00.'; }
 }

 if(empty($error)){
  ensureVodPointsMigrated($pdo, (int)$_SESSION['staff_id']);
  $pdo->beginTransaction();
  try{
   $st=$pdo->prepare("INSERT INTO vods(streamer_id,url,vod_date,duration_minutes,points_awarded) VALUES(?,?,?,?,0)");
   $st->execute([$sid,trim($_POST['url']),$vodDate,$minutes]);
   recalculateVodPointsForDate($pdo,$sid,$vodDate,(int)$_SESSION['staff_id']);
   $pointsQ=$pdo->prepare("SELECT points_awarded FROM vods WHERE id=?");
   $pointsQ->execute([(int)$pdo->lastInsertId()]);
   $vodPoints=(int)$pointsQ->fetchColumn();
   $dayQ=$pdo->prepare("SELECT COALESCE(SUM(points_awarded),0) FROM vods WHERE streamer_id=? AND vod_date=?");
   $dayQ->execute([$sid,$vodDate]);
   $dayPoints=(int)$dayQ->fetchColumn();
   $pdo->commit();
   $staffQ=$pdo->prepare("SELECT name FROM staff_users WHERE id=?");
   $staffQ->execute([(int)$_SESSION['staff_id']]);
   $staffName=(string)($staffQ->fetchColumn() ?: 'Staff');
   sendStreamerWebhook($pdo, $sid, '🎥 VOD registrada', 'Uma nova VOD foi registrada e a pontuação do dia foi recalculada.', 'info', [
      'Data' => date('d/m/Y', strtotime($vodDate)),
      'Duração' => sprintf('%02d:%02d', intdiv($minutes,60), $minutes%60),
      'Pontos desta VOD' => '+'.$vodPoints,
      'Total de pontos no dia' => $dayPoints,
      'Staff' => $staffName
   ]);
   header('Location: vods.php');exit;
  }catch(Throwable $e){$pdo->rollBack();throw $e;}
 }
}
?><!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Nova VOD</title><link rel="stylesheet" href="../assets/css/style.css"></head><body><main class="container"><a href="vods.php">← VODs</a><h1>Cadastrar VOD</h1><?php if(!empty($error)): ?><p class="muted" style="color:#ff8a8a"><?=htmlspecialchars($error)?></p><?php endif; ?><form method="post" class="form">
<select name="streamer_id" required><option value="">Selecione o streamer</option><?php foreach($streamers as $s): ?><option value="<?=$s['id']?>" <?=((int)($_POST['streamer_id']??0)===(int)$s['id'])?'selected':''?>><?=htmlspecialchars($s['name'])?> — <?=ucfirst($s['category'])?></option><?php endforeach;?></select>
<div class="field-with-action"><input id="vod_url" type="url" name="url" placeholder="Link da VOD (YouTube/Twitch)" value="<?=htmlspecialchars($_POST['url']??'')?>" required><button type="button" id="btn-fetch-duration" class="btn secondary">Buscar duração</button></div>
<input type="date" name="vod_date" value="<?=htmlspecialchars($_POST['vod_date']??date('Y-m-d'))?>" required>
<div class="field-with-action"><input id="duration_hm" type="text" name="duration_hm" inputmode="numeric" pattern="[0-9]{1,3}:[0-5][0-9]" placeholder="Duração (HH:MM)" value="<?=htmlspecialchars($_POST['duration_hm']??'')?>" required><span id="duration-status" class="muted">Informe HH:MM ou busque automaticamente.</span></div>
<button class="btn primary">Cadastrar</button></form><p class="muted">A duração pode ser preenchida automaticamente a partir de VODs públicas do YouTube/Twitch. Se a plataforma não permitir a leitura automática, informe HH:MM manualmente.</p><p class="muted">Regra: 1 ponto por hora completa, somando todas as VODs do mesmo streamer na mesma data, com máximo de 10 pontos por dia.</p>
<script>
const urlInput=document.getElementById('vod_url'), durationInput=document.getElementById('duration_hm');
const btn=document.getElementById('btn-fetch-duration'), statusEl=document.getElementById('duration-status');
btn.addEventListener('click', async ()=>{
  const url=urlInput.value.trim();
  if(!url){ statusEl.textContent='Informe primeiro o link da VOD.'; return; }
  btn.disabled=true; btn.textContent='Buscando...'; statusEl.textContent='Consultando a VOD...';
  try{
    const r=await fetch('../api/vod_duration.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({url})});
    const data=await r.json();
    if(!r.ok || !data.success) throw new Error(data.message||'Não foi possível obter a duração.');
    durationInput.value=data.duration_hm;
    statusEl.textContent='Duração encontrada automaticamente: '+data.duration_hm;
  }catch(e){ statusEl.textContent=e.message+' Você pode preencher HH:MM manualmente.'; }
  finally{ btn.disabled=false; btn.textContent='Buscar duração'; }
});
</script></main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>
