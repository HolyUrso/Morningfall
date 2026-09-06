<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: ../admin/streamers.php'); exit; }

$st = $pdo->prepare("SELECT active FROM streamers WHERE id=?");
$st->execute([$id]);
$s = $st->fetch();

if ($s) {
    $up = $pdo->prepare("UPDATE streamers SET active=? WHERE id=?");
    $up->execute([(int)!((int)$s['active']), $id]);
}
header('Location: ../admin/streamers.php');
exit;