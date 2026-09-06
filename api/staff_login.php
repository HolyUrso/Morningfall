<?php
require_once '../config/database.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../login.php'); exit; }
$stmt=$pdo->prepare("SELECT * FROM staff_users WHERE username=? AND active=1 LIMIT 1");
$stmt->execute([trim($_POST['username']??'')]); $u=$stmt->fetch();
if($u && password_verify($_POST['password']??'', $u['password_hash'])){
 $_SESSION['staff_id']=$u['id']; $_SESSION['staff_name']=$u['name']; $_SESSION['staff_role']=$u['role']; $_SESSION['staff_discord_avatar']=$u['discord_avatar'] ?? ''; header('Location: ../admin/index.php'); exit;
}
header('Location: ../login.php?error=1'); exit;
