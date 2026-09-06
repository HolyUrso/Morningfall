<?php
require_once '../config/streamer_auth.php';
require_once '../config/database.php';
require_once '../config/vod_points.php';
require_once '../config/content_points.php';
function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function streamerSetting(PDO $pdo,string $key,string $default=''): string {
 $st=$pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1'); $st->execute([$key]); $v=$st->fetchColumn();
 return $v===false?$default:(string)$v;
}
function formatDurationHM(int $m): string { return sprintf('%02d:%02d',intdiv($m,60),$m%60); }
$id=(int)$_SESSION['streamer_id'];
$st=$pdo->prepare("SELECT * FROM streamers WHERE id=? AND active=1"); $st->execute([$id]); $streamer=$st->fetch();
if(!$streamer){session_destroy();header('Location: ../streamer-login.php');exit;}

$cooldownDays=max(0,(int)(getSetting($pdo,'redemption_cooldown_days',7)));
$lastApprovedQ=$pdo->prepare("SELECT reviewed_at FROM redemptions WHERE streamer_id=? AND status='approved' AND reviewed_at IS NOT NULL ORDER BY reviewed_at DESC LIMIT 1");
$lastApprovedQ->execute([$id]);
$lastApprovedAt=$lastApprovedQ->fetchColumn();
$cooldownUntil=null;
$cooldownActive=false;
$cooldownRemainingDays=0;
if($lastApprovedAt && $cooldownDays>0){
    $cooldownUntil=(new DateTime($lastApprovedAt))->modify('+'.$cooldownDays.' days');
    $cooldownActive=(new DateTime('now')) < $cooldownUntil;
    if($cooldownActive){
        $seconds=max(0,$cooldownUntil->getTimestamp()-time());
        $cooldownRemainingDays=max(1,(int)ceil($seconds/86400));
    }
}
$pendingAnyQ=$pdo->prepare("SELECT COUNT(*) FROM redemptions WHERE streamer_id=? AND status='pending'");
$pendingAnyQ->execute([$id]);
$hasPendingAny=(int)$pendingAnyQ->fetchColumn()>0;
$bg='../'.streamerSetting($pdo,'streamer_background','assets/img/streamer-background-default.png');
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Resgatar pontos • Morningfall Creators</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="streamer-page" style="--streamer-bg:url('<?=esc($bg)?>')" style="--streamer-bg:url('<?=esc($bg)?>')"><main class="container">
<div class="page-back"><a href="index.php">← Meu painel</a></div>
<section class="page-head"><p class="eyebrow">🎁 RECOMPENSAS</p><h1>Resgatar pontos</h1></section>
<?php if(isset($_GET['redeem']) && $_GET['redeem']!=='success'): ?><div class="alert error" style="margin-bottom:16px">❌ <?=esc($_GET['redeem'])?></div><?php elseif(isset($_GET['redeem']) && $_GET['redeem']==='success'): ?><div class="alert success" style="margin-bottom:16px">✅ Solicitação de resgate enviada para análise da Staff.</div><?php endif; ?>
<?php $cat=$streamer['category']; $prizes=$pdo->query("SELECT * FROM prizes WHERE active=1 ORDER BY points_{$cat} ASC,id ASC")->fetchAll(); ?>
<section class="panel">
  <div class="section-title">
    <div><p class="eyebrow">🎁 RECOMPENSAS</p><h2>Resgatar pontos</h2><p class="muted">Você pode solicitar apenas 1 resgate a cada <?=number_format($cooldownDays,0,',','.')?> dia(s), conforme a configuração da Staff.</p></div>
    <span class="muted">Saldo: <strong><?=number_format((int)$streamer['points'],0,',','.')?> pts</strong></span>
  </div>
  <?php if($hasPendingAny): ?><div class="alert success" style="margin:0 0 16px">🎁 Você possui um resgate em análise. Aguarde a Staff concluir a análise antes de solicitar outro.</div>
  <?php elseif($cooldownActive): ?><div class="alert" style="margin:0 0 16px">⏳ Seu último resgate foi aprovado em <?=date('d/m/Y H:i',strtotime($lastApprovedAt))?>. Você poderá solicitar outro a partir de <?=esc($cooldownUntil->format('d/m/Y H:i'))?> (faltam aproximadamente <?=$cooldownRemainingDays?> dia(s)).</div><?php endif; ?>
  <div class="card-grid-modern">
  <?php foreach($prizes as $rp): $cost=(int)$rp['points_'.$cat]; $can=$cost>0 && (int)$streamer['points']>=$cost; $has=$pdo->prepare("SELECT COUNT(*) FROM redemptions WHERE streamer_id=? AND prize_id=? AND status='pending'"); $has->execute([$id,$rp['id']]); $inProgress=(int)$has->fetchColumn()>0; $progress=$cost>0?min(100,(int)floor(((int)$streamer['points']/$cost)*100)):0; ?>
    <article class="prize-card"><div class="card-topline"><span class="card-label">🎁 RECOMPENSA</span><span class="prize-price"><?=number_format($cost,0,',','.')?> pts</span></div><strong class="card-title"><?=esc($rp['name'])?></strong><?php if($rp['description']): ?><p class="muted"><?=esc($rp['description'])?></p><?php endif; ?><div class="card-progress"><span style="width:<?=$progress?>%"></span></div><div class="card-footer">
      <?php if($inProgress): ?><span class="card-status pending">Em análise</span>
      <?php elseif($cooldownActive): ?><span class="muted">Disponível após <?=esc($cooldownUntil->format('d/m/Y'))?></span>
      <?php elseif($hasPendingAny): ?><span class="muted">Aguarde seu resgate em análise</span>
      <?php elseif($can): ?><span class="muted">Você tem <?=number_format((int)$streamer['points'],0,',','.')?> pts</span><form method="post" action="resgatar.php" onsubmit="return confirm('Solicitar esta recompensa?')"><input type="hidden" name="prize_id" value="<?=$rp['id']?>"><button class="card-action">Resgatar</button></form>
      <?php else: ?><span class="muted">Faltam <?=number_format(max(0,$cost-(int)$streamer['points']),0,',','.')?> pts</span><?php endif; ?>
    </div></article>
  <?php endforeach; ?></div>
</section>
<div style="margin:18px 0"><a class="btn" href="index.php">← Voltar ao meu painel</a></div>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>