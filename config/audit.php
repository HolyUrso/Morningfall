<?php
/**
 * Auditoria central do Morningfall Creators.
 * Registra quem fez a alteração, usuário afetado e antes/depois.
 */
if (!function_exists('auditLog')) {
    function auditLog(PDO $pdo, string $action, string $category, ?int $affectedUserId = null, string $affectedUserName = '', string $details = '', $before = null, $after = null): void {
        try {
            if (session_status() !== PHP_SESSION_ACTIVE) @session_start();
            $staffId = !empty($_SESSION['staff_id']) ? (int)$_SESSION['staff_id'] : null;
            $staffName = trim((string)($_SESSION['staff_name'] ?? ''));
            if ($staffName === '' && $staffId) {
                try {
                    $q = $pdo->prepare('SELECT name FROM staff_users WHERE id=? LIMIT 1');
                    $q->execute([$staffId]);
                    $staffName = (string)($q->fetchColumn() ?: 'Staff');
                } catch (Throwable $e) { $staffName = 'Staff'; }
            }
            if ($staffName === '') $staffName = 'Sistema';
            $encode = static function($value) {
                if ($value === null || $value === '') return null;
                if (is_string($value)) return $value;
                return json_encode($value, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            };
            $st = $pdo->prepare('INSERT INTO audit_logs
                (staff_id,staff_name,affected_user_id,affected_user_name,action,category,details,before_data,after_data,ip_address,user_agent)
                VALUES(?,?,?,?,?,?,?,?,?,?,?)');
            $st->execute([
                $staffId, $staffName, $affectedUserId, $affectedUserName !== '' ? $affectedUserName : null,
                $action, $category, $details, $encode($before), $encode($after),
                substr((string)($_SERVER['REMOTE_ADDR'] ?? ''),0,45),
                substr((string)($_SERVER['HTTP_USER_AGENT'] ?? ''),0,500)
            ]);
        } catch (Throwable $e) {
            // Auditoria nunca deve impedir a operação principal.
        }
    }
}
