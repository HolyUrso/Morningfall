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


/*
 * Relatório somente de streamers ativos.
 * - Horas de Live: soma da duração das VODs.
 * - Qtd. de Conteúdos: conteúdos aprovados.
 * - Pontos: saldo atual do streamer.
 * - Qtd. Resgates: total de pedidos de resgate registrados.
 * - Rank Atual: classificação por pontos atuais entre os streamers ativos.
 */
$sql = "
SELECT
    s.id,
    s.name,
    s.category,
    s.discord,
    s.platform,
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

    /*
     * Desempate:
     * 1º Pontos
     * 2º Horas de Live
     * 3º Nome (apenas para deixar a ordem determinística)
     *
     * Assim, dois streamers com a mesma pontuação não ficam empatados
     * quando um deles possui mais horas de live.
     */
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

function formatHoursMinutes(int $minutes): string {
    return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
}

$totalStreamers = count($rows);
$totalHoursMinutes = array_sum(array_map(fn($r) => (int)$r['total_minutes'], $rows));
$totalPoints = array_sum(array_map(fn($r) => (int)$r['current_points'], $rows));
$totalRedemptions = array_sum(array_map(fn($r) => (int)$r['redemption_count'], $rows));
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Relatório de Streamers Ativos · Morningfall Creators</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.report-toolbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:14px;
    margin:20px 0;
    flex-wrap:wrap;
}
.report-actions{display:flex;gap:8px;flex-wrap:wrap}
.report-summary{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:12px;
    margin:18px 0;
}
.report-summary .stat{
    min-height:auto;
}
.report-table-wrap{
    overflow-x:auto;
    background:#111722;
    border:1px solid #29344a;
    border-radius:14px;
}
.report-table{
    width:100%;
    border-collapse:collapse;
    min-width:1100px;
}
.report-table th{
    white-space:nowrap;
    font-size:12px;
    text-transform:uppercase;
}
.report-table td,
.report-table th{
    padding:13px 12px;
    border-bottom:1px solid #29344a;
    text-align:left;
}
.report-table tbody tr:last-child td{border-bottom:0}
.report-table .center{text-align:center}
.report-table .right{text-align:right}
.report-title-print{display:none}
.report-note{
    margin-top:16px;
    color:#91a0b8;
    font-size:13px;
}
@media(max-width:800px){
    .report-summary{grid-template-columns:repeat(2,1fr)}
}
@media print{
    @page{
        size:A4 landscape;
        margin:10mm;
    }
    html,body{
        background:#fff !important;
        color:#111 !important;
    }
    body{
        font-family:Arial,sans-serif !important;
    }
    .topbar,
    .site-footer,
    .page-head>a,
    .report-toolbar .report-actions,
    .report-note,
    .no-print{
        display:none !important;
    }
    .container{
        max-width:none !important;
        width:100% !important;
        padding:0 !important;
        margin:0 !important;
    }
    .report-title-print{
        display:block;
        margin-bottom:12px;
    }
    .report-title-print h1{
        color:#111 !important;
        margin:0 0 4px;
        font-size:22px;
    }
    .report-title-print p{
        margin:0;
        color:#555 !important;
        font-size:11px;
    }
    .report-summary{
        display:grid;
        grid-template-columns:repeat(4,1fr);
        gap:8px;
        margin:10px 0;
    }
    .report-summary .stat{
        border:1px solid #ccc !important;
        background:#f7f7f7 !important;
        color:#111 !important;
        padding:8px !important;
    }
    .report-summary .stat small,
    .report-summary .stat strong{
        color:#111 !important;
    }
    .report-table-wrap{
        border:1px solid #bbb !important;
        background:#fff !important;
        overflow:visible !important;
    }
    .report-table{
        min-width:0 !important;
        font-size:9px !important;
        color:#111 !important;
    }
    .report-table th{
        background:#eee !important;
        color:#111 !important;
        font-size:8px !important;
    }
    .report-table td,
    .report-table th{
        border-color:#ccc !important;
        padding:6px 5px !important;
    }
    .report-table td{
        color:#111 !important;
    }
    .badge{
        color:#111 !important;
        background:#eee !important;
        border:1px solid #bbb !important;
    }
}
.report-filter{display:flex;align-items:end;gap:10px;flex-wrap:wrap;margin:16px 0 8px;padding:14px;background:#111722;border:1px solid #29344a;border-radius:14px}.filter-field{display:flex;flex-direction:column;gap:5px}.filter-field label{font-size:11px;text-transform:uppercase;color:#91a0b8;font-weight:700}.filter-field input{min-width:155px}.report-period{margin:0 0 12px;color:#91a0b8;font-size:13px}@media print{.report-filter{display:none!important}}</style>
</head>
<body>

<header class="topbar">
    <div><b>MORNINGFALL</b> <span>CREATORS</span></div>
    <div><?=htmlspecialchars($_SESSION['staff_name'] ?? 'Staff')?> · <a href="../logout.php">Sair</a></div>
</header>

<main class="container">
    <div class="report-toolbar">
        <div>
            <a href="index.php">← Dashboard</a>
            <p class="eyebrow">RELATÓRIO</p>
            <h1>📄 Streamers Ativos</h1>
        </div>

        <form class="report-filter no-print" method="get">
<div class="filter-field"><label for="data_inicio">Data inicial</label><input type="date" id="data_inicio" name="data_inicio" value="<?=htmlspecialchars($startDate)?>"></div>
<div class="filter-field"><label for="data_fim">Data final</label><input type="date" id="data_fim" name="data_fim" value="<?=htmlspecialchars($endDate)?>"></div>
<div class="filter-field"><label for="categoria">Categoria</label>
<select id="categoria" name="categoria">
<option value="">Todas as categorias</option>
<?php foreach($labels as $key=>$label): ?>
<option value="<?=htmlspecialchars($key)?>" <?=$category===$key?'selected':''?>><?=htmlspecialchars($label)?></option>
<?php endforeach; ?>
</select></div>
<button class="btn primary" type="submit">🔎 Filtrar período</button>
<a class="btn" href="relatorio_streamers.php">↺ Limpar</a>
</form>

<div class="report-actions">
            <button class="btn primary" type="button" onclick="window.print()">📄 Gerar PDF</button>
            <a class="btn secondary" href="relatorio_streamers_xls.php<?php
$q=[];
if($startDate!=='') $q['data_inicio']=$startDate;
if($endDate!=='') $q['data_fim']=$endDate;
if($category!=='') $q['categoria']=$category;
echo $q ? '?'.http_build_query($q) : '';
?>">📊 Baixar XLS</a>
            <a class="btn secondary" href="streamers.php?status=active">👥 Ver ativos</a>
        </div>
    </div>

    <p class="report-period"><?php if($startDate || $endDate): ?>📅 Período filtrado: <strong><?=htmlspecialchars($startDate ?: 'início')?></strong> até <strong><?=htmlspecialchars($endDate ?: 'fim')?></strong><?php else: ?>📅 Período: <strong>Todos os registros</strong><?php endif; ?></p>

<div class="report-title-print">
        <h1>Morningfall Creators — Streamers Ativos</h1>
        <p>Relatório gerado em <?=date('d/m/Y H:i')?> · Somente streamers ativos<?php if($category): ?> · Categoria: <?=htmlspecialchars($labels[$category] ?? $category)?><?php endif; ?><?php if($startDate || $endDate): ?> · Período: <?=htmlspecialchars($startDate ?: 'início')?> a <?=htmlspecialchars($endDate ?: 'fim')?><?php endif; ?></p>
    </div>

    <div class="report-summary">
        <div class="stat">
            <small>STREAMERS ATIVOS</small>
            <strong><?=$totalStreamers?></strong>
        </div>
        <div class="stat">
            <small>HORAS DE LIVE</small>
            <strong><?=formatHoursMinutes($totalHoursMinutes)?></strong>
        </div>
        <div class="stat">
            <small>PONTOS ATUAIS</small>
            <strong><?=number_format($totalPoints,0,',','.')?></strong>
        </div>
        <div class="stat">
            <small>RESGATES</small>
            <strong><?=number_format($totalRedemptions,0,',','.')?></strong>
        </div>
    </div>

    <?php if (!$rows): ?>
        <div class="empty">Nenhum streamer ativo encontrado.</div>
    <?php else: ?>
        <div class="report-table-wrap">
            <table class="report-table">
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
                        <th class="center">Rank Atual</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td>
                            <span class="badge <?=htmlspecialchars($r['category'])?>">
                                <?=htmlspecialchars($labels[$r['category']] ?? $r['category'])?>
                            </span>
                        </td>
                        <td><strong><?=htmlspecialchars($r['name'])?></strong></td>
                        <td><?=htmlspecialchars($r['discord'] ?: '—')?></td>
                        <td><?=htmlspecialchars($r['platform'] ?: '—')?></td>
                        <td><?=formatHoursMinutes((int)$r['total_minutes'])?></td>
                        <td><?=number_format((int)$r['content_count'],0,',','.')?></td>
                        <td><strong><?=number_format((int)$r['current_points'],0,',','.')?></strong></td>
                        <td><?=number_format((int)$r['redemption_count'],0,',','.')?></td>
                        <td class="center"><strong>#<?=$r['rank']?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <p class="report-note">
        <strong>Critérios:</strong> Horas de Live = soma das durações das VODs dentro do período selecionado.
        Conteúdos = conteúdos aprovados dentro do período.
        <?php if($pointsArePeriod): ?>Pontos = pontos positivos registrados no período selecionado.<?php else: ?>Pontos = saldo atual.<?php endif; ?>
        Resgates = total de pedidos registrados dentro do período.
        Rank = pontos atuais; em caso de empate, vence quem tiver mais horas de live no período. Se também empatar nas horas, o nome é usado apenas para ordenar.
    </p>
</main>

<footer class="site-footer">
    Morningfall Creators V1 <span>•</span>
    Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a>
</footer>

</body>
</html>
