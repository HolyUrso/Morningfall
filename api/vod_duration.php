<?php
require_once '../config/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success'=>false,'message'=>'Método não permitido.']);
    exit;
}

$url=trim($_POST['url']??'');
$parts=parse_url($url);
$host=strtolower($parts['host']??'');
$host=preg_replace('/^www\./','',$host);

$allowed=false;
if ($host==='youtube.com' || $host==='youtu.be' || $host==='m.youtube.com') $allowed=true;
if ($host==='twitch.tv' || $host==='m.twitch.tv') $allowed=true;

if (!$allowed) {
    http_response_code(422);
    echo json_encode(['success'=>false,'message'=>'Use uma VOD pública do YouTube ou Twitch.']);
    exit;
}

function httpGet(string $url): string {
    if (function_exists('curl_init')) {
        $ch=curl_init($url);
        curl_setopt_array($ch,[
            CURLOPT_RETURNTRANSFER=>true,
            CURLOPT_FOLLOWLOCATION=>true,
            CURLOPT_MAXREDIRS=>3,
            CURLOPT_CONNECTTIMEOUT=>8,
            CURLOPT_TIMEOUT=>15,
            CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/124 Safari/537.36',
            CURLOPT_HTTPHEADER=>['Accept-Language: pt-BR,pt;q=0.9,en;q=0.8'],
        ]);
        $body=curl_exec($ch);
        $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body!==false && $code>=200 && $code<400) return $body;
    } else {
        $ctx=stream_context_create(['http'=>['timeout'=>15,'header'=>"User-Agent: Mozilla/5.0\r\nAccept-Language: pt-BR,pt;q=0.9,en;q=0.8\r\n"]]);
        $body=@file_get_contents($url,false,$ctx);
        if ($body!==false) return $body;
    }
    return '';
}

function secondsToHm(int $seconds): string {
    $seconds=max(0,$seconds);
    $hours=intdiv($seconds,3600);
    $minutes=intdiv($seconds%3600,60);
    return sprintf('%02d:%02d',$hours,$minutes);
}

$html=httpGet($url);
if ($html==='') {
    http_response_code(502);
    echo json_encode(['success'=>false,'message'=>'Não foi possível acessar a VOD. Verifique se o link é público.']);
    exit;
}

$seconds=null;

/* YouTube: o HTML costuma expor lengthSeconds e/ou itemprop duration. */
if ($host==='youtube.com' || $host==='youtu.be' || $host==='m.youtube.com') {
    if (preg_match('/"lengthSeconds":"(\d+)"/',$html,$m)) {
        $seconds=(int)$m[1];
    } elseif (preg_match('/itemprop=["\']duration["\'][^>]+content=["\']PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?["\']/i',$html,$m)) {
        $seconds=((int)($m[1]??0)*3600)+((int)($m[2]??0)*60)+(int)($m[3]??0);
    } elseif (preg_match('/content=["\']PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?["\'][^>]+itemprop=["\']duration["\']/i',$html,$m)) {
        $seconds=((int)($m[1]??0)*3600)+((int)($m[2]??0)*60)+(int)($m[3]??0);
    }
}

/* Twitch: tenta lengthSeconds e formatos de duração presentes no JSON da página. */
if ($seconds===null && ($host==='twitch.tv' || $host==='m.twitch.tv')) {
    if (preg_match('/"lengthSeconds":\s*(\d+)/',$html,$m)) {
        $seconds=(int)$m[1];
    } elseif (preg_match('/"duration":\s*"(?:(\d+)h)?(?:(\d+)m)?(?:(\d+)s)?"/i',$html,$m)) {
        $seconds=((int)($m[1]??0)*3600)+((int)($m[2]??0)*60)+(int)($m[3]??0);
    } elseif (preg_match('/"duration":\s*"(\d+):(\d+):(\d+)"/',$html,$m)) {
        $seconds=((int)$m[1]*3600)+((int)$m[2]*60)+(int)$m[3];
    }
}

if ($seconds===null || $seconds<=0) {
    http_response_code(422);
    echo json_encode(['success'=>false,'message'=>'A duração não foi encontrada automaticamente. Algumas VODs/plataformas bloqueiam essa leitura. Informe HH:MM manualmente.']);
    exit;
}

/* O sistema trabalha com minutos inteiros; segundos são arredondados para cima. */
$minutes=(int)ceil($seconds/60);

echo json_encode([
    'success'=>true,
    'duration_minutes'=>$minutes,
    'duration_hm'=>sprintf('%02d:%02d',intdiv($minutes,60),$minutes%60)
]);
