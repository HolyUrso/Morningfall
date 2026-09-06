<?php
session_start();
if (empty($_SESSION['streamer_id'])) {
    header('Location: ../streamer-login.php');
    exit;
}
