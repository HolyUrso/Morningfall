<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/vod_points.php';
require_once '../config/streamer_webhook.php';

$id=(int)($_GET['id']??0);
$st=$pdo->prepare("SELECT * FROM streamers WHERE id=?");
$st->execute([$id]);
$s=$st->fetch();
if(!$s) exit('Streamer não encontrado.');

$v=$pdo->prepare("SELECT * FROM vods WHERE streamer_id=? ORDER BY vod_date DESC,created_at DESC");
$v->execute([$id]); $vods=$v->fetchAll();

$pt=$pdo->prepare("SELECT * FROM point_transactions WHERE streamer_id=? ORDER BY created_at DESC LIMIT 50");
$pt->execute([$id]); $points=$pt->fetchAll();

$totalMinutes=(int)$pdo->prepare("SELECT COALESCE(SUM(duration_minutes),0) FROM vods WHERE streamer_id=?");
$q=$pdo->prepare("SELECT COALESCE(SUM(duration_minutes),0) FROM vods WHERE streamer_id=?"); $q->execute([$id]); $totalMinutes=(int)$q->fetchColumn();
function formatDurationHM(int $minutes): string { return sprintf('%02d:%02d', intdiv($minutes,60), $minutes%60); }
?>
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=htmlspecialchars($s['name'])?></title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body><main class="container">
<a href="streamers.php">← Streamers</a>
<?php if(isset($_GET['created'])): ?><div class="alert success">Streamer cadastrado com sucesso.</div><?php endif; ?>
<?php if(isset($_GET['updated'])): ?><div class="alert success">Dados do streamer atualizados.</div><?php endif; ?>
<?php if(($_GET['webhook_test'] ?? '')==='ok'): ?>
<div class="alert success">Teste enviado para a webhook do streamer com sucesso.</div>
<?php elseif(($_GET['webhook_test'] ?? '')==='fail'): ?>
<div class="alert danger">
<strong>Não foi possível enviar o teste.</strong>
<?php if(!empty($_GET['msg'])): ?><br><?=htmlspecialchars($_GET['msg'])?><?php endif; ?>
<?php if(isset($_GET['code']) && (int)$_GET['code'] > 0): ?><br>HTTP: <?=htmlspecialchars($_GET['code'])?><?php elseif(isset($_GET['code']) && (int)$_GET['code'] === 0): ?><br>HTTP: 0 — o servidor não recebeu uma resposta do Discord.<?php endif; ?>
<?php if(!empty($_GET['err'])): ?><br>cURL: <?=htmlspecialchars($_GET['err'])?><?php endif; ?>
</div>
<?php endif; ?>

<div class="profile-head">
<div><p class="eyebrow">PERFIL</p><h1><?=htmlspecialchars($s['name'])?></h1><span class="badge <?=$s['category']?>"><?=ucfirst($s['category'])?></span> <span class="status-dot <?=$s['active']?'status-active':'status-inactive'?>"><?=$s['active']?'Ativo':'Inativo'?></span></div>
<div class="big-points"><?=number_format($s['points'],0,',','.')?><small>pontos</small></div>
</div>

<div class="actions profile-actions">
<a class="btn primary" href="streamer_editar.php?id=<?=$id?>">Editar streamer</a>
<a class="btn <?=$s['active']?'danger-btn':'success-btn'?>" href="../api/toggle_streamer.php?id=<?=$id?>" onclick="return confirm('<?=$s['active']?'Inativar':'Ativar'?> este streamer?')"><?=$s['active']?'Inativar streamer':'Ativar streamer'?></a>
<a class="btn secondary" href="vod_nova.php?streamer_id=<?=$id?>">+ Cadastrar VOD</a>
</div>

<div class="stats">
<div class="stat"><small>VODS</small><strong><?=count($vods)?></strong></div>
<div class="stat"><small>TEMPO TOTAL</small><strong><?=formatDurationHM($totalMinutes)?></strong></div>
<div class="stat"><small>STATUS</small><strong><?=$s['active']?'Ativo':'Inativo'?></strong></div>
</div>

<div class="profile-grid">
<div class="panel"><h3>Dados</h3>
<p><strong>Discord:</strong> <?=htmlspecialchars($s['discord'] ?: 'Não informado')?></p>
<p><strong>Plataforma:</strong> <?=htmlspecialchars($s['platform'] ?: 'Não informada')?></p>
<p><strong>Canal:</strong> <?php if($s['channel_url']): ?><a href="<?=htmlspecialchars($s['channel_url'])?>" target="_blank">Abrir canal</a><?php else: ?>Não informado<?php endif; ?></p>
<p><strong>Entrada:</strong> <?=htmlspecialchars($s['joined_at'] ?: 'Não informada')?></p>
<p><strong>Código de acesso:</strong> <b><?=htmlspecialchars($s['access_code'])?></b></p>
<p><strong>Webhook:</strong> <?=!empty($s['webhook_url'])?'🟢 Configurada':'⚪ Não configurada'?></p>
<?php if(!empty($s['webhook_url'])): ?><a class="btn small-btn secondary" href="../api/test_streamer_webhook.php?streamer_id=<?=$id?>" onclick="return confirm('Enviar um teste para a webhook deste streamer?')">🔔 Testar webhook</a><?php endif; ?>
</div>
<div class="panel"><h3>Observações</h3><p><?=nl2br(htmlspecialchars($s['notes'] ?: 'Nenhuma observação cadastrada.'))?></p></div>
</div>

<h2>VODs</h2>
<?php if(!$vods): ?><div class="empty">Nenhuma VOD cadastrada para este streamer.</div><?php else: ?>
<table><thead><tr><th>Data</th><th>Link</th><th>Duração</th><th>Pontos</th><th>Ação</th></tr></thead><tbody>
<?php foreach($vods as $x): ?><tr><td><?=$x['vod_date']?></td><td><a href="<?=htmlspecialchars($x['url'])?>" target="_blank">Abrir VOD</a></td><td><?=formatDurationHM((int)$x['duration_minutes'])?></td><td>+<?=$x['points_awarded']?></td><td><a class="btn danger-btn" href="../api/delete_vod.php?id=<?=$x['id']?>" onclick="return confirm('Excluir esta VOD? A pontuação do dia será recalculada automaticamente.')">Excluir</a></td></tr><?php endforeach;?>
</tbody></table>
<?php endif; ?>

<h2>Histórico de pontos</h2>
<?php if(!$points): ?><div class="empty">Nenhuma movimentação de pontos registrada.</div><?php else: ?>
<table><thead><tr><th>Data</th><th>Tipo</th><th>Descrição</th><th>Pontos</th></tr></thead><tbody>
<?php foreach($points as $x): ?><tr><td><?=$x['created_at']?></td><td><?=$x['type']?></td><td><?=htmlspecialchars($x['description'])?></td><td><?=$x['points']>0?'+':''?><?=$x['points']?></td></tr><?php endforeach;?>
</tbody></table>
<?php endif; ?>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>