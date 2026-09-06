<?php
require_once '../config/streamer_auth.php';
require_once '../config/database.php';
$id=(int)$_SESSION['streamer_id'];
$st=$pdo->prepare("SELECT id,name,category,points FROM streamers WHERE id=? AND active=1");$st->execute([$id]);$me=$st->fetch();
if(!$me){session_destroy();header('Location: ../streamer-login.php');exit;}
$period=$_GET['period']??'month'; if(!in_array($period,['month','week','all'],true))$period='month';
$start=$period==='month'?date('Y-m-01 00:00:00'):($period==='week'?date('Y-m-d 00:00:00',strtotime('monday this week')):'1970-01-01 00:00:00');
$st=$pdo->prepare("SELECT s.id,s.name,s.category,s.points,COALESCE((SELECT SUM(pt.points) FROM point_transactions pt WHERE pt.streamer_id=s.id AND pt.points>0 AND pt.created_at>=?),0) period_points FROM streamers s WHERE s.active=1 ORDER BY period_points DESC,s.points DESC,s.name ASC");
$st->execute([$start]);$rows=$st->fetchAll();$rank=0;$last=null;$myRank=null;
foreach($rows as $i=>&$r){$score=(int)$r['period_points'];if($last===null||$score<$last)$rank=$i+1;$r['rank']=$rank;$last=$score;if((int)$r['id']===$id)$myRank=$rank;}unset($r);
$title=$period==='month'?'Ranking do Mês':($period==='week'?'Ranking da Semana':'Ranking Geral');
function esc($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=esc($title)?></title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="streamer-area"><header class="topbar"><div><b>MORNINGFALL</b> <span>CREATORS</span></div><div><?=esc($me['name'])?> · <a href="../streamer-logout.php">Sair</a></div></header>
<main class="container"><a href="index.php">← Meu painel</a><div class="page-head"><div><p class="eyebrow">DESEMPENHO</p><h1>🏆 <?=esc($title)?></h1><p class="muted">Sua posição é baseada nos pontos conquistados no período.</p></div></div>
<div class="rank-tabs" style="display:flex;gap:8px;flex-wrap:wrap;margin:18px 0"><a class="filter-chip <?=$period==='month'?'active':''?>" href="?period=month">Mês</a><a class="filter-chip <?=$period==='week'?'active':''?>" href="?period=week">Semana</a><a class="filter-chip <?=$period==='all'?'active':''?>" href="?period=all">Geral</a></div>
<div class="stats dashboard-stats"><div class="stat stat-highlight"><small>SUA POSIÇÃO</small><strong>#<?=esc($myRank??'-')?></strong><span class="stat-note">de <?=count($rows)?> streamers</span></div><div class="stat"><small>PONTOS NO PERÍODO</small><strong>+<?=number_format((int)$rows[array_search($id,array_column($rows,'id'))]['period_points']??0,0,',','.')?></strong></div><div class="stat"><small>PONTOS ATUAIS</small><strong><?=number_format((int)$me['points'],0,',','.')?></strong></div></div>
<div class="panel"><div class="section-title"><div><p class="eyebrow">CLASSIFICAÇÃO</p><h2>Ranking</h2></div></div><div class="table-wrap"><table><thead><tr><th>#</th><th>Streamer</th><th>Categoria</th><th>Pontos no período</th><th>Pontos atuais</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr class="<?=((int)$r['id']===$id?'me':'')?>"><td><?=($r['rank']<=3?(['🥇','🥈','🥉'][$r['rank']-1]??$r['rank']):$r['rank'])?></td><td><strong><?=esc($r['name'])?></strong><?=((int)$r['id']===$id?' <span class="badge">VOCÊ</span>':'')?></td><td><?=esc(ucfirst($r['category']))?></td><td>+<?=number_format((int)$r['period_points'],0,',','.')?></td><td><?=number_format((int)$r['points'],0,',','.')?> pts</td></tr><?php endforeach;?>
</tbody></table></div></div></main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>