<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$month=trim((string)($_GET['month']??date('Y-m')));
if(!preg_match('/^\d{4}-\d{2}$/',$month)) $month=date('Y-m');
$start=$month.'-01'; $next=date('Y-m-d',strtotime($start.' +1 month')); $end=date('Y-m-d',strtotime($next.' -1 day'));
$category=trim((string)($_GET['category']??''));
$cats=['novato'=>'Novato','oficial'=>'Oficial','afiliado'=>'Afiliado'];
$where='WHERE s.active=1';$params=[];
if(array_key_exists($category,$cats)){$where.=' AND s.category=?';$params[]=$category;}

$sql="SELECT s.id,s.name,s.category,s.points,
COALESCE((SELECT COUNT(DISTINCT v.vod_date) FROM vods v WHERE v.streamer_id=s.id AND v.vod_date BETWEEN ? AND ?),0) live_days,
COALESCE((SELECT SUM(v.duration_minutes) FROM vods v WHERE v.streamer_id=s.id AND v.vod_date BETWEEN ? AND ?),0) live_minutes,
COALESCE((SELECT COUNT(*) FROM vods v WHERE v.streamer_id=s.id AND v.vod_date BETWEEN ? AND ?),0) vod_count,
COALESCE((SELECT COUNT(*) FROM content_submissions c WHERE c.streamer_id=s.id AND c.status='approved' AND c.submission_date BETWEEN ? AND ?),0) approved_contents,
COALESCE((SELECT COUNT(*) FROM content_submissions c WHERE c.streamer_id=s.id AND c.status='approved' AND c.collab=1 AND c.submission_date BETWEEN ? AND ?),0) collabs,
COALESCE((SELECT SUM(pt.points) FROM point_transactions pt WHERE pt.streamer_id=s.id AND pt.points>0 AND pt.created_at>=? AND pt.created_at<?),0) earned_points,
COALESCE((SELECT COUNT(*) FROM redemptions r WHERE r.streamer_id=s.id AND r.status='approved' AND r.created_at>=? AND r.created_at<?),0) redemption_count
FROM streamers s $where ORDER BY earned_points DESC,live_days DESC,s.name ASC";
$p=[
$start,$end,$start,$end,$start,$end,$start,$end,$start,$end,
$start.' 00:00:00',$next.' 00:00:00',$start.' 00:00:00',$next.' 00:00:00'
];
$q=$pdo->prepare($sql);$q->execute(array_merge($p,$params));$rows=$q->fetchAll();

function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function hm2($m){$m=(int)$m;return sprintf('%02d:%02d',intdiv($m,60),$m%60);}
$eligible=0;$earned=0;$lives=0;$vods=0;
foreach($rows as $r){if((int)$r['live_days']>=20)$eligible++;$earned+=(int)$r['earned_points'];$lives+=(int)$r['live_days'];$vods+=(int)$r['vod_count'];}
$label=date('m/Y',strtotime($start));
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><title>Relatório Morningfall Creators - <?=e($label)?></title>
<style>
@page{size:A4 landscape;margin:12mm}*{box-sizing:border-box}body{font-family:Arial,Helvetica,sans-serif;color:#111;background:#fff;margin:0;font-size:11px}
.header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #111;padding-bottom:12px;margin-bottom:15px}
h1{font-size:22px;margin:0 0 4px}.muted{color:#555}.cards{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin-bottom:16px}.card{border:1px solid #bbb;border-radius:6px;padding:10px}.card small{display:block;color:#555}.card strong{display:block;font-size:18px;margin-top:4px}
table{width:100%;border-collapse:collapse}th,td{border:1px solid #bbb;padding:7px;text-align:left}th{background:#eee}td.num,th.num{text-align:center}.ok{font-weight:bold}.footer{margin-top:15px;color:#666;font-size:9px}
.actions{position:fixed;right:15px;top:15px}button{padding:9px 14px;border:1px solid #777;border-radius:6px;background:#111;color:#fff;cursor:pointer}
@media print{.actions{display:none}}
</style></head><body>
<div class="actions"><button onclick="window.print()">🖨️ Salvar como PDF</button></div>
<div class="header"><div><h1>Morningfall Creators</h1><div>Relatório mensal de desempenho — <?=e($label)?></div></div><div class="muted">Gerado em <?=date('d/m/Y H:i')?></div></div>
<div class="cards">
<div class="card"><small>Streamers ativos</small><strong><?=count($rows)?></strong></div>
<div class="card"><small>Dias de live</small><strong><?=$lives?></strong></div>
<div class="card"><small>VODs</small><strong><?=$vods?></strong></div>
<div class="card"><small>Pontos conquistados</small><strong>+<?=$earned?></strong></div>
</div>
<table><thead><tr><th>Streamer</th><th>Categoria</th><th class="num">Dias</th><th class="num">Horas</th><th class="num">VODs</th><th class="num">Conteúdos</th><th class="num">Pontos</th><th class="num">Saldo</th><th class="num">Resgates</th><th class="num">Sorteio</th></tr></thead><tbody>
<?php foreach($rows as $r):$is=(int)$r['live_days']>=20;?>
<tr><td><strong><?=e($r['name'])?></strong></td><td><?=e($cats[$r['category']]??$r['category'])?></td>
<td class="num"><?=$r['live_days']?></td><td class="num"><?=hm2($r['live_minutes'])?></td><td class="num"><?=$r['vod_count']?></td>
<td class="num"><?=$r['approved_contents']?></td><td class="num">+<?=$r['earned_points']?></td><td class="num"><?=$r['points']?> pts</td>
<td class="num"><?=$r['redemption_count']?></td><td class="num <?=$is?'ok':''?>"><?=$is?'✓ Elegível':$r['live_days'].'/20'?></td></tr>
<?php endforeach;?></tbody></table>
<p class="footer">Elegibilidade do sorteio: 20 dias distintos de live no mês. Este relatório considera os dados registrados no sistema no momento da geração.</p>
<script>window.addEventListener('load',()=>setTimeout(()=>window.print(),350));</script>
<footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>
