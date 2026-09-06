<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$search = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? 'all';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(s.name LIKE ? OR s.discord LIKE ? OR s.platform LIKE ?)";
    $term = "%{$search}%";
    $params[] = $term; $params[] = $term; $params[] = $term;
}
if (in_array($category, ['novato','oficial','afiliado'], true)) {
    $where[] = "s.category = ?";
    $params[] = $category;
}
if ($status === 'active') $where[] = "s.active = 1";
if ($status === 'inactive') $where[] = "s.active = 0";

$sql = "SELECT s.*,
        COALESCE((SELECT COUNT(*) FROM vods v WHERE v.streamer_id=s.id),0) AS vod_count,
        COALESCE((SELECT SUM(v.duration_minutes) FROM vods v WHERE v.streamer_id=s.id),0) AS total_minutes
        FROM streamers s";
if ($where) $sql .= " WHERE ".implode(" AND ", $where);
$sql .= " ORDER BY s.active DESC, s.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

function formatDurationHM(int $minutes): string {
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Streamers</title><link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="container">
<a href="index.php">← Dashboard</a>
<div class="page-head">
  <div><p class="eyebrow">GESTÃO</p><h1>Streamers</h1></div>
  <div style="display:flex;gap:8px;flex-wrap:wrap"><a class="btn secondary" href="relatorio_streamers.php">📄 PDF dos Ativos</a><a class="btn secondary" href="relatorio_streamers_xls.php">📊 XLS dos Ativos</a><a class="btn primary" href="streamer_novo.php">+ Cadastrar Streamer</a></div>
</div>

<form class="filters" method="get">
  <input name="q" value="<?=htmlspecialchars($search)?>" placeholder="Buscar por nome, Discord ou plataforma">
  <select name="category">
    <option value="">Todas as categorias</option>
    <option value="novato" <?=$category==='novato'?'selected':''?>>Novato</option>
    <option value="oficial" <?=$category==='oficial'?'selected':''?>>Oficial</option>
    <option value="afiliado" <?=$category==='afiliado'?'selected':''?>>Afiliado</option>
  </select>
  <select name="status">
    <option value="all" <?=$status==='all'?'selected':''?>>Todos os status</option>
    <option value="active" <?=$status==='active'?'selected':''?>>Ativos</option>
    <option value="inactive" <?=$status==='inactive'?'selected':''?>>Inativos</option>
  </select>
  <button class="btn secondary" type="submit">Filtrar</button>
  <a class="btn secondary" href="streamers.php">Limpar</a>
</form>

<?php if (!$rows): ?>
<div class="empty">Nenhum streamer encontrado com os filtros atuais.</div>
<?php else: ?>
<table>
<thead><tr>
<th>Streamer</th><th>Categoria</th><th>VODs</th><th>Tempo total</th><th>Pontos</th><th>Status</th><th>Ações</th>
</tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
<td><a href="streamer.php?id=<?=$r['id']?>"><strong><?=htmlspecialchars($r['name'])?></strong></a><small class="table-sub"><?=htmlspecialchars($r['platform'] ?: 'Plataforma não informada')?></small></td>
<td><span class="badge <?=$r['category']?>"><?=ucfirst($r['category'])?></span></td>
<td><?=$r['vod_count']?></td>
<td><?=formatDurationHM((int)$r['total_minutes'])?></td>
<td><?=number_format($r['points'],0,',','.')?></td>
<td><span class="status-dot <?=$r['active']?'status-active':'status-inactive'?>"><?=$r['active']?'Ativo':'Inativo'?></span></td>
<td class="actions">
  <a class="btn small-btn" href="streamer.php?id=<?=$r['id']?>">Ver</a>
  <a class="btn small-btn secondary" href="streamer_editar.php?id=<?=$r['id']?>">Editar</a>
  <a class="btn small-btn <?=$r['active']?'danger-btn':'success-btn'?>" href="../api/toggle_streamer.php?id=<?=$r['id']?>" onclick="return confirm('<?=$r['active']?'Inativar':'Ativar'?> este streamer?')"><?=$r['active']?'Inativar':'Ativar'?></a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php endif; ?>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
</body></html>