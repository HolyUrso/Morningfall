<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/streamer_webhook.php';

$id = (int)($_POST['streamer_id'] ?? $_GET['streamer_id'] ?? 0);
if ($id <= 0) {
    header('Location: ../admin/streamers.php');
    exit;
}

$diagnostic = [];
$ok = sendStreamerWebhook(
    $pdo,
    $id,
    '🔔 Teste de Webhook',
    'A webhook individual deste streamer está configurada e funcionando.',
    'success',
    [
        'Streamer' => 'ID #'.$id,
        'Ação' => 'Teste realizado pela Staff'
    ],
    $diagnostic
);

$params = [
    'webhook_test' => $ok ? 'ok' : 'fail'
];

if (!$ok) {
    $params['code'] = (string)($diagnostic['http_code'] ?? 0);
    $params['err'] = mb_substr((string)($diagnostic['curl_error'] ?? ''), 0, 180);
    $params['msg'] = mb_substr((string)($diagnostic['message'] ?? ''), 0, 180);
}

header('Location: ../admin/streamer.php?id='.$id.'&'.http_build_query($params));
exit;
