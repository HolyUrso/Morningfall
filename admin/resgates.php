<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$status=trim((string)($_GET['status']??'pending'));
$allowed=['pending','approved','rejected','cancelled','all'];
if(!in_array($status,$allowed,true))$status='pending';
$where=$status==='all'?'':'WHERE r.status=?';$params=$status==='all'?[]:[$status];

$q=$pdo->prepare("SELECT r.*,s.name streamer_name,s.category,p.name prize_name,p.description prize_description,st.name staff_name
                  FROM redemptions r
                  JOIN streamers s ON s.id=r.streamer_id
                  JOIN prizes p ON p.id=r.prize_id
                  LEFT JOIN staff_users st ON st.id=r.reviewed_by
                  $where ORDER BY r.created_at DESC,r.id DESC");
$q->execute($params);$rows=$q->fetchAll();

function esc($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$labels=['pending'=>'Pendente','approved'=>'Aprovado','rejected'=>'Recusado','cancelled'=>'Cancelado'];
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Resgates · Morningfall Creators</title><link rel="stylesheet" href="../assets/css/style.css">
<style>.tabs{display:flex;gap:8px;flex-wrap:wrap;margin:18px 0}.tabs a{padding:9px 13px;border:1px solid #303b52;border-radius:10px;color:#b9c4d7;text-decoration:none}.tabs a.active{background:#2a2050;border-color:#7c5cff;color:#fff}.actions{display:flex;gap:7px;align-items:center}.actions form{margin:0}.danger{background:#5b2630!important;border-color:#8a3a48!important}</style></head>
<body><header class="topbar"><div><b>MORNINGFALL</b> <span>CREATORS</span></div><div><?=esc($_SESSION['staff_name']??'Staff')?> · <a href="../logout.php">Sair</a></div></header>
<main class="container"><a href="index.php">← Dashboard</a><div class="page-head"><div><p class="eyebrow">RECOMPENSAS</p><h1>🎁 Resgates</h1><p class="muted">A solicitação não desconta pontos. O desconto acontece somente quando a Staff aprovar.</p></div></div>
<?php if(isset($_GET['msg'])):?><div class="alert success"><?=esc($_GET['msg'])?></div><?php endif;?>
<?php if(isset($_GET['error'])):?><div class="alert error"><?=esc($_GET['error'])?></div><?php endif;?>
<div class="tabs"><?php foreach($allowed as $k):?><a class="<?=$status===$k?'active':''?>" href="?status=<?=$k?>"><?=esc($k==='all'?'Todos':$labels[$k])?></a><?php endforeach;?></div>
<section class="panel"><div class="table-wrap"><table><thead><tr><th>Data</th><th>Streamer</th><th>Recompensa</th><th>Custo</th><th>Status</th><th>Análise</th><th>Ações</th></tr></thead><tbody>
<?php if(!$rows):?><tr><td colspan="7"><div class="empty">Nenhum resgate encontrado.</div></td></tr><?php else:foreach($rows as $r):?>
<tr><td><?=date('d/m/Y H:i',strtotime($r['created_at']))?></td><td><strong><?=esc($r['streamer_name'])?></strong><br><span class="muted"><?=esc(ucfirst($r['category']))?></span></td><td><strong><?=esc($r['prize_name'])?></strong><?php if($r['prize_description']):?><br><span class="muted"><?=esc($r['prize_description'])?></span><?php endif;?></td><td><?=number_format((int)$r['points_spent'],0,',','.')?> pts</td><td><span class="badge <?=esc($r['status'])?>"><?=esc($labels[$r['status']]??$r['status'])?></span></td><td><?=esc($r['staff_name']??'—')?><?php if($r['staff_notes']):?><br><small><?=esc($r['staff_notes'])?></small><?php endif;?></td><td><?php if($r['status']==='pending'):?><div class="actions"><form method="post" action="resgate_acao.php" onsubmit="return confirm('Aprovar este resgate e descontar <?=number_format((int)$r['points_spent'],0,',','.')?> pontos?')"><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="action" value="approve"><button class="btn small primary">✓ Aprovar</button></form><form method="post" action="resgate_acao.php" onsubmit="return confirm('Recusar este resgate?')"><input type="hidden" name="id" value="<?=$r['id']?>"><input type="hidden" name="action" value="reject"><input type="hidden" name="note" value="Recusado pela Staff."><button class="btn small danger">✕ Recusar</button></form></div><?php else:?>—<?php endif;?></td></tr>
<?php endforeach;endif;?></tbody></table></div></section>
<div class="dashboard-tip" style="margin-top:18px"><span>🔐</span><div><strong>Regra financeira</strong><p>O saldo só muda no momento da aprovação. Cada desconto aprovado também cria uma movimentação negativa em <code>point_transactions</code>.</p></div></div>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>
