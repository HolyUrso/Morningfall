<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/discord.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SESSION['staff_role'] ?? '') !== 'master') {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'Somente o Staff Master pode buscar avatar.']);
    exit;
}

$id=preg_replace('/\D+/','',trim($_POST['discord_id']??''));
$type=$_POST['type']??'streamer';
$entityId=(int)($_POST['entity_id']??0);

if($id==='' || strlen($id)<15){
    echo json_encode(['success'=>false,'error'=>'Informe um ID de Discord válido.']);
    exit;
}
if(!in_array($type,['streamer','staff','site_team'],true)){
    echo json_encode(['success'=>false,'error'=>'Tipo inválido.']);
    exit;
}

$url=rtrim(DISCORD_RELAY_URL,'/').'/discord-avatar';
$payload=json_encode(['discord_id'=>$id]);

/*
 * O InfinityFree pode não conseguir resolver diretamente o hostname
 * workers.dev. Primeiro tentamos o cURL normal. Se falhar por DNS,
 * resolvemos o hostname via DNS-over-HTTPS e usamos CURLOPT_RESOLVE.
 * O hostname original continua sendo usado no HTTPS, então a validação
 * do certificado permanece correta.
 */
$relayHost = parse_url($url, PHP_URL_HOST);
$relayPort = (int)(parse_url($url, PHP_URL_PORT) ?: 443);

$curlRequest = function($requestUrl, $resolve = null) use ($payload) {
    $ch = curl_init($requestUrl);

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-Relay-Secret: '.DISCORD_RELAY_SECRET,
            'User-Agent: Carmesim-Creators/1.0'
        ],
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ];

    if ($resolve) {
        $options[CURLOPT_RESOLVE] = [$resolve];
    }

    curl_setopt_array($ch, $options);

    $result = [
        'body' => curl_exec($ch),
        'code' => (int)curl_getinfo($ch, CURLINFO_HTTP_CODE),
        'error' => curl_error($ch)
    ];

    curl_close($ch);
    return $result;
};

/* 1) Tentativa normal. */
$result = $curlRequest($url);
$body = $result['body'];
$code = $result['code'];
$err = $result['error'];

/*
 * 2) Se o hostname não foi resolvido/conectado, tenta descobrir IPv4
 *    por DNS-over-HTTPS. Usamos dois resolvedores públicos como fallback.
 */
if ($body === false || $code === 0) {
    $resolvedIps = [];

    foreach ([
        'https://dns.google/resolve?name='.rawurlencode($relayHost).'&type=A',
        'https://cloudflare-dns.com/dns-query?name='.rawurlencode($relayHost).'&type=A'
    ] as $dnsUrl) {
        $dnsCh = curl_init($dnsUrl);

        curl_setopt_array($dnsCh, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_HTTPHEADER => [
                'Accept: application/dns-json',
                'User-Agent: Carmesim-Creators/1.0'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ]);

        $dnsBody = curl_exec($dnsCh);
        curl_close($dnsCh);

        if ($dnsBody === false) {
            continue;
        }

        $dnsData = json_decode($dnsBody, true);

        if (!empty($dnsData['Answer']) && is_array($dnsData['Answer'])) {
            foreach ($dnsData['Answer'] as $answer) {
                if (
                    isset($answer['type'], $answer['data']) &&
                    (int)$answer['type'] === 1 &&
                    filter_var($answer['data'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                ) {
                    $resolvedIps[] = $answer['data'];
                }
            }
        }

        if ($resolvedIps) {
            break;
        }
    }

    $resolvedIps = array_values(array_unique($resolvedIps));

    foreach ($resolvedIps as $ip) {
        $resolved = $relayHost.':'.$relayPort.':'.$ip;
        $retry = $curlRequest($url, $resolved);

        if ($retry['body'] !== false && $retry['code'] > 0) {
            $body = $retry['body'];
            $code = $retry['code'];
            $err = $retry['error'];
            break;
        }

        $err = $retry['error'] ?: $err;
    }
}

if($body===false){
    echo json_encode([
        'success'=>false,
        'error'=>'Não foi possível conectar ao Cloudflare Relay.'.($err?' '.$err:'')
    ]);
    exit;
}

$data=json_decode($body,true);
if(!is_array($data)){
    echo json_encode(['success'=>false,'error'=>'O Cloudflare Relay retornou uma resposta inválida.','http'=>$code]);
    exit;
}
if($code<200 || $code>=300 || empty($data['success'])){
    echo json_encode([
        'success'=>false,
        'error'=>$data['error']??'Não foi possível consultar o Discord.',
        'http'=>$code
    ]);
    exit;
}

$avatar=$data['avatar']??null;
if(!$avatar){
    echo json_encode([
        'success'=>true,
        'avatar'=>null,
        'username'=>$data['username']??'',
        'global_name'=>$data['global_name']??'',
        'message'=>'Este usuário não possui avatar personalizado no Discord.'
    ]);
    exit;
}

if($entityId>0){
    if($type==='streamer'){
        $st=$pdo->prepare("UPDATE streamers SET discord_id=?,discord_avatar=? WHERE id=?");
        $st->execute([$id,$avatar,$entityId]);
    }elseif($type==='staff'){
        $st=$pdo->prepare("UPDATE staff_users SET discord_id=?,discord_avatar=? WHERE id=?");
        $st->execute([$id,$avatar,$entityId]);
    }else{
        $st=$pdo->prepare("UPDATE site_team_members SET discord_id=?,discord_avatar=? WHERE id=?");
        $st->execute([$id,$avatar,$entityId]);
    }
}

echo json_encode([
    'success'=>true,
    'avatar'=>$avatar,
    'username'=>$data['username']??'',
    'global_name'=>$data['global_name']??''
]);
