<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
try {
    require_once __DIR__ . '/../config/database.php';
    $stmt = $pdo->query("SELECT name, command, description FROM player_commands WHERE active = 1 ORDER BY position ASC, id ASC");
    echo json_encode(['ok'=>true,'source'=>'Carmesim Creators','commands'=>$stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'source'=>'Carmesim Creators','commands'=>[],'message'=>'Não foi possível carregar os comandos.'], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
