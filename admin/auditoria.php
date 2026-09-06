<?php
require_once '../config/auth.php';
require_once '../config/database.php';
if (($_SESSION['staff_role'] ?? '') !== 'master') { http_response_code(403); exit('Acesso restrito ao Staff Master.'); }

$search=trim((string)($_GET['q']??''));
$category=trim((string)($_GET['category']??''));
$action=trim((string)($_GET['action']??''));
$staffId=(int)($_GET['staff_id']??0);
$userId=(int)($_GET['user_id']??0);
$start=trim((string)($_GET['start']??''));
$end=trim((string)($_GET['end']??''));
$where=[];$params=[];
if($search!==''){ $where[]='(a.affected_user_name LIKE ? OR a.staff_name LIKE ? OR a.details LIKE ? OR a.action LIKE ?)'; $term='%'.$search.'%'; array_push($params,$term,$term,$term,$term); }
if($category!==''){ $where[]='a.category=?'; $params[]=$category; }
if($action!==''){ $where[]='a.action=?'; $params[]=$action; }
if($staffId>0){ $where[]='a.staff_id=?'; $params[]=$staffId; }
if($userId>0){ $where[]='a.affected_user_id=?'; $params[]=$userId; }
if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$start)){ $where[]='a.created_at>=?'; $params[]=$start.' 00:00:00'; }
if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$end)){ $where[]='a.created_at<=?'; $params[]=$end.' 23:59:59'; }
$whereSql=$where?'WHERE '.implode(' AND ',$where):'';
$q=$pdo->prepare("SELECT a.* FROM audit_logs a $whereSql ORDER BY a.created_at DESC,a.id DESC LIMIT 500");$q->execute($params);$logs=$q->fetchAll();
$staffRows=$pdo->query("SELECT id,name FROM staff_users ORDER BY name")->fetchAll();
$users=$pdo->query("SELECT id,name FROM streamers ORDER BY name")->fetchAll();
$actions=$pdo->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
$categories=$pdo->query("SELECT DISTINCT category FROM audit_logs ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
function ae($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function auditJson($v){ if(!$v) return '—'; $d=json_decode($v,true); if(is_array($d)) return json_encode($d,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT); return $v; }
$paramsGet=$_GET; unset($paramsGet['page']); $qs=http_build_query($paramsGet);
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Logs de Alterações · Morningfall Creators</title><link rel="stylesheet" href="../assets/css/style.css"><style>
.audit-toolbar{display:grid;grid-template-columns:2fr 1fr 1fr 1fr 1fr 1fr;gap:10px;align-items:end}.audit-toolbar label{font-size:12px}.audit-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px}.audit-table td{vertical-align:top}.audit-detail{max-width:360px;white-space:pre-wrap;font-size:11px;color:#9eacc4}.audit-pill{display:inline-block;padding:4px 8px;border-radius:999px;background:#1c2536;border:1px solid #34415a;font-size:11px}.audit-muted{color:#8391aa;font-size:12px}.audit-empty{padding:24px;text-align:center;color:#8794aa}.export-btn{background:#202b42!important}.master-note{margin-bottom:18px}.audit-table{font-size:12px}
@media(max-width:1000px){.audit-toolbar{grid-template-columns:1fr 1fr}.audit-table{min-width:1050px}}
</style></head><body><header class="topbar"><div><b>MORNINGFALL</b> <span>CREATORS</span></div><div><?=ae($_SESSION['staff_name']??'')?> · <a href="../logout.php">Sair</a></div></header>
<main class="container"><a href="index.php">← Dashboard</a><div class="page-head"><div><p class="eyebrow">ADMINISTRAÇÃO · MASTER</p><h1>🧾 Logs de Alterações</h1><p class="muted">Histórico das alterações realizadas pela Staff e pelo Master. Os registros não possuem opção de exclusão nesta tela.</p></div></div>
<div class="panel master-note"><strong>🔐 Auditoria</strong><p class="muted">Mostra quem alterou, qual usuário foi afetado, o que aconteceu e os valores antes/depois quando disponíveis.</p></div>
<section class="panel"><form method="get" class="audit-toolbar"><div><label>Buscar</label><input name="q" value="<?=ae($search)?>" placeholder="Usuário, Staff, ação ou detalhe"></div><div><label>Categoria</label><select name="category"><option value="">Todas</option><?php foreach($categories as $c):?><option value="<?=ae($c)?>" <?=$category===$c?'selected':''?>><?=ae($c)?></option><?php endforeach;?></select></div><div><label>Ação</label><select name="action"><option value="">Todas</option><?php foreach($actions as $a):?><option value="<?=ae($a)?>" <?=$action===$a?'selected':''?>><?=ae($a)?></option><?php endforeach;?></select></div><div><label>Staff</label><select name="staff_id"><option value="0">Todas</option><?php foreach($staffRows as $r):?><option value="<?=$r['id']?>" <?=$staffId===(int)$r['id']?'selected':''?>><?=ae($r['name'])?></option><?php endforeach;?></select></div><div><label>Usuário afetado</label><select name="user_id"><option value="0">Todos</option><?php foreach($users as $r):?><option value="<?=$r['id']?>" <?=$userId===(int)$r['id']?'selected':''?>><?=ae($r['name'])?></option><?php endforeach;?></select></div><div><label>De / Até</label><div style="display:flex;gap:6px"><input type="date" name="start" value="<?=ae($start)?>"><input type="date" name="end" value="<?=ae($end)?>"></div></div><div class="audit-actions"><button class="btn primary">🔎 Filtrar</button><a class="btn secondary" href="auditoria.php">Limpar</a><a class="btn export-btn" href="auditoria_pdf.php?<?=$qs?>" target="_blank">📄 PDF</a><a class="btn export-btn" href="auditoria_xls.php?<?=$qs?>">📊 XLS</a></div></form></section>
<section class="panel" style="margin-top:18px"><div class="section-title"><div><p class="eyebrow">HISTÓRICO</p><h2><?=count($logs)?> registros</h2></div><span class="audit-pill">🔒 MASTER</span></div><div class="table-wrap"><table class="audit-table"><thead><tr><th>Data/Hora</th><th>Staff</th><th>Usuário afetado</th><th>Ação</th><th>Categoria</th><th>Alteração</th><th>Antes</th><th>Depois</th></tr></thead><tbody><?php if(!$logs):?><tr><td colspan="8" class="audit-empty">Nenhum registro encontrado.</td></tr><?php else: foreach($logs as $r):?><tr><td><?=ae(date('d/m/Y H:i:s',strtotime($r['created_at'])))?></td><td><strong><?=ae($r['staff_name'])?></strong><div class="audit-muted">#<?=ae($r['staff_id']??'')?></div></td><td><?=ae($r['affected_user_name']??'—')?><div class="audit-muted"><?=!empty($r['affected_user_id'])?'ID '.$r['affected_user_id']:''?></div></td><td><strong><?=ae($r['action'])?></strong></td><td><span class="audit-pill"><?=ae($r['category'])?></span></td><td class="audit-detail"><?=ae($r['details']??'')?></td><td class="audit-detail"><?=ae(auditJson($r['before_data']))?></td><td class="audit-detail"><?=ae(auditJson($r['after_data']))?></td></tr><?php endforeach; endif;?></tbody></table></div></section>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>
