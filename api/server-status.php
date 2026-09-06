<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$code = 'm4zzggv';
$joinUrl = 'https://cfx.re/join/' . $code;
$apiUrl = 'https://frontend.cfx-services.net/api/servers/single/' . $code;

function fetch_url($url) {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json,text/html;q=0.9,*/*;q=0.8',
                'Cache-Control: no-cache',
                'Pragma: no-cache'
            ],
            CURLOPT_USERAGENT => 'CarmesimRoleplayStatus/1.0'
        ]);
        $body = curl_exec($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        return [
            'body' => ($body === false ? '' : $body),
            'http' => $http,
            'error' => $error
        ];
    }

    $ctx = stream_context_create([
        'http' => [
            'timeout' => 10,
            'ignore_errors' => true,
            'header' => "Accept: application/json,text/html;q=0.9,*/*;q=0.8\r\nCache-Control: no-cache\r\nPragma: no-cache\r\nUser-Agent: CarmesimRoleplayStatus/1.0\r\n"
        ]
    ]);
    $body = @file_get_contents($url, false, $ctx);
    $http = 0;
    if (isset($http_response_header) && is_array($http_response_header)) {
        foreach ($http_response_header as $header) {
            if (preg_match('/^HTTP\/\S+\s+(\d+)/i', $header, $m)) {
                $http = (int)$m[1];
            }
        }
    }
    return [
        'body' => ($body === false ? '' : $body),
        'http' => $http,
        'error' => ($body === false ? 'request_failed' : '')
    ];
}

/* CFX join page: tells us whether the public CFX join URL is reachable. */
$join = fetch_url($joinUrl);
$cfxAccessible = ($join['http'] >= 200 && $join['http'] < 400);

/* Current CFX server-list data: provides online state and player count. */
$api = fetch_url($apiUrl);
$data = json_decode($api['body'], true);
$server = null;

if (is_array($data)) {
    if (isset($data['Data']) && is_array($data['Data'])) {
        $server = $data['Data'];
    } elseif (isset($data['data']) && is_array($data['data'])) {
        $server = $data['data'];
    } elseif (isset($data['clients']) || isset($data['hostname'])) {
        $server = $data;
    }
}

$online = is_array($server);
$players = null;
$maxPlayers = null;
$hostname = 'Condado Carmesim';

if ($online) {
    if (isset($server['clients'])) {
        $players = (int)$server['clients'];
    }
    if (isset($server['svMaxclients'])) {
        $maxPlayers = (int)$server['svMaxclients'];
    } elseif (isset($server['sv_maxclients'])) {
        $maxPlayers = (int)$server['sv_maxclients'];
    }

    if (!empty($server['hostname'])) {
        $hostname = (string)$server['hostname'];
    } elseif (!empty($server['vars']['sv_projectName'])) {
        $hostname = (string)$server['vars']['sv_projectName'];
    } elseif (!empty($server['vars']['sv_hostname'])) {
        $hostname = (string)$server['vars']['sv_hostname'];
    }
}

/*
 * If CFX is reachable and the server-list API is temporarily unavailable,
 * do not pretend the server is offline. Report "unknown" to the frontend.
 */
$status = $online ? 'online' : ($cfxAccessible ? 'unknown' : 'offline');

echo json_encode([
    'ok' => true,
    'online' => $online,
    'status' => $status,
    'players' => $players,
    'maxPlayers' => $maxPlayers,
    'hostname' => $hostname,
    'cfxAccessible' => $cfxAccessible,
    'checkedAt' => date('c')
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
