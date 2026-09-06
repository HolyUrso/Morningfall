<?php
/*
 * Morningfall Creators — Discord / Cloudflare Relay
 * Valores atuais do projeto mantidos como fallback.
 * O Master pode alterar pelo painel.
 */
require_once __DIR__ . '/database.php';

function morningfallDiscordSetting(PDO $pdo, string $key, string $fallback = ''): string {
    try {
        $st = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
        $st->execute([$key]);
        $value = $st->fetchColumn();
        return ($value === false || trim((string)$value) === '') ? $fallback : trim((string)$value);
    } catch (Throwable $e) {
        return $fallback;
    }
}

if (!defined('DISCORD_RELAY_URL')) {
    define('DISCORD_RELAY_URL', morningfallDiscordSetting(
        $pdo,
        'discord_relay_url',
        'https://morningfall-relay.santourso.workers.dev'
    ));
}

if (!defined('DISCORD_RELAY_SECRET')) {
    define('DISCORD_RELAY_SECRET', morningfallDiscordSetting(
        $pdo,
        'discord_relay_secret',
        'MorningfallRelay_2026_X7p9K2mQ8vL4'
    ));
}
