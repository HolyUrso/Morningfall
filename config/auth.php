<?php
session_start();

if (empty($_SESSION['staff_id'])) {
    header('Location: ../login.php');
    exit;
}

/*
 * V25.1 — Compatibilidade com sessões antigas.
 * Se o usuário já estava logado antes da V25, a sessão pode não ter
 * staff_role/staff_discord_avatar. Busca novamente no banco.
 */
if (!isset($_SESSION['staff_role']) || !isset($_SESSION['staff_discord_avatar'])) {
    try {
        require_once __DIR__ . '/database.php';
        $st = $pdo->prepare("SELECT role, discord_avatar FROM staff_users WHERE id=? AND active=1 LIMIT 1");
        $st->execute([(int)$_SESSION['staff_id']]);
        $staff = $st->fetch();
        if (!$staff) {
            session_unset();
            session_destroy();
            header('Location: ../login.php');
            exit;
        }
        $_SESSION['staff_role'] = $staff['role'];
        $_SESSION['staff_discord_avatar'] = $staff['discord_avatar'] ?? '';
    } catch (Throwable $e) {
        // Mantém a sessão existente; páginas que exigem dados específicos tratarão o acesso.
    }
}
