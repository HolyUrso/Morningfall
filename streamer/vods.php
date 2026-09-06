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
<title>Minhas VODs • Morningfall Creators</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="streamer-page" style="--streamer-bg:url('<?=esc($bg)?>')" style="--streamer-bg:url('<?=esc($bg)?>')"><main class="container">
<div class="page-back"><a href="index.php">← Meu painel</a></div>
<section class="page-head"><p class="eyebrow">🎥 ATIVIDADE</p><h1>Minhas VODs</h1></section>
<?php $q=$pdo->prepare("SELECT * FROM vods WHERE streamer_id=? ORDER BY vod_date DESC,created_at DESC LIMIT 30"); $q->execute([$id]); $rows=$q->fetchAll(); ?><section class="panel"><div class="section-title"><div><p class="eyebrow">🎥 ATIVIDADE</p><h2>Minhas VODs</h2></div><span class="muted">Últimas 30</span></div><?php if(!$rows): ?><div class="card-empty">🎥 Nenhuma VOD registrada.</div><?php else: ?><div class="card-grid-modern"><?php foreach($rows as $v): ?><article class="activity-card"><div class="card-topline"><span class="card-label">🎥 VOD / LIVE</span><span class="card-points">+<?=number_format((int)$v['points_awarded'],0,',','.')?> pts</span></div><strong class="card-title">Transmissão de <?=date('d/m/Y',strtotime($v['vod_date']))?></strong><div class="card-meta"><span>⏱️ <?=formatDurationHM((int)$v['duration_minutes'])?></span><span>📅 <?=date('d/m/Y',strtotime($v['vod_date']))?></span></div><div class="card-footer"><span class="muted">Pontuação da atividade</span><?php if(trim((string)$v['url'])!==''): $vodUrl=trim((string)$v['url']); $vodHost=parse_url($vodUrl,PHP_URL_HOST) ?: 'Domínio não identificado'; $vodHost=preg_replace('/^www\./i','',$vodHost); ?><div style="width:100%;margin-top:8px"><div style="font-size:11px;color:#9aa6bb;word-break:break-all">🔗 <?=esc($vodHost)?></div><div style="font-size:12px;color:#d9e2f2;word-break:break-all;margin:4px 0 8px"><?=esc($vodUrl)?></div><a class="card-action" href="<?=esc($vodUrl)?>" target="_blank" rel="noopener noreferrer">Abrir VOD ↗</a></div><?php else: ?><span class="muted">📸 Envio por comprovante</span><?php endif; ?></div></article><?php endforeach; ?></div><?php endif; ?></section>
<div style="margin:18px 0"><a class="btn" href="index.php">← Voltar ao meu painel</a></div>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>