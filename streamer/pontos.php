<?php
require_once '../config/streamer_auth.php';
require_once '../config/database.php';

$id=(int)$_SESSION['streamer_id'];

$st=$pdo->prepare("SELECT id,name,category,points FROM streamers WHERE id=? AND active=1");
$st->execute([$id]);
$streamer=$st->fetch();

if(!$streamer){
    session_destroy();
    header('Location: ../streamer-login.php');
    exit;
}

$type=trim((string)($_GET['type']??''));
$allowed=['earn','penalty','adjustment','redemption_refund'];
$where='WHERE streamer_id=?';
$params=[$id];

if(in_array($type,$allowed,true)){
    $where.=' AND type=?';
    $params[]=$type;
}

$q=$pdo->prepare("SELECT id,type,description,points,reference_type,reference_id,created_at
                  FROM point_transactions
                  $where
                  ORDER BY created_at DESC,id DESC
                  LIMIT 300");
$q->execute($params);
$rows=$q->fetchAll();

function esc($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function typeLabel($type){
    return [
        'earn'=>'Ganho',
        'penalty'=>'Penalidade',
        'adjustment'=>'Bonificação/Ajuste',
        'redemption_refund'=>'Estorno'
    ][$type]??'Movimentação';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Histórico de Pontos · <?=esc($streamer['name'])?></title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.history-summary{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:20px 0}
.history-card{background:rgba(17,24,39,.92);border:1px solid #29344a;border-radius:16px;padding:20px}
.history-card small{color:#91a0b8;display:block;margin-bottom:8px}
.history-card strong{font-size:28px}
.history-filter{display:flex;gap:8px;flex-wrap:wrap;margin:18px 0}
.history-filter a{padding:9px 13px;border:1px solid #303b52;border-radius:10px;text-decoration:none;color:#b9c4d7}
.history-filter a.active{border-color:#7c5cff;background:#2a2050;color:#fff}
.points-positive{color:#61e6a5}
.points-negative{color:#ff7373}
.badge.adjustment{background:#34265e;color:#c8b5ff}
.history-desc{max-width:620px}
@media(max-width:700px){.history-summary{grid-template-columns:1fr}}
</style>
</head>
<body class="streamer-page" style="--streamer-bg:url('<?=esc($bg)?>')">
<header class="topbar">
<div><b>MORNINGFALL</b> <span>CREATORS</span></div>
<div><?=esc($streamer['name'])?> · <a href="../streamer-logout.php">Sair</a></div>
</header>

<main class="container">
<a href="index.php">← Meu painel</a>

<div class="page-head">
<div>
<p class="eyebrow">ÁREA DO STREAMER</p>
<h1>📊 Histórico de Pontos</h1>
<p class="muted">Confira de onde vieram suas pontuações e todos os ajustes registrados.</p>
</div>
</div>

<div class="history-summary">
<div class="history-card">
<small>PONTOS ATUAIS</small>
<strong><?=number_format((int)$streamer['points'],0,',','.')?> pts</strong>
</div>
<div class="history-card">
<small>MOVIMENTAÇÕES EXIBIDAS</small>
<strong><?=number_format(count($rows),0,',','.')?></strong>
</div>
</div>

<div class="history-filter">
<a class="<?= $type===''?'active':'' ?>" href="pontos.php">Todos</a>
<?php foreach($allowed as $t): ?>
<a class="<?= $type===$t?'active':'' ?>" href="?type=<?=urlencode($t)?>"><?=esc(typeLabel($t))?></a>
<?php endforeach; ?>
</div>

<section class="panel">
<div class="section-title">
<div>
<p class="eyebrow">EXTRATO</p>
<h2>Suas movimentações</h2>
</div>
<span class="muted">Últimas 300</span>
</div>

<?php if(!$rows): ?>
<div class="empty">Nenhuma movimentação encontrada.</div>
<?php else: ?>
<div class="card-grid-modern">
<?php foreach($rows as $r): $positive=(int)$r['points']>=0; ?>
<article class="point-card">
  <div class="card-topline">
    <span class="card-status default"><?=esc(typeLabel($r['type']))?></span>
    <span class="card-points <?=$positive?'':'negative'?>"><?=((int)$r['points']>0?'+':'')?><?=number_format((int)$r['points'],0,',','.')?> pts</span>
  </div>
  <strong class="card-title"><?=esc($r['description'])?></strong>
  <div class="card-meta">
    <span>📅 <?=date('d/m/Y',strtotime($r['created_at']))?></span>
    <span>🕒 <?=date('H:i',strtotime($r['created_at']))?></span>
  </div>
</article>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

<div class="dashboard-tip" style="margin-top:18px">
<span>ℹ️</span>
<div>
<strong>Transparência</strong>
<p>Os pontos só aparecem no seu histórico quando uma movimentação é registrada no sistema. Conteúdos enviados ainda em análise não geram pontos.</p>
</div>
</div>

<div style="margin-top:18px">
<a class="btn" href="index.php">← Voltar para meu painel</a>
</div>
</main>

<footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
</body>
</html>
