<?php
require_once '../config/streamer_auth.php';
require_once '../config/database.php';
require_once '../config/content_points.php';
$id=(int)$_SESSION['streamer_id'];
$st=$pdo->prepare("SELECT id,name,category,points FROM streamers WHERE id=? AND active=1");
$st->execute([$id]); $me=$st->fetch();
if(!$me){session_destroy();header('Location: ../streamer-login.php');exit;}
function esc($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$today=date('Y-m-d');
$stats=[];
$all=$pdo->query("SELECT id,name,category FROM streamers WHERE active=1 ORDER BY name ASC")->fetchAll();
foreach($all as $r){
    $stt=liveSequenceStats($pdo,(int)$r['id'],$today);
    $stats[]=['id'=>(int)$r['id'],'name'=>$r['name'],'category'=>$r['category'],'current'=>$stt['current'],'best'=>$stt['best']];
}
usort($stats,function($a,$b){
    return ($b['current']<=>$a['current']) ?: ($b['best']<=>$a['best']) ?: strcasecmp($a['name'],$b['name']);
});
$rank=0;$lastCurrent=null;$myRank=null;
foreach($stats as $i=>&$r){
    if($lastCurrent===null || $r['current']<$lastCurrent) $rank=$i+1;
    $r['rank']=$rank; $lastCurrent=$r['current'];
    if($r['id']===$id) $myRank=$rank;
}
unset($r);
$mine=liveSequenceStats($pdo,$id,$today);
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>🔥 Sequência de Lives</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="streamer-area"><header class="topbar"><div><b>MORNINGFALL</b> <span>CREATORS</span></div><div><?=esc($me['name'])?> · <a href="../streamer-logout.php">Sair</a></div></header>
<main class="container"><a href="index.php">← Meu painel</a>
<div class="page-head"><div><p class="eyebrow">DESEMPENHO</p><h1>🔥 Sequência de Lives</h1><p class="muted">Ranking baseado somente em Lives aprovadas com mais de 2 horas.</p></div></div>
<div class="stats dashboard-stats"><div class="stat stat-highlight"><small>SUA POSIÇÃO</small><strong>#<?=esc($myRank??'-')?></strong><span class="stat-note">de <?=count($stats)?> streamers</span></div><div class="stat"><small>SEQUÊNCIA ATUAL</small><strong><?=number_format($mine['current'])?></strong><span class="stat-note">Lives válidas</span></div><div class="stat"><small>MELHOR SEQUÊNCIA</small><strong><?=number_format($mine['best'])?></strong><span class="stat-note">recorde pessoal</span></div></div>
<div class="panel" style="margin-top:18px"><div class="section-title"><div><p class="eyebrow">CLASSIFICAÇÃO</p><h2>🔥 Ranking de Sequência de Lives</h2><p class="muted">Uma Live válida é aquela com duração superior a 2 horas. No máximo uma Live válida por dia conta para a sequência.</p></div></div>
<div class="table-wrap"><table><thead><tr><th>#</th><th>Streamer</th><th>Categoria</th><th>Sequência atual</th><th>Melhor sequência</th></tr></thead><tbody>
<?php foreach($stats as $r):?><tr class="<?=($r['id']===$id?'me':'')?>"><td><?=($r['rank']<=3?(['🥇','🥈','🥉'][$r['rank']-1]??$r['rank']):$r['rank'])?></td><td><strong><?=esc($r['name'])?></strong><?=($r['id']===$id?' <span class="badge">VOCÊ</span>':'')?></td><td><?=esc(ucfirst($r['category']))?></td><td>🔥 <?=number_format($r['current'])?> Lives</td><td><?=number_format($r['best'])?> Lives</td></tr><?php endforeach;?>
</tbody></table></div></div>
<div class="dashboard-tip" style="margin-top:18px"><span>💡</span><div><strong>Como funciona?</strong><p>Somente Lives aprovadas com <b>mais de 2 horas</b> entram no ranking. Lives de 2 horas ou menos não contam. Não há pontos ou bônus por essa mecânica. O objetivo é premiar a regularidade e a consistência do streamer.</p></div></div>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>
