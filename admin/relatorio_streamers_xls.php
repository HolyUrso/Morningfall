<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$labels = [
    'novato' => 'Novato',
    'oficial' => 'Oficial',
    'afiliado' => 'Afiliado'
];

$startDate = trim((string)($_GET['data_inicio'] ?? ''));
$endDate = trim((string)($_GET['data_fim'] ?? ''));
if ($startDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) $startDate = '';
if ($endDate !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) $endDate = '';
if ($startDate !== '' && $endDate !== '' && $startDate > $endDate) {
    [$startDate, $endDate] = [$endDate, $startDate];
}
$vodDateFilter = '';
$vodParams = [];
if ($startDate !== '') { $vodDateFilter .= ' AND v.vod_date >= ?'; $vodParams[] = $startDate; }
if ($endDate !== '') { $vodDateFilter .= ' AND v.vod_date <= ?'; $vodParams[] = $endDate; }

$contentDateFilter = '';
$contentParams = [];
if ($startDate !== '') { $contentDateFilter .= ' AND cs.submission_date >= ?'; $contentParams[] = $startDate; }
if ($endDate !== '') { $contentDateFilter .= ' AND cs.submission_date <= ?'; $contentParams[] = $endDate; }

$redemptionDateFilter = '';
$redemptionParams = [];

$category = trim((string)($_GET['categoria'] ?? ''));
$allowedCategories = ['novato','afiliado','oficial'];
if (!in_array($category, $allowedCategories, true)) $category = '';

$categoryFilter = '';
$categoryParams = [];
if ($category !== '') {
    $categoryFilter = ' AND s.category = ?';
    $categoryParams[] = $category;
}

$periodPointsSelect = 'COALESCE(s.points, 0)';
$periodPointsParams = [];
$pointsArePeriod = ($startDate !== '' || $endDate !== '');
if ($pointsArePeriod) {
    $pointDateFilter = '';
    if ($startDate !== '') { $pointDateFilter .= ' AND pt.created_at >= ?'; $periodPointsParams[] = $startDate . ' 00:00:00'; }
    if ($endDate !== '') { $pointDateFilter .= ' AND pt.created_at <= ?'; $periodPointsParams[] = $endDate . ' 23:59:59'; }
    $periodPointsSelect = "(SELECT COALESCE(SUM(pt.points),0) FROM point_transactions pt WHERE pt.streamer_id = s.id AND pt.points > 0 $pointDateFilter)";
}

if ($startDate !== '') { $redemptionDateFilter .= ' AND r.created_at >= ?'; $redemptionParams[] = $startDate . ' 00:00:00'; }
if ($endDate !== '') { $redemptionDateFilter .= ' AND r.created_at <= ?'; $redemptionParams[] = $endDate . ' 23:59:59'; }


$sql = "
SELECT
    s.id,
    s.name,
    s.category,
    s.discord,
    sp.platform,
    COALESCE((SELECT SUM(v.duration_minutes)
              FROM vods v
              WHERE v.streamer_id = s.id $vodDateFilter), 0) AS total_minutes,
    COALESCE((SELECT COUNT(*)
              FROM content_submissions cs
              WHERE cs.streamer_id = s.id
                AND cs.status = 'approved' $contentDateFilter), 0) AS content_count,
    $periodPointsSelect AS current_points,
    COALESCE((SELECT COUNT(*)
              FROM redemptions r
              WHERE r.streamer_id = s.id $redemptionDateFilter), 0) AS redemption_count
FROM streamers s
LEFT JOIN streamer_platforms sp
    ON sp.streamer_id=s.id
 AND sp.is_primary=1
 AND sp.active=1
WHERE s.active = 1 $categoryFilter
ORDER BY s.points DESC, total_minutes DESC, s.name ASC
";

$stmt = $pdo->prepare($sql);
$allParams = array_merge($vodParams, $contentParams, $periodPointsParams, $redemptionParams, $categoryParams);
$stmt->execute($allParams);
$rows = $stmt->fetchAll();

$rank = 0;
$position = 0;
$lastPoints = null;
$lastMinutes = null;

foreach ($rows as &$row) {
    $position++;
    $points = (int)$row['current_points'];
    $minutes = (int)$row['total_minutes'];

    if (
        $lastPoints === null ||
        $points < $lastPoints ||
        ($points === $lastPoints && $minutes < $lastMinutes)
    ) {
        $rank = $position;
    }

    $row['rank'] = $rank;
    $lastPoints = $points;
    $lastMinutes = $minutes;
}
unset($row);

function h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function hoursMinutes(int $minutes): string {
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

$filename = 'streamers_ativos_' . ($startDate ?: 'inicio') . '_' . ($endDate ?: 'fim') . '.xls';

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF";
?>
<html>
<head>
<meta charset="UTF-8">
<style>
table{border-collapse:collapse}
th,td{border:1px solid #999;padding:7px}
th{background:#ddd;font-weight:bold}
.num{text-align:center}
</style>
</head>
<body>
<p><strong>Morningfall Creators — Streamers Ativos</strong><br>Período: <?=h($startDate ?: 'Todos')?> até <?=h($endDate ?: 'Todos')?><?php if($category): ?> · Categoria: <?=h($labels[$category] ?? $category)?><?php endif; ?></p>
<table>
<thead>
<tr>
<th>Categoria</th>
<th>Nome</th>
<th>Discord</th>
<th>Plataforma</th>
<th>Horas de Live</th>
<th>Qtd. de Conteúdos</th>
<th><?=($pointsArePeriod ? 'Pontos no Período' : 'Pontos')?></th>
<th>Qtd. Resgates</th>
<th>Rank Atual</th>
</tr>
</thead>
<tbody>
<?php foreach ($rows as $r): ?>
<tr>
<td><?=h($labels[$r['category']] ?? $r['category'])?></td>
<td><?=h($r['name'])?></td>
<td><?=h($r['discord'] ?: '—')?></td>
<td><?=h($r['platform'] ?: '—')?></td>
<td><?=h(hoursMinutes((int)$r['total_minutes']))?></td>
<td class="num"><?=number_format((int)$r['content_count'],0,',','.')?></td>
<td class="num"><?=number_format((int)$r['current_points'],0,',','.')?></td>
<td class="num"><?=number_format((int)$r['redemption_count'],0,',','.')?></td>
<td class="num"><?=h($r['rank'])?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</body>
</html>
