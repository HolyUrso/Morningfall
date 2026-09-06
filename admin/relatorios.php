<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$month=trim((string)($_GET['month']??date('Y-m')));
if(!preg_match('/^\d{4}-\d{2}$/',$month)) $month=date('Y-m');
$start=$month.'-01';
$next=date('Y-m-d',strtotime($start.' +1 month'));
$end=date('Y-m-d',strtotime($next.' -1 day'));
$export=($_GET['export']??'')==='csv';

$category=trim((string)($_GET['category']??''));
$cats=['novato'=>'Novato','oficial'=>'Oficial','afiliado'=>'Afiliado'];

$where='WHERE s.active=1'; $params=[];
if(array_key_exists($category,$cats)){ $where.=' AND s.category=?'; $params[]=$category; }

$sql="SELECT
 s.id,s.name,s.category,s.points,
 COALESCE((SELECT COUNT(DISTINCT v.vod_date) FROM vods v WHERE v.streamer_id=s.id AND v.vod_date BETWEEN ? AND ?),0) AS live_days,
 COALESCE((SELECT SUM(v.duration_minutes) FROM vods v WHERE v.streamer_id=s.id AND v.vod_date BETWEEN ? AND ?),0) AS live_minutes,
 COALESCE((SELECT COUNT(*) FROM vods v WHERE v.streamer_id=s.id AND v.vod_date BETWEEN ? AND ?),0) AS vod_count,
 COALESCE((SELECT COUNT(*) FROM content_submissions c WHERE c.streamer_id=s.id AND c.status='approved' AND c.submission_date BETWEEN ? AND ?),0) AS approved_contents,
 COALESCE((SELECT COUNT(*) FROM content_submissions c WHERE c.streamer_id=s.id AND c.status='approved' AND c.collab=1 AND c.submission_date BETWEEN ? AND ?),0) AS collabs,
 COALESCE((SELECT SUM(c.points_awarded) FROM content_submissions c WHERE c.streamer_id=s.id AND c.status='approved' AND c.submission_date BETWEEN ? AND ?),0) AS content_points,
 COALESCE((SELECT SUM(pt.points) FROM point_transactions pt WHERE pt.streamer_id=s.id AND pt.points>0 AND pt.created_at>=? AND pt.created_at<?),0) AS earned_points,
 COALESCE((SELECT COUNT(*) FROM redemptions r WHERE r.streamer_id=s.id AND r.status='approved' AND r.created_at>=? AND r.created_at<?),0) AS redemption_count,
 COALESCE((SELECT SUM(r.points_spent) FROM redemptions r WHERE r.streamer_id=s.id AND r.status='approved' AND r.created_at>=? AND r.created_at<?),0) AS redeemed_points
 FROM streamers s $where
 ORDER BY earned_points DESC, live_days DESC, s.name ASC";

$params2=[
 $start,$end,$start,$end,$start,$end,
 $start,$end,$start,$end,$start,$end,
 $start.' 00:00:00',$next.' 00:00:00',
 $start.' 00:00:00',$next.' 00:00:00',
 $start.' 00:00:00',$next.' 00:00:00'
];
$params2=array_merge($params2,$params);
$q=$pdo->prepare($sql);$q->execute($params2);$rows=$q->fetchAll();

$tot=[
 'streamers'=>count($rows),'live_days'=>0,'live_minutes'=>0,'vod_count'=>0,
 'approved_contents'=>0,'collabs'=>0,'earned_points'=>0,'redemption_count'=>0,'redeemed_points'=>0
];
foreach($rows as &$r){
    $r['eligible']=(int)$r['live_days']>=20;
    foreach(array_keys($tot) as $k) if($k!=='streamers') $tot[$k]+=(int)$r[$k];
}
unset($r);
$eligibleCount=count(array_filter($rows,fn($r)=>$r['eligible']));
$tot['eligible']=$eligibleCount;

function esc($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function hm($minutes){$m=(int)$minutes;return sprintf('%02d:%02d',intdiv($m,60),$m%60);}
function csvCell($v){return '"'.str_replace('"','""',(string)$v).'"';}

if($export){
    $filename='relatorio_morningfall_'.$month.'.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    echo "\xEF\xBB\xBF";
    echo "Streamer;Categoria;Dias de live;Horas;VODs;Conteudos aprovados;Collab/Raid;Pontos conquistados;Pontos atuais;Resgates aprovados;Pontos resgatados;Elegivel sorteio\n";
    foreach($rows as $r){
        echo implode(';',[
            csvCell($r['name']),csvCell($cats[$r['category']]??$r['category']),
            csvCell($r['live_days']),csvCell(hm($r['live_minutes'])),csvCell($r['vod_count']),
            csvCell($r['approved_contents']),csvCell($r['collabs']),csvCell($r['earned_points']),
            csvCell($r['points']),csvCell($r['redemption_count']),csvCell($r['redeemed_points']),
            csvCell($r['eligible']?'SIM':'NAO')
        ])."\n";
    }
    exit;
}

$monthLabel=date('m/Y',strtotime($start));
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Relatórios · Morningfall Creators</title><link rel="stylesheet" href="../assets/css/style.css">
<style>
.report-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin:20px 0}
.report-stat{background:rgba(17,24,39,.92);border:1px solid #29344a;border-radius:15px;padding:18px}.report-stat small{display:block;color:#91a0b8;margin-bottom:7px}.report-stat strong{font-size:27px}
.filters{display:flex;gap:10px;align-items:end;flex-wrap:wrap;background:rgba(17,24,39,.75);border:1px solid #29344a;border-radius:15px;padding:15px;margin:18px 0}.filters>div{min-width:180px}
.filters .actions{display:flex;gap:8px}
.eligible{color:#61e6a5;font-weight:700}.not-eligible{color:#91a0b8}
.report-note{margin-top:16px;padding:15px;border:1px solid #29344a;border-radius:13px;color:#b7c3d8}
@media(max-width:1000px){.report-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:600px){.report-grid{grid-template-columns:1fr}}
</style></head>
<body><header class="topbar"><div><b>MORNINGFALL</b> <span>CREATORS</span></div><div><?=esc($_SESSION['staff_name']??'Staff')?> · <a href="../logout.php">Sair</a></div></header>
<main class="container"><a href="index.php">← Dashboard</a>
<div class="page-head"><div><p class="eyebrow">CONTROLE DA STAFF</p><h1>📊 Relatórios</h1><p class="muted">Resumo mensal de desempenho, pontos, resgates e elegibilidade para o sorteio.</p></div></div>
<form class="filters" method="get">
<div><label>Mês</label><input type="month" name="month" value="<?=esc($month)?>"></div>
<div><label>Categoria</label><select name="category"><option value="">Todas</option><?php foreach($cats as $k=>$v):?><option value="<?=esc($k)?>" <?=$category===$k?'selected':''?>><?=esc($v)?></option><?php endforeach;?></select></div>
<div class="actions"><button class="btn primary" type="submit">🔎 Atualizar</button><a class="btn" href="?month=<?=esc($month)?>&category=<?=urlencode($category)?>&export=csv">📥 CSV</a><a class="btn" href="relatorio_pdf.php?month=<?=esc($month)?>&category=<?=urlencode($category)?>" target="_blank">📄 PDF</a></div>
</form>
<div class="report-grid">
<div class="report-stat"><small>STREAMERS ATIVOS</small><strong><?=number_format($tot['streamers'],0,',','.')?></strong></div>
<div class="report-stat"><small>DIAS DE LIVE</small><strong><?=number_format($tot['live_days'],0,',','.')?></strong></div>
<div class="report-stat"><small>VODs</small><strong><?=number_format($tot['vod_count'],0,',','.')?></strong></div>
<div class="report-stat"><small>PONTOS CONQUISTADOS</small><strong><?=number_format($tot['earned_points'],0,',','.')?></strong></div>
<div class="report-stat"><small>CONTEÚDOS APROVADOS</small><strong><?=number_format($tot['approved_contents'],0,',','.')?></strong></div>
<div class="report-stat"><small>COLLAB / RAID</small><strong><?=number_format($tot['collabs'],0,',','.')?></strong></div>
<div class="report-stat"><small>RESGATES APROVADOS</small><strong><?=number_format($tot['redemption_count'],0,',','.')?></strong></div>
<div class="report-stat"><small>ELEGÍVEIS AO SORTEIO</small><strong class="eligible"><?=number_format($tot['eligible'],0,',','.')?></strong></div>
</div>
<section class="panel"><div class="section-title"><div><p class="eyebrow"><?=esc($monthLabel)?></p><h2>Desempenho por streamer</h2></div><span class="muted"><?=count($rows)?> streamers</span></div>
<div class="table-wrap"><table><thead><tr><th>Streamer</th><th>Categoria</th><th>Lives</th><th>Horas</th><th>VODs</th><th>Conteúdos</th><th>Pontos</th><th>Saldo</th><th>Resgates</th><th>Sorteio</th></tr></thead><tbody>
<?php if(!$rows):?><tr><td colspan="10"><div class="empty">Nenhum streamer encontrado.</div></td></tr>
<?php else:foreach($rows as $r):?><tr>
<td><strong><?=esc($r['name'])?></strong></td>
<td><?=esc($cats[$r['category']]??$r['category'])?></td>
<td><?=number_format((int)$r['live_days'],0,',','.')?> dias</td>
<td><?=hm($r['live_minutes'])?></td>
<td><?=number_format((int)$r['vod_count'],0,',','.')?></td>
<td><?=number_format((int)$r['approved_contents'],0,',','.')?></td>
<td><strong class="points-pill">+<?=number_format((int)$r['earned_points'],0,',','.')?></strong></td>
<td><?=number_format((int)$r['points'],0,',','.')?> pts</td>
<td><?=number_format((int)$r['redemption_count'],0,',','.')?></td>
<td><?= $r['eligible']?'<span class="eligible">✓ Elegível</span>':'<span class="not-eligible">'.number_format((int)$r['live_days'],0,',','.') . '/20</span>'?></td>
</tr><?php endforeach;endif;?>
</tbody></table></div></section>
<section class="panel" style="margin-top:18px"><div class="section-title"><div><p class="eyebrow">SORTEIO</p><h2>Elegíveis de <?=esc($monthLabel)?></h2></div></div>
<div class="table-wrap"><table><thead><tr><th>#</th><th>Streamer</th><th>Dias distintos de live</th><th>Pontos no mês</th><th>Status</th></tr></thead><tbody>
<?php $n=0; foreach($rows as $r): if(!$r['eligible']) continue; $n++;?><tr><td><?=$n?></td><td><strong><?=esc($r['name'])?></strong></td><td><?=$r['live_days']?>/20</td><td>+<?=number_format((int)$r['earned_points'],0,',','.')?></td><td class="eligible">✓ Elegível</td></tr><?php endforeach;?>
<?php if($n===0):?><tr><td colspan="5"><div class="empty">Nenhum streamer atingiu 20 dias distintos de live neste mês.</div></td></tr><?php endif;?>
</tbody></table></div></section>
<div class="report-note">💡 <strong>Regra do sorteio:</strong> a elegibilidade usa <strong>20 dias distintos de live</strong> no mês, calculados pela data das VODs. Resgates não diminuem os pontos conquistados no relatório. O CSV exporta os mesmos dados filtrados.</div>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>
