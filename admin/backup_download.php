<?php
require_once '../config/auth.php';
require_once '../config/database.php';

if (($_SESSION['staff_role'] ?? '') !== 'master') {
    try {
        $roleQ = $pdo->prepare("SELECT role FROM staff_users WHERE id=? AND active=1 LIMIT 1");
        $roleQ->execute([(int)($_SESSION['staff_id'] ?? 0)]);
        if ($roleQ->fetchColumn() === 'master') $_SESSION['staff_role'] = 'master';
    } catch (Throwable $e) {}
}
if (($_SESSION['staff_role'] ?? '') !== 'master') { http_response_code(403); exit('Acesso restrito ao Staff Master.'); }

$name = basename($_GET['file'] ?? '');
if ($name === '' || !preg_match('/^morningfall_(backup|uploaded)_.*\.zip$/i', $name)) { http_response_code(400); exit('Arquivo inválido.'); }
$path = dirname(__DIR__) . '/storage/backups/' . $name;
if (!is_file($path)) { http_response_code(404); exit('Backup não encontrado.'); }

header('Content-Type: application/zip');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . str_replace('"','',basename($path)) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
