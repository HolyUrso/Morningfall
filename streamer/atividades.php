<?php
require_once '../config/streamer_auth.php';
require_once '../config/database.php';
require_once '../config/vod_points.php';
require_once '../config/content_points.php';

function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function formatDurationHM(int $minutes): string { return sprintf('%02d:%02d', intdiv($minutes,60), $minutes%60); }
function streamerSetting(PDO $pdo, string $key, string $default=''): string {
    $st=$pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
    $st->execute([$key]); $v=$st->fetchColumn();
    return $v===false?$default:(string)$v;
}
function getSetting(PDO $pdo,string $key,$default=0){
    $st=$pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
    $st->execute([$key]); $v=$st->fetchColumn();
    return $v===false?$default:$v;
}

$id=(int)$_SESSION['streamer_id'];
$st=$pdo->prepare("SELECT * FROM streamers WHERE id=? AND active=1");
$st->execute([$id]); $streamer=$st->fetch();
if(!$streamer){session_destroy();header('Location: ../streamer-login.php');exit;}

try { ensureVodPointsMigrated($pdo); } catch(Throwable $e) {}

$cat=$streamer['category'];
$labels=['pending'=>'Pendente','approved'=>'Aprovado','delivered'=>'Entregue','cancelled'=>'Cancelado','rejected'=>'Recusado'];

$vodQ=$pdo->prepare("SELECT * FROM vods WHERE streamer_id=? ORDER BY vod_date DESC,created_at DESC LIMIT 8");
$vodQ->execute([$id]); $vods=$vodQ->fetchAll();

$ptsQ=$pdo->prepare("SELECT * FROM point_transactions WHERE streamer_id=? ORDER BY created_at DESC LIMIT 12");
$ptsQ->execute([$id]); $points=$ptsQ->fetchAll();

$prizes=$pdo->query("SELECT * FROM prizes WHERE active=1 ORDER BY points_{$cat} ASC,id ASC")->fetchAll();

$redQ=$pdo->prepare("SELECT r.*,p.name prize_name FROM redemptions r JOIN prizes p ON p.id=r.prize_id WHERE r.streamer_id=? ORDER BY r.created_at DESC LIMIT 10");
$redQ->execute([$id]); $redemptions=$redQ->fetchAll();

$bgSetting=streamerSetting($pdo,'streamer_background','assets/img/streamer-background-default.png');
$bg='../'.$bgSetting;
$logoSetting=streamerSetting($pdo,'streamer_logo','');
$logo=$logoSetting?'../'.$logoSetting:'';
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Atividades • Morningfall Creators</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="streamer-page" style="--streamer-bg:url('<?=esc($bg)?>')">
<main class="container">
<div class="page-back"><a href="index.php">← Meu painel</a></div>

<?php if($logo): ?><div class="streamer-logo"><img src="<?=esc($logo)?>" alt="Morningfall Creators"></div><?php endif; ?>

<section class="page-head">
  <p class="eyebrow">📊 CENTRAL DO STREAMER</p>
  <h1>Atividades, pontos e recompensas</h1>
  <p class="muted">Tudo organizado em cards para você acompanhar sua evolução.</p>
</section>

<section class="panel">
  <div class="section-title">
    <div><p class="eyebrow">🎥 ATIVIDADE RECENTE</p><h2>Minhas VODs</h2></div>
    <span class="muted">Últimas 8</span>
  </div>
  <?php if(!$vods): ?><div class="card-empty">🎥 Nenhuma VOD registrada.</div>
  <?php else: ?><div class="card-grid-modern">
    <?php foreach($vods as $v): ?>
    <article class="activity-card">
      <div class="card-topline"><span class="card-label">🎥 VOD / LIVE</span><span class="card-points">+<?=number_format((int)$v['points_awarded'],0,',','.')?> pts</span></div>
      <strong class="card-title">Transmissão de <?=date('d/m/Y',strtotime($v['vod_date']))?></strong>
      <div class="card-meta"><span>⏱️ <?=formatDurationHM((int)$v['duration_minutes'])?></span><span>📅 <?=date('d/m/Y',strtotime($v['vod_date']))?></span></div>
      <div class="card-footer"><span class="muted">Pontuação da atividade</span><a class="card-action" href="<?=esc($v['url'])?>" target="_blank" rel="noopener">Abrir VOD ↗</a></div>
    </article>
    <?php endforeach; ?>
  </div><?php endif; ?>
</section>

<section class="panel">
  <div class="section-title">
    <div><p class="eyebrow">⭐ PONTUAÇÃO</p><h2>Histórico de pontos</h2></div>
    <span class="muted">Últimas 12</span>
  </div>
  <?php if(!$points): ?><div class="card-empty">⭐ Nenhuma movimentação de pontos.</div>
  <?php else: ?><div class="card-grid-modern">
    <?php foreach($points as $p): $positive=(int)$p['points']>=0; ?>
    <article class="point-card">
      <div class="card-topline"><span class="card-label">⭐ <?=esc($p['type']??'Movimentação')?></span><span class="card-points <?=$positive?'':'negative'?>"><?=((int)$p['points']>0?'+':'')?><?=number_format((int)$p['points'],0,',','.')?> pts</span></div>
      <strong class="card-title"><?=esc($p['description'])?></strong>
      <div class="card-meta"><span>📅 <?=date('d/m/Y',strtotime($p['created_at']))?></span><span>🕒 <?=date('H:i',strtotime($p['created_at']))?></span></div>
    </article>
    <?php endforeach; ?>
  </div><?php endif; ?>
</section>

<section class="panel">
  <div class="section-title">
    <div><p class="eyebrow">🎁 RECOMPENSAS</p><h2>Resgatar pontos</h2></div>
    <span class="muted">Saldo: <strong><?=number_format((int)$streamer['points'],0,',','.')?> pts</strong></span>
  </div>
  <?php if(isset($_GET['redeem'])): ?><div class="alert <?=($_GET['redeem']==='success'?'success':'error')?>"><?=($_GET['redeem']==='success'?'Pedido de resgate enviado para a Staff.':esc($_GET['redeem']))?></div><?php endif; ?>
  <div class="card-grid-modern">
  <?php foreach($prizes as $rp):
    $cost=(int)$rp['points_'.$cat];
    $can=$cost>0 && (int)$streamer['points'] >= $cost;
    $has=$pdo->prepare("SELECT COUNT(*) FROM redemptions WHERE streamer_id=? AND prize_id=? AND status='pending'");
    $has->execute([$id,$rp['id']]); $inProgress=(int)$has->fetchColumn()>0;
    $progress=$cost>0?min(100,(int)floor(((int)$streamer['points']/$cost)*100)):0;
  ?>
  <article class="prize-card">
    <div class="card-topline"><span class="card-label">🎁 RECOMPENSA</span><span class="prize-price"><?=number_format($cost,0,',','.')?> pts</span></div>
    <strong class="card-title"><?=esc($rp['name'])?></strong>
    <?php if($rp['description']): ?><p class="muted"><?=esc($rp['description'])?></p><?php endif; ?>
    <div class="card-progress"><span style="width:<?=$progress?>%"></span></div>
    <div class="card-footer">
      <?php if($inProgress): ?><span class="card-status pending">Em análise</span>
      <?php elseif($can): ?>
        <span class="muted">Você tem <?=number_format((int)$streamer['points'],0,',','.')?> pts</span>
        <form method="post" action="resgatar.php" onsubmit="return confirm('Solicitar esta recompensa por <?=number_format($cost,0,',','.')?> pontos? Os pontos só serão descontados se a Staff aprovar.')"><input type="hidden" name="prize_id" value="<?=$rp['id']?>"><button class="card-action">Resgatar</button></form>
      <?php else: ?><span class="muted">Faltam <?=number_format(max(0,$cost-(int)$streamer['points']),0,',','.')?> pts</span><?php endif; ?>
    </div>
  </article>
  <?php endforeach; ?>
  </div>
</section>

<section class="panel">
  <div class="section-title"><div><p class="eyebrow">📋 HISTÓRICO</p><h2>Meus resgates</h2></div><span class="muted">Últimos 10</span></div>
  <?php if(!$redemptions): ?><div class="card-empty">🎁 Nenhum resgate solicitado.</div>
  <?php else: ?><div class="card-grid-modern">
    <?php foreach($redemptions as $r):
      $label=$labels[$r['status']]??$r['status'];
      $cls=in_array($r['status'],['approved','delivered','pending','rejected','cancelled'],true)?$r['status']:'default';
    ?>
    <article class="redemption-card">
      <div class="card-topline"><span class="card-label">🎁 RESGATE</span><span class="card-status <?=$cls?>"><?=esc($label)?></span></div>
      <strong class="card-title"><?=esc($r['prize_name'])?></strong>
      <div class="card-meta"><span>⭐ <?=number_format((int)$r['points_spent'],0,',','.')?> pts</span><span>📅 <?=date('d/m/Y',strtotime($r['created_at']))?></span><span>🕒 <?=date('H:i',strtotime($r['created_at']))?></span></div>
    </article>
    <?php endforeach; ?>
  </div><?php endif; ?>
</section>

<div style="margin:18px 0"><a class="btn" href="index.php">← Voltar ao meu painel</a></div>
</main>
<footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
</body>
</html>
