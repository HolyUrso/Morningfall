<?php
require_once '../config/database.php';
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../streamer-login.php'); exit; }
$stmt=$pdo->prepare("SELECT id FROM streamers WHERE access_code=? AND active=1 LIMIT 1");
$stmt->execute([trim($_POST['access_code']??'')]); $s=$stmt->fetch();
if($s){ $_SESSION['streamer_id']=$s['id']; header('Location: ../streamer/index.php'); exit; }
header('Location: ../streamer-login.php?error=1'); exit;
