<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

try {
    require_once __DIR__ . '/../config/database.php';

    $sql = "SELECT
                s.id,
                s.name,
                s.category,
                s.points,
                s.discord_avatar,
                COALESCE((
                    SELECT SUM(pt.points)
                    FROM point_transactions pt
                    WHERE pt.streamer_id = s.id
                      AND pt.points > 0
                ), 0) AS total_points
            FROM streamers s
            WHERE s.active = 1
            ORDER BY total_points DESC, s.points DESC, s.name ASC
            LIMIT 5";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $rank = 0;
    $last = null;
    foreach ($rows as $i => &$row) {
        $score = (int)$row['total_points'];
        if ($last === null || $score < $last) {
            $rank = $i + 1;
        }
        $row['rank'] = $rank;
        $row['id'] = (int)$row['id'];
        $row['points'] = (int)$row['points'];
        $row['total_points'] = $score;
        $row['name'] = (string)$row['name'];
        $row['category'] = (string)($row['category'] ?? '');
        $row['discord_avatar'] = !empty($row['discord_avatar']) ? (string)$row['discord_avatar'] : '';
        $last = $score;
    }
    unset($row);

    echo json_encode([
        'ok' => true,
        'source' => 'Carmesim Creators',
        'title' => 'Ranking Geral',
        'ranking' => $rows
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'source' => 'Carmesim Creators',
        'ranking' => [],
        'message' => 'Não foi possível carregar o Ranking Geral.'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
