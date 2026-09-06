<?php
require_once __DIR__.'/../classes/PlatformSync.php';

header('Content-Type: application/json; charset=utf-8');

$plataforma = trim((string)($_GET['plataforma'] ?? $_POST['plataforma'] ?? ''));
$login = trim((string)($_GET['login'] ?? $_GET['url'] ?? $_POST['login'] ?? $_POST['url'] ?? ''));

try {
    if ($login === '') {
        throw new InvalidArgumentException('Informe um login ou URL de canal.');
    }

    $platformSync = new PlatformSync();
    $result = $platformSync->resolve($login, $plataforma !== '' ? $plataforma : null);

    echo json_encode([
        'success' => true,
        'platform' => (string)($result['platform'] ?? ''),
        'username' => (string)($result['username'] ?? ''),
        'display_name' => (string)($result['display_name'] ?? ''),
        'channel_id' => (string)($result['channel_id'] ?? ''),
        'avatar_url' => (string)($result['avatar_url'] ?? ''),
        'channel_url' => (string)($result['channel_url'] ?? '')
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

}