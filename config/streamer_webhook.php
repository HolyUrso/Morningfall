<?php
/**
 * Webhooks individuais dos streamers via Cloudflare Worker Relay.
 * A URL da webhook continua salva por streamer no banco, mas o PHP do InfinityFree
 * não acessa o Discord diretamente. Ele envia a mensagem ao relay.
 */
require_once __DIR__ . '/database.php';

// Endereço público do Cloudflare Worker criado para o Morningfall.


require_once __DIR__ . '/relay_secret.php';
require_once __DIR__ . '/discord.php';

// O valor secreto fica separado em config/relay_secret.php e não deve ser compartilhado.

function sendStreamerWebhook(
    PDO $pdo,
    int $streamerId,
    string $title,
    string $description,
    string $type = 'info',
    array $fields = [],
    ?array &$diagnostic = null
): bool {
    $diagnostic = [
        'ok' => false,
        'http_code' => 0,
        'curl_error' => '',
        'message' => ''
    ];

    try {
        $st = $pdo->prepare("SELECT name, webhook_url FROM streamers WHERE id=? LIMIT 1");
        $st->execute([$streamerId]);
        $s = $st->fetch();

        if (!$s) {
            $diagnostic['message'] = 'Streamer não encontrado.';
            return false;
        }

        if (empty($s['webhook_url'])) {
            $diagnostic['message'] = 'Nenhuma webhook configurada para este streamer.';
            return false;
        }

        if (MORNINGFALL_RELAY_SECRET === '') {
            $diagnostic['message'] = 'O RELAY_SECRET ainda não foi configurado no site.';
            return false;
        }

        $url = trim($s['webhook_url']);
        $parsed = filter_var($url, FILTER_VALIDATE_URL);
        $path = $parsed ? (string)parse_url($url, PHP_URL_PATH) : '';
        $host = $parsed ? strtolower((string)parse_url($url, PHP_URL_HOST)) : '';

        // Validação compatível com webhooks atuais do Discord.
        // Não restringimos o token a um conjunto excessivamente rígido de caracteres.
        if (
            !$parsed ||
            !in_array($host, ['discord.com', 'discordapp.com'], true) ||
            !preg_match('~^/api/webhooks/[^/]+/[^/]+/?$~', $path)
        ) {
            $diagnostic['message'] = 'A URL informada não possui o formato esperado de uma webhook do Discord.';
            return false;
        }

        $colors = [
            'success' => 5763719,
            'danger'  => 15548997,
            'warning' => 16705372,
            'info'    => 5793266
        ];

        $embedFields = [];
        foreach ($fields as $name => $value) {
            $embedFields[] = [
                'name' => (string)$name,
                'value' => (string)$value,
                'inline' => true
            ];
        }

        // Sempre identificar claramente o streamer afetado no conteúdo do WebHook.
        // Isso garante que qualquer ação registrada tenha o nome do usuário,
        // mesmo que a chamada do evento não informe o nome na descrição.
        $streamerName = trim((string)$s['name']);
        $description = "👤 **Usuário: {$streamerName}**\n\n" . trim($description);

        $payload = [
            'webhook_url' => $url,
            'embeds' => [[
                'title' => $title,
                'description' => $description,
                'color' => $colors[$type] ?? $colors['info'],
                'fields' => $embedFields,
                'footer' => ['text' => 'Morningfal Creatos V1 • luciferms666'],
                'timestamp' => gmdate('c')
            ]]
        ];

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $diagnostic['message'] = 'Não foi possível preparar a mensagem JSON.';
            return false;
        }

        if (!function_exists('curl_init')) {
            $diagnostic['message'] = 'A extensão cURL do PHP não está disponível no servidor.';
            return false;
        }

        $relayUrl = rtrim(DISCORD_RELAY_URL, '/') . '/send';
        $ch = curl_init($relayUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Content-Length: ' . strlen($json),
                'User-Agent: Morningfall-Creators-Relay/1.0',
                'X-Relay-Secret: ' . MORNINGFALL_RELAY_SECRET
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $diagnostic['http_code'] = $http;
        $diagnostic['curl_error'] = $curlError;
        $diagnostic['curl_errno'] = $curlErrno;

        if ($response === false) {
            $diagnostic['message'] = $curlError !== ''
                ? 'O servidor não conseguiu conectar ao Cloudflare Relay.'
                : 'A requisição cURL ao relay falhou.';
            return false;
        }

        $relay = json_decode($response, true);
        if ($http >= 200 && $http < 300 && !empty($relay['success'])) {
            $diagnostic['ok'] = true;
            $diagnostic['message'] = 'Webhook enviada com sucesso pelo Cloudflare Relay.';
            return true;
        }

        if ($http === 401) {
            $diagnostic['message'] = 'O Cloudflare Relay recusou a autenticação. Verifique o RELAY_SECRET.';
        } elseif ($http === 400) {
            $diagnostic['message'] = $relay['error'] ?? 'O relay recusou os dados enviados.';
        } elseif ($http === 404) {
            $diagnostic['message'] = 'Endpoint do Cloudflare Relay não encontrado.';
        } elseif ($http === 502) {
            $diagnostic['message'] = 'O relay não conseguiu conectar ao Discord.';
        } elseif ($http >= 500) {
            $diagnostic['message'] = 'O Cloudflare Relay retornou um erro interno.';
        } else {
            $diagnostic['message'] = $relay['error'] ?? 'O Cloudflare Relay recusou a requisição.';
        }

        return false;
    } catch (Throwable $e) {
        $diagnostic['message'] = 'Erro interno ao enviar pelo Cloudflare Relay.';
        $diagnostic['curl_error'] = $e->getMessage();
        return false;
    }
}
