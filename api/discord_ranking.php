<?php
require_once '../config/discord_ranking.php';
header('Content-Type: application/json; charset=utf-8');
$key=trim((string)($_GET['key'] ?? $_SERVER['HTTP_X_RANKING_KEY'] ?? ''));
$stored=discordRankingSetting($pdo,'discord_ranking_key','');
if($stored==='' || !hash_equals($stored,$key)) { http_response_code(403); echo json_encode(['success'=>false,'error'=>'Chave inválida.']); exit; }
try {
    $result=discordRankingUpdate($pdo);
    echo json_encode(['success'=>true,'updated_at'=>date('d/m/Y H:i'),'top_count'=>$result['top_count'],'message_id'=>$result['message_id'],'mode'=>$result['mode']], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch(Throwable $e) {
    http_response_code(502); echo json_encode(['success'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}
