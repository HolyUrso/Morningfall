<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$category=trim((string)($_GET['category']??''));
$period=trim((string)($_GET['period']??'month'));
$allowedCat=['novato','oficial','afiliado'];
$allowedPeriod=['month','week','all'];
if(!in_array($period,$allowedPeriod,true)) $period='month';

$where='WHERE s.active=1'; $params=[];
if(in_array($category,$allowedCat,true)){ $where.=' AND s.category=?'; $params[]=$category; }

$today=date('Y-m-d');
$monthStart=date('Y-m-01').' 00:00:00';
$weekStart=date('Y-m-d',strtotime('monday this week')).' 00:00:00';
$periodStart=$period==='month'?$monthStart:($period==='week'?$weekStart:'1970-01-01 00:00:00');

$sql="SELECT s.id,s.name,s.category,s.points,
 COALESCE((SELECT SUM(pt.points) FROM point_transactions pt WHERE pt.streamer_id=s.id AND pt.points>0 AND pt.created_at>=?),0) AS period_points,
 COALESCE((SELECT COUNT(*) FROM vods v WHERE v.streamer_id=s.id AND v.vod_date>=?),0) AS period_vods,
 COALESCE((SELECT COUNT(*) FROM content_submissions cs WHERE cs.streamer_id=s.id AND cs.status='approved' AND cs.submission_date>=?),0) AS period_submissions
 FROM streamers s $where
 ORDER BY period_points DESC, s.points DESC, s.name ASC";
$st=$pdo->prepare($sql);
$st->execute(array_merge([$periodStart,substr($periodStart,0,10),substr($periodStart,0,10)],$params));
$rows=$st->fetchAll();

$labels=['novato'=>'Novato','oficial'=>'Oficial','afiliado'=>'Afiliado'];
$title=$period==='month'?'Ranking do Mês':($period==='week'?'Ranking da Semana':'Ranking Geral');

$rank=0;$last=null;
foreach($rows as $i=>&$r){
    $score=(int)$r['period_points'];
    if($last===null || $score<$last) $rank=$i+1;
    $r['rank']=$rank;$last=$score;
}
unset($r);
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=htmlspecialchars($title)?> · Morningfall Creators</title><link rel="stylesheet" href="../assets/css/style.css">
<style>
.rank-hero{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin:20px 0}
.rank-card{background:rgba(17,24,39,.92);border:1px solid #29344a;border-radius:16px;padding:18px;text-align:center}
.rank-card.top1{transform:translateY(-5px)}
.rank-card .medal{font-size:32px}.rank-card strong{display:block;font-size:21px;margin:7px 0}.rank-card small{color:#91a0b8}
.rank-tabs{display:flex;gap:8px;flex-wrap:wrap;margin:18px 0}.rank-tabs a{padding:9px 13px;border:1px solid #303b52;border-radius:10px;color:#b9c4d7;text-decoration:none}.rank-tabs a.active{background:#2a2050;border-color:#7c5cff;color:#fff}
.rank-points{font-size:17px}.rank-number{font-size:20px}.rank-top{font-size:25px}
.me{outline:2px solid #7c5cff;background:rgba(124,92,255,.08)}
@media(max-width:750px){.rank-hero{grid-template-columns:1fr}}
</style></head>
<body><header class="topbar"><div><b>MORNINGFALL</b> <span>CREATORS</span></div><div><?=htmlspecialchars($_SESSION['staff_name']??'Staff')?> · <a href="../logout.php">Sair</a></div></header>
<main class="container"><a href="index.php">← Dashboard</a>
<div class="page-head"><div><p class="eyebrow">DESEMPENHO</p><h1>🏆 <?=htmlspecialchars($title)?></h1><p class="muted">O ranking do período considera pontos positivos conquistados no período selecionado. O saldo atual é mostrado separadamente.</p></div></div>
<div class="rank-tabs">
<?php foreach(['month'=>'Mês','week'=>'Semana','all'=>'Geral'] as $k=>$v):?><a class="<?=$period===$k?'active':''?>" href="?period=<?=$k?>&category=<?=urlencode($category)?>"><?=htmlspecialchars($v)?></a><?php endforeach;?>
</div>
<div class="ranking-filters"><a class="filter-chip <?=$category===''?'active':''?>" href="?period=<?=$period?>">Todos</a>
<?php foreach($allowedCat as $c):?><a class="filter-chip <?=$category===$c?'active':''?>" href="?period=<?=$period?>&category=<?=$c?>"><?=htmlspecialchars($labels[$c])?></a><?php endforeach;?>
</div>
<?php if(count($rows)>=1):?><div class="rank-hero">
<?php foreach(array_slice($rows,0,3) as $r):?><div class="rank-card <?=$r['rank']===1?'top1':''?>"><div class="medal"><?=['🥇','🥈','🥉'][$r['rank']-1]??'🏆'?></div><strong><?=htmlspecialchars($r['name'])?></strong><small><?=number_format((int)$r['period_points'],0,',','.')?> pts no período</small><div class="muted" style="margin-top:7px"><?=number_format((int)$r['points'],0,',','.')?> pts atuais</div></div><?php endforeach;?>
</div><?php endif;?>
<?php if(!$rows):?><div class="empty">Nenhum streamer encontrado.</div><?php else:?><div class="panel ranking-panel"><div class="table-wrap"><table class="ranking-table"><thead><tr><th>#</th><th>Streamer</th><th>Categoria</th><th>Pontos no período</th><th>Pontos atuais</th><th>VODs</th><th>Conteúdos</th></tr></thead><tbody>
<?php foreach($rows as $r):?><tr><td><span class="rank-number <?=$r['rank']<=3?'rank-top':''?>"><?=$r['rank']<=3?(['🥇','🥈','🥉'][$r['rank']-1]??$r['rank']):$r['rank']?></span></td><td><strong><?=htmlspecialchars($r['name'])?></strong></td><td><span class="badge <?=htmlspecialchars($r['category'])?>"><?=htmlspecialchars($labels[$r['category']]??$r['category'])?></span></td><td><strong class="rank-points">+<?=number_format((int)$r['period_points'],0,',','.')?></strong></td><td><?=number_format((int)$r['points'],0,',','.')?> pts</td><td><?=number_format((int)$r['period_vods'],0,',','.')?></td><td><?=number_format((int)$r['period_submissions'],0,',','.')?></td></tr><?php endforeach;?>
</tbody></table></div></div><?php endif;?>
<div class="ranking-note">💡 <strong>Como funciona:</strong> Mês e Semana usam pontos positivos registrados em <code>point_transactions</code> dentro do período. O Ranking Geral usa todo o histórico. Resgates não reduzem o desempenho histórico do período.</div>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>