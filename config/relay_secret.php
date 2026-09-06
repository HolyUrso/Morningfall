<?php
/**
 * RELAY_SECRET atual do projeto.
 * O Master pode alterar pelo painel.
 */
require_once __DIR__ . '/database.php';

if (!defined('MORNINGFALL_RELAY_SECRET')) {
    try {
        $st = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
        $st->execute(['discord_relay_secret']);
        $value = $st->fetchColumn();
    } catch (Throwable $e) {
        $value = '';
    }

    if (trim((string)$value) === '') {
        $value = 'MorningfallRelay_2026_X7p9K2mQ8vL4';
    }

    define('MORNINGFALL_RELAY_SECRET', trim((string)$value));
}
