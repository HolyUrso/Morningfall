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
$bg='../'.streamerSetting($pdo,'streamer_background','assets/img/streamer-background-default.png');
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Meus resgates • Morningfall Creators</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="streamer-page" style="--streamer-bg:url('<?=esc($bg)?>')" style="--streamer-bg:url('<?=esc($bg)?>')"><main class="container">
<div class="page-back"><a href="index.php">← Meu painel</a></div>
<section class="page-head"><p class="eyebrow">📋 RESGATES</p><h1>Meus resgates</h1></section>
<?php $labels=['pending'=>'Pendente','approved'=>'Aprovado','delivered'=>'Entregue','cancelled'=>'Cancelado','rejected'=>'Recusado']; $q=$pdo->prepare("SELECT r.*,p.name prize_name FROM redemptions r JOIN prizes p ON p.id=r.prize_id WHERE r.streamer_id=? ORDER BY r.created_at DESC LIMIT 30"); $q->execute([$id]); $rows=$q->fetchAll(); ?><section class="panel"><div class="section-title"><div><p class="eyebrow">📋 RESGATES</p><h2>Meus resgates</h2></div><span class="muted">Últimos 30</span></div><?php if(!$rows): ?><div class="card-empty">🎁 Nenhum resgate solicitado.</div><?php else: ?><div class="card-grid-modern"><?php foreach($rows as $r): $cls=in_array($r['status'],['approved','delivered','pending','rejected','cancelled'],true)?$r['status']:'default'; ?><article class="redemption-card"><div class="card-topline"><span class="card-label">🎁 RESGATE</span><span class="card-status <?=$cls?>"><?=esc($labels[$r['status']]??$r['status'])?></span></div><strong class="card-title"><?=esc($r['prize_name'])?></strong><div class="card-meta"><span>⭐ <?=number_format((int)$r['points_spent'],0,',','.')?> pts</span><span>📅 <?=date('d/m/Y',strtotime($r['created_at']))?></span><span>🕒 <?=date('H:i',strtotime($r['created_at']))?></span></div></article><?php endforeach; ?></div><?php endif; ?></section>
<div style="margin:18px 0"><a class="btn" href="index.php">← Voltar ao meu painel</a></div>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>