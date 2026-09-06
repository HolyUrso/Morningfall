<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/discord.php';
require_once __DIR__ . '/relay_secret.php';

function discordRankingSetting(PDO $pdo, string $key, string $fallback = ''): string {
    try {
        $st = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
        $st->execute([$key]);
        $v = $st->fetchColumn();
        return ($v === false || trim((string)$v) === '') ? $fallback : trim((string)$v);
    } catch (Throwable $e) { return $fallback; }
}

function discordRankingSave(PDO $pdo, string $key, string $value, string $description = ''): void {
    $st = $pdo->prepare('SELECT COUNT(*) FROM settings WHERE setting_key=?');
    $st->execute([$key]);
    if ((int)$st->fetchColumn()) {
        $up = $pdo->prepare('UPDATE settings SET setting_value=?, description=? WHERE setting_key=?');
        $up->execute([$value,$description,$key]);
    } else {
        $ins = $pdo->prepare('INSERT INTO settings(setting_key,setting_value,description) VALUES(?,?,?)');
        $ins->execute([$key,$value,$description]);
    }
}

function discordRankingQualifyingDays(PDO $pdo, int $streamerId): array {
    $st=$pdo->prepare("SELECT DISTINCT vod_date FROM vods WHERE streamer_id=? AND duration_minutes>120 ORDER BY vod_date ASC");
    $st->execute([$streamerId]);
    return array_values(array_map('strval',$st->fetchAll(PDO::FETCH_COLUMN)));
}

function discordRankingSequence(PDO $pdo, int $streamerId, ?string $today=null): array {
    $today=$today ?: date('Y-m-d');
    $days=discordRankingQualifyingDays($pdo,$streamerId);
    if(!$days) return ['current'=>0,'best'=>0];
    $set=array_fill_keys($days,true); $best=0; $run=0; $prev=null;
    foreach($days as $day){
        if($prev!==null && (strtotime($day)-strtotime($prev))===86400) $run++; else $run=1;
        $best=max($best,$run); $prev=$day;
    }
    $current=0; $last=end($days);
    if($last===$today || $last===date('Y-m-d',strtotime($today.' -1 day'))){
        $current=1; $cursor=$last;
        while(isset($set[date('Y-m-d',strtotime($cursor.' -1 day'))])){ $cursor=date('Y-m-d',strtotime($cursor.' -1 day')); $current++; }
    }
    return ['current'=>$current,'best'=>$best];
}

function discordRankingLivesTop10(PDO $pdo): array {
    $rows=[];
    $q=$pdo->query("SELECT s.id,s.name,s.channel_url,COUNT(v.id) AS total_lives
                    FROM streamers s
                    INNER JOIN vods v ON v.streamer_id=s.id
                    WHERE s.active=1 AND v.duration_minutes>120
                    GROUP BY s.id,s.name,s.channel_url
                    HAVING total_lives>0
                    ORDER BY total_lives DESC, s.name ASC
                    LIMIT 10");
    foreach($q->fetchAll() as $s){
        $vod=$pdo->prepare("SELECT url,vod_date FROM vods WHERE streamer_id=? ORDER BY vod_date DESC, created_at DESC LIMIT 1");
        $vod->execute([(int)$s['id']]);
        $v=$vod->fetch();
        $rows[]=[
            'id'=>(int)$s['id'],
            'name'=>(string)$s['name'],
            'total_lives'=>(int)$s['total_lives'],
            'url'=>trim((string)($v['url'] ?? '')) !== '' ? trim((string)$v['url']) : trim((string)($s['channel_url'] ?? ''))
        ];
    }
    return $rows;
}

function discordRankingStreamerTop10(PDO $pdo): array {
    $rows=[];
    $q=$pdo->query("SELECT id,name,points,channel_url FROM streamers WHERE active=1 ORDER BY points DESC, name ASC LIMIT 10");
    foreach($q->fetchAll() as $s){
        $vod=$pdo->prepare("SELECT url,vod_date FROM vods WHERE streamer_id=? AND duration_minutes>0 ORDER BY vod_date DESC, created_at DESC LIMIT 1");
        $vod->execute([(int)$s['id']]);
        $v=$vod->fetch();
        $rows[]=['id'=>(int)$s['id'],'name'=>(string)$s['name'],'points'=>(int)$s['points'],'url'=>trim((string)($v['url'] ?? '')) !== '' ? trim((string)$v['url']) : trim((string)($s['channel_url'] ?? ''))];
    }
    return $rows;
}


function discordRankingWeeklyDaysTop10(PDO $pdo): array {
    $rows=[];
    $monday=date('Y-m-d', strtotime('monday this week'));
    $sunday=date('Y-m-d', strtotime('sunday this week'));
    $q=$pdo->query("SELECT s.id,s.name,s.channel_url,COUNT(DISTINCT v.vod_date) AS total_days
                    FROM streamers s
                    INNER JOIN vods v ON v.streamer_id=s.id
                    WHERE s.active=1
                      AND v.vod_date BETWEEN '".$monday."' AND '".$sunday."'
                    GROUP BY s.id,s.name,s.channel_url
                    HAVING total_days>0
                    ORDER BY total_days DESC, s.name ASC
                    LIMIT 10");
    foreach($q->fetchAll() as $s){
        $vod=$pdo->prepare("SELECT url,vod_date FROM vods WHERE streamer_id=? ORDER BY vod_date DESC, created_at DESC LIMIT 1");
        $vod->execute([(int)$s['id']]);
        $v=$vod->fetch();
        $rows[]=[
            'id'=>(int)$s['id'],
            'name'=>(string)$s['name'],
            'total_days'=>(int)$s['total_days'],
            'url'=>trim((string)($v['url'] ?? '')) !== '' ? trim((string)$v['url']) : trim((string)($s['channel_url'] ?? ''))
        ];
    }
    return $rows;
}

function discordRankingGlobalDaysTop10(PDO $pdo): array {
    $rows=[];
    $q=$pdo->query("SELECT s.id,s.name,s.channel_url,COUNT(DISTINCT v.vod_date) AS total_days
                    FROM streamers s
                    INNER JOIN vods v ON v.streamer_id=s.id
                    WHERE s.active=1
                    GROUP BY s.id,s.name,s.channel_url
                    HAVING total_days>0
                    ORDER BY total_days DESC, s.name ASC
                    LIMIT 10");
    foreach($q->fetchAll() as $s){
        $vod=$pdo->prepare("SELECT url,vod_date FROM vods WHERE streamer_id=? ORDER BY vod_date DESC, created_at DESC LIMIT 1");
        $vod->execute([(int)$s['id']]);
        $v=$vod->fetch();
        $rows[]=[
            'id'=>(int)$s['id'],
            'name'=>(string)$s['name'],
            'total_days'=>(int)$s['total_days'],
            'url'=>trim((string)($v['url'] ?? '')) !== '' ? trim((string)$v['url']) : trim((string)($s['channel_url'] ?? ''))
        ];
    }
    return $rows;
}

function discordRankingContentTop10(PDO $pdo): array {
    $rows=[];
    $q=$pdo->query("SELECT s.id,s.name,s.channel_url,COUNT(cs.id) AS total_content
                    FROM streamers s
                    INNER JOIN content_submissions cs ON cs.streamer_id=s.id
                    WHERE s.active=1
                      AND cs.status='approved'
                      AND cs.vod_id IS NULL
                    GROUP BY s.id,s.name,s.channel_url
                    HAVING total_content>0
                    ORDER BY total_content DESC, s.name ASC
                    LIMIT 10");
    foreach($q->fetchAll() as $s){
        $vod=$pdo->prepare("SELECT url,vod_date FROM vods WHERE streamer_id=? AND duration_minutes>0 ORDER BY vod_date DESC, created_at DESC LIMIT 1");
        $vod->execute([(int)$s['id']]);
        $v=$vod->fetch();
        $rows[]=[
            'id'=>(int)$s['id'],
            'name'=>(string)$s['name'],
            'total_content'=>(int)$s['total_content'],
            'url'=>trim((string)($v['url'] ?? '')) !== '' ? trim((string)$v['url']) : trim((string)($s['channel_url'] ?? ''))
        ];
    }
    return $rows;
}

function discordRankingHoursTop10(PDO $pdo): array {
    $rows=[];
    $q=$pdo->query("SELECT s.id,s.name,s.channel_url,COALESCE(SUM(v.duration_minutes),0) AS total_minutes
                    FROM streamers s
                    INNER JOIN vods v ON v.streamer_id=s.id
                    WHERE s.active=1 AND v.duration_minutes>0
                    GROUP BY s.id,s.name,s.channel_url
                    HAVING total_minutes>0
                    ORDER BY total_minutes DESC, s.name ASC
                    LIMIT 10");
    foreach($q->fetchAll() as $s){
        $vod=$pdo->prepare("SELECT url,vod_date FROM vods WHERE streamer_id=? AND duration_minutes>0 ORDER BY vod_date DESC, created_at DESC LIMIT 1");
        $vod->execute([(int)$s['id']]);
        $v=$vod->fetch();
        $rows[]=[
            'id'=>(int)$s['id'],
            'name'=>(string)$s['name'],
            'total_minutes'=>(int)$s['total_minutes'],
            'url'=>trim((string)($v['url'] ?? '')) !== '' ? trim((string)$v['url']) : trim((string)($s['channel_url'] ?? ''))
        ];
    }
    return $rows;
}


function discordRankingUpdate(PDO $pdo): array {
    $webhook=discordRankingSetting($pdo,'discord_ranking_webhook','');
    if($webhook==='') throw new RuntimeException('Webhook do ranking não configurada.');
    if(!filter_var($webhook,FILTER_VALIDATE_URL)) throw new RuntimeException('Webhook inválida.');

    $bottomImage=discordRankingSetting($pdo,'discord_ranking_bottom_image','');
    $messageId=discordRankingSetting($pdo,'discord_ranking_message_id','');
    $livesRows=discordRankingLivesTop10($pdo);
    $streamerRows=discordRankingStreamerTop10($pdo);
    $hoursRows=discordRankingHoursTop10($pdo);
    $contentRows=discordRankingContentTop10($pdo);
    $weeklyRows=discordRankingWeeklyDaysTop10($pdo);
    $globalRows=discordRankingGlobalDaysTop10($pdo);
    $medals=['🥇','🥈','🥉'];

    $makeLines=function(array $rows) use ($medals): array {
        $lines=[];
        foreach($rows as $i=>$r){
            $pos=$i+1; $label=$medals[$i]??($pos.'️⃣');
            $name=trim((string)$r['name']); $url=trim((string)($r['url']??''));
            $lines[]=$label.' '.($url!==''?'['.$name.']('.$url.')':$name);
        }
        return $lines;
    };

    $livesLines=$makeLines($livesRows);
    $streamerLines=$makeLines($streamerRows);
    $hoursLines=$makeLines($hoursRows);
    $contentLines=$makeLines($contentRows);
    $weeklyLines=$makeLines($weeklyRows);
    $globalLines=$makeLines($globalRows);

    foreach($livesLines as $i => $line){
        if(isset($livesRows[$i])) $livesLines[$i] .= ' — **'.(int)$livesRows[$i]['total_lives'].' Lives**';
    }
    foreach($hoursLines as $i => $line){
        if(isset($hoursRows[$i])) {
            $minutes=(int)$hoursRows[$i]['total_minutes'];
            $h=intdiv($minutes,60); $m=$minutes%60;
            $hoursLines[$i] .= ' — **'.$h.'h '.str_pad((string)$m,2,'0',STR_PAD_LEFT).'min**';
        }
    }
    foreach($contentLines as $i => $line){
        if(isset($contentRows[$i])) $contentLines[$i] .= ' — **'.(int)$contentRows[$i]['total_content'].' conteúdos**';
    }
    foreach($weeklyLines as $i => $line){
        if(isset($weeklyRows[$i])) $weeklyLines[$i] .= ' — **'.(int)$weeklyRows[$i]['total_days'].' dias**';
    }
    foreach($globalLines as $i => $line){
        if(isset($globalRows[$i])) $globalLines[$i] .= ' — **'.(int)$globalRows[$i]['total_days'].' dias**';
    }
    if(!$livesLines) $livesLines[]='Ainda não há Lives válidas registradas.';
    if(!$weeklyLines) $weeklyLines[]='Ainda não há dias de Live nesta semana.';
    if(!$globalLines) $globalLines[]='Ainda não há dias de Live registrados.';
    if(!$streamerLines) $streamerLines[]='Ainda não há streamers cadastrados.';
    if(!$hoursLines) $hoursLines[]='Ainda não há horas de live registradas.';
    if(!$contentLines) $contentLines[]='Ainda não há conteúdos aprovados registrados.';

    $footer=['text'=>'Carmesim Creators • Atualizado automaticamente a cada 1 hora'];

    $payload=['embeds'=>[
        [
            'title'=>'🏆 CARMESIM CREATORS',
            'description'=>"*Ranking geral dos criadores com melhor desempenho no Carmesim Creators.*\n\n👑 **Ranking de Streamers**\n\n".implode("\n",$streamerLines),
            'color'=>5793266,
            'footer'=>$footer,
            'timestamp'=>gmdate('c')
        ],
        [
            'title'=>'⏱️ Ranking de Horas',
            'description'=>"*Total de tempo acumulado em Lives/VODs registradas.*\n\n".implode("\n",$hoursLines),
            'color'=>3447003,
            'footer'=>$footer
        ],
        [
            'title'=>'🔥 Histórico de Lives',
            'description'=>"*Quantidade de dias com Lives realizadas durante a semana. Várias Lives no mesmo dia contam como apenas 1 dia.*\n\n".implode("\n",$weeklyLines),
            'color'=>16753920,
            'footer'=>$footer
        ],
        [
            'title'=>'🌎 Ranking Global',
            'description'=>"*Histórico de todos os dias em que o streamer realizou Lives. Várias Lives no mesmo dia contam como apenas 1 dia.*\n\n".implode("\n",$globalLines),
            'color'=>7506394,
            'footer'=>$footer
        ],
        [
            'title'=>'🎬 Ranking de Conteúdo',
            'description'=>"*Quantidade de conteúdos extras aprovados, sem contar Lives/VODs.*\n\n".implode("\n",$contentLines),
            'color'=>10181046,
            'footer'=>$footer
        ]
    ]];

    if(!function_exists('curl_init')) throw new RuntimeException('cURL não está habilitado no PHP do servidor.');
    $relaySecret=defined('MORNINGFALL_RELAY_SECRET') ? trim((string)MORNINGFALL_RELAY_SECRET) : '';
    if($relaySecret==='') throw new RuntimeException('RELAY_SECRET não configurado no projeto.');
    $relayBase=rtrim((defined('DISCORD_RELAY_URL')?DISCORD_RELAY_URL:''),'/');
    if($relayBase==='') throw new RuntimeException('Cloudflare Relay URL não configurada.');

    // A imagem grande é enviada pelo Worker do Cloudflare como anexo do próprio
    // webhook. Assim não dependemos do Discord conseguir baixar uma imagem do InfinityFree.
    $relayPayload=$payload;
    $relayPayload['webhook_url']=$webhook;
    $relayPayload['wait']=true;
    $relayPayload['message_id']=$messageId;
    if($bottomImage!=='') $relayPayload['bottom_image_url']=$bottomImage;

    $json=json_encode($relayPayload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $ch=curl_init($relayBase.'/send');
    curl_setopt_array($ch,[
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>$json,
        CURLOPT_HTTPHEADER=>[
            'Content-Type: application/json',
            'X-Relay-Secret: '.$relaySecret,
            'User-Agent: Carmesim-Creators-Ranking/2.0'
        ],
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_TIMEOUT=>40,
        CURLOPT_CONNECTTIMEOUT=>10,
        CURLOPT_SSL_VERIFYPEER=>true,
        CURLOPT_SSL_VERIFYHOST=>2
    ]);
    $body=curl_exec($ch); $err=curl_error($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); curl_close($ch);
    if($err!=='') throw new RuntimeException('Não foi possível conectar ao Cloudflare Relay. cURL: '.$err);
    $decoded=json_decode((string)$body,true);
    if($code<200 || $code>=300 || !is_array($decoded) || empty($decoded['success'])){
        $detail=is_array($decoded)?(string)($decoded['error']??$decoded['details']??''):trim((string)$body);
        throw new RuntimeException('Cloudflare Relay recusou o ranking (HTTP '.$code.').'.($detail?' '.$detail:''));
    }
    $newId=(string)($decoded['message_id']??$decoded['id']??($decoded['message']['id']??$messageId));
    if($newId!=='') discordRankingSave($pdo,'discord_ranking_message_id',$newId,'ID da mensagem do ranking no Discord.');
    $mode=(string)($decoded['action']??($messageId!==''?'updated':'created'));
    return ['message_id'=>$newId,'top_count'=>count($streamerRows),'mode'=>$mode];
}
?>
