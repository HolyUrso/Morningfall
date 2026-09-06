<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';
require_once '../config/relay_secret.php';

$error=''; $success=''; $drawResults=[];

function getRaffleSetting(PDO $pdo,string $key,$default=null){
    $st=$pdo->prepare("SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1");
    $st->execute([$key]); $v=$st->fetchColumn();
    return $v===false?$default:$v;
}
function syncRaffleEligible(PDO $pdo, int $raffleId, ?string $referenceDate): int {
    if (!$referenceDate) return 0;
    $month=date('Y-m',strtotime($referenceDate));
    $start=$month.'-01'; $end=date('Y-m-t',strtotime($start));
    $target=max(0,(int)getRaffleSetting($pdo,'monthly_live_days_target',20));
$monthlyBonus=max(0,(int)getRaffleSetting($pdo,'monthly_live_days_bonus',50));
    $sql="SELECT s.id, COUNT(DISTINCT v.vod_date) AS days
          FROM streamers s JOIN vods v ON v.streamer_id=s.id
          WHERE s.active=1 AND v.vod_date BETWEEN ? AND ?
          GROUP BY s.id HAVING COUNT(DISTINCT v.vod_date) >= ?";
    $st=$pdo->prepare($sql); $st->execute([$start,$end,$target]); $eligible=$st->fetchAll();
    $ins=$pdo->prepare("INSERT INTO raffle_entries(raffle_id,streamer_id,eligibility_days,month_reference)
                        VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE eligibility_days=VALUES(eligibility_days),month_reference=VALUES(month_reference)");
    foreach($eligible as $row) $ins->execute([$raffleId,(int)$row['id'],(int)$row['days'],$month]);
    $ids=array_map(fn($r)=>(int)$r['id'],$eligible);
    if($ids){
        $ph=implode(',',array_fill(0,count($ids),'?'));
        $params=array_merge([$raffleId],$ids);
        // Remove somente inscrições AUTOMÁTICAS que deixaram de cumprir a meta.
        // eligibility_days = 0 identifica inclusão manual e deve ser preservada.
        $params2=array_merge([$raffleId],$ids);
        $del=$pdo->prepare("DELETE FROM raffle_entries WHERE raffle_id=? AND eligibility_days>0 AND streamer_id NOT IN ($ph)");
        $del->execute($params2);
    } else {
        // Sem elegíveis automáticos: preserve todos os participantes manuais.
        $pdo->prepare("DELETE FROM raffle_entries WHERE raffle_id=? AND eligibility_days>0")->execute([$raffleId]);
    }
    return count($eligible);
}
function sendDiscordWebhook(string $url, string $title, string $description, array $fields=[]): array {
    // InfinityFree -> Cloudflare Relay -> Discord
    $relayUrl = 'https://morningfall-relay.santourso.workers.dev/send';

    // O secret deve ser o MESMO RELAY_SECRET configurado no Cloudflare.
    $relaySecret = defined('MORNINGFALL_RELAY_SECRET') ? MORNINGFALL_RELAY_SECRET : '';

    if ($relaySecret === '') {
        return ['ok'=>false,'code'=>0,'error'=>'MORNINGFALL_RELAY_SECRET não configurado no projeto.'];
    }

    if($url==='' || !filter_var($url,FILTER_VALIDATE_URL)){
        return ['ok'=>false,'code'=>0,'error'=>'Webhook vazia ou URL inválida.'];
    }

    $payload = [
        'webhook_url' => $url,
        'embeds' => [[
            'title'=>$title,
            'description'=>$description,
            'color'=>0x7c5cff,
            'fields'=>$fields,
            'footer'=>['text'=>'Morningfall Creators V1 • luciferms666'],
            'timestamp'=>gmdate('c')
        ]]
    ];

    $json=json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

    if(!function_exists('curl_init')){
        return ['ok'=>false,'code'=>0,'error'=>'cURL não está habilitado no PHP do servidor.'];
    }

    $ch=curl_init($relayUrl);
    curl_setopt_array($ch,[
        CURLOPT_POST=>true,
        CURLOPT_POSTFIELDS=>$json,
        CURLOPT_HTTPHEADER=>[
            'Content-Type: application/json',
            'X-Relay-Secret: '.$relaySecret,
            'User-Agent: Morningfall-Creators-PHP/1.0'
        ],
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_TIMEOUT=>15,
        CURLOPT_CONNECTTIMEOUT=>8,
        CURLOPT_SSL_VERIFYPEER=>true,
        CURLOPT_SSL_VERIFYHOST=>2
    ]);

    $body=curl_exec($ch);
    $errno=curl_errno($ch);
    $err=$errno ? curl_error($ch) : '';
    $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($errno){
        return ['ok'=>false,'code'=>$code,'error'=>"Relay cURL #{$errno}: {$err}"];
    }

    $responseData=json_decode((string)$body,true);

    if($code>=200 && $code<300 && !empty($responseData['success'])){
        return ['ok'=>true,'code'=>$code,'error'=>''];
    }

    $detail='';
    if(is_array($responseData)){
        $detail=$responseData['error'] ?? ($responseData['details'] ?? '');
        if(isset($responseData['discord_status'])){
            $detail .= ($detail?' ':'').'Discord HTTP '.$responseData['discord_status'];
        }
    }

    return ['ok'=>false,'code'=>$code,'error'=>$detail ?: ('Relay HTTP '.$code)];
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $action=$_POST['action']??'';
        if($action==='save'){
            $title=trim($_POST['title']??''); $description=trim($_POST['description']??''); $prize='+'.max(0,(int)getRaffleSetting($pdo,'monthly_live_days_bonus',50)).' pontos extras';
            $drawDate=trim($_POST['draw_date']??'')?:null; $drawTime=trim($_POST['draw_time']??'')?:null;
            $winners=max(1,min(100,(int)($_POST['winners_count']??1))); $eligibility=trim($_POST['eligibility_text']??'');
            $webhook=trim($_POST['webhook_url']??''); $active=isset($_POST['active'])?1:0;
            if($title==='') throw new RuntimeException('Informe o nome do sorteio.');
            if($active && !$drawDate) throw new RuntimeException('Informe a data do sorteio para calcular os elegíveis.');
            if($active) $pdo->exec("UPDATE raffles SET active=0");
            $stmt=$pdo->prepare("INSERT INTO raffles(title,description,prize,draw_date,draw_time,winners_count,entry_points,eligibility_text,active,created_by,webhook_url)
                                 VALUES(?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$title,$description,$prize,$drawDate,$drawTime,$winners,0,$eligibility,$active,(int)$_SESSION['staff_id'],$webhook]);
            $id=(int)$pdo->lastInsertId(); $count=syncRaffleEligible($pdo,$id,$drawDate);
            $success="Sorteio salvo. {$count} streamer(s) elegível(is) no momento.";
        } elseif($action==='test_webhook'){
            $url=trim($_POST['webhook_url']??'');
            if($url==='') throw new RuntimeException('Informe a webhook do sorteio antes de testar.');
            $test=sendDiscordWebhook($url,'🔔 Teste de Webhook do Sorteio',
                'A webhook do sorteio está configurada e funcionando.',
                [['name'=>'Ação','value'=>'Teste realizado pela Staff','inline'=>false]]);
            if($test['ok']) $success='Webhook testada com sucesso via Cloudflare Relay! HTTP '.$test['code'].'.';
            else $error='Falha no teste da webhook via Cloudflare Relay: '.$test['error'].' HTTP: '.$test['code'];
        } elseif($action==='sync'){
            $id=(int)$_POST['raffle_id']; $r=$pdo->prepare("SELECT * FROM raffles WHERE id=?"); $r->execute([$id]); $raffle=$r->fetch();
            if(!$raffle) throw new RuntimeException('Sorteio não encontrado.');
            $count=syncRaffleEligible($pdo,$id,$raffle['draw_date']); $success="Lista atualizada: {$count} elegível(is).";
        } elseif($action==='add_manual'){
            $raffleId=(int)$_POST['raffle_id'];
            $streamerId=(int)$_POST['streamer_id'];
            $r=$pdo->prepare("SELECT * FROM raffles WHERE id=?");
            $r->execute([$raffleId]); $raffle=$r->fetch();
            if(!$raffle) throw new RuntimeException('Sorteio não encontrado.');
            if(!empty($raffle['drawn_at'])) throw new RuntimeException('Este sorteio já foi realizado.');
            $st=$pdo->prepare("SELECT id,name,active FROM streamers WHERE id=?");
            $st->execute([$streamerId]); $streamer=$st->fetch();
            if(!$streamer) throw new RuntimeException('Streamer não encontrado.');
            $month=$raffle['draw_date'] ? date('Y-m',strtotime($raffle['draw_date'])) : date('Y-m');
            $exists=$pdo->prepare("SELECT eligibility_days FROM raffle_entries WHERE raffle_id=? AND streamer_id=?");
            $exists->execute([$raffleId,$streamerId]);
            $existing=$exists->fetchColumn();
            if($existing!==false && (int)$existing>0){
                $success="{$streamer['name']} já estava no sorteio por elegibilidade automática.";
            } else {
                $ins=$pdo->prepare("INSERT INTO raffle_entries(raffle_id,streamer_id,eligibility_days,month_reference)
                                    VALUES(?,?,0,?)
                                    ON DUPLICATE KEY UPDATE eligibility_days=0,month_reference=VALUES(month_reference)");
                $ins->execute([$raffleId,$streamerId,$month]);
                $success="{$streamer['name']} foi adicionado manualmente ao sorteio.";
            }
        } elseif($action==='remove_manual'){
            $raffleId=(int)$_POST['raffle_id'];
            $streamerId=(int)$_POST['streamer_id'];
            $r=$pdo->prepare("SELECT drawn_at FROM raffles WHERE id=?");
            $r->execute([$raffleId]); $raffle=$r->fetch();
            if(!$raffle) throw new RuntimeException('Sorteio não encontrado.');
            $st=$pdo->prepare("SELECT name FROM streamers WHERE id=? LIMIT 1");
            $st->execute([$streamerId]); $streamer=$st->fetch();
            if(!$streamer) throw new RuntimeException('Streamer não encontrado.');
            $del=$pdo->prepare("DELETE FROM raffle_entries WHERE raffle_id=? AND streamer_id=? AND eligibility_days=0");
            $del->execute([$raffleId,$streamerId]);
            if($del->rowCount() > 0){
                $success="A inclusão manual de {$streamer['name']} foi removida do sorteio.";
            } else {
                $success="Nenhuma inclusão manual de {$streamer['name']} foi encontrada para remover.";
            }
        } elseif($action==='draw'){
            $id=(int)$_POST['raffle_id'];
            $st=$pdo->prepare("SELECT * FROM raffles WHERE id=? FOR UPDATE");
            $pdo->beginTransaction(); $st->execute([$id]); $raffle=$st->fetch();
            if(!$raffle) throw new RuntimeException('Sorteio não encontrado.');
            if(!empty($raffle['drawn_at'])) throw new RuntimeException('Este sorteio já foi realizado e não pode ser sorteado novamente.');
            syncRaffleEligible($pdo,$id,$raffle['draw_date']);
            $q=$pdo->prepare("SELECT re.streamer_id,re.eligibility_days,s.name FROM raffle_entries re JOIN streamers s ON s.id=re.streamer_id WHERE re.raffle_id=? ORDER BY RAND()");
            $q->execute([$id]); $participants=$q->fetchAll();
            if(!$participants) throw new RuntimeException('Não há participantes elegíveis para realizar o sorteio.');
            $n=min((int)$raffle['winners_count'],count($participants));
            $winners=array_slice($participants,0,$n);

            // Meta mensal não dá pontos automaticamente. O prêmio é pago somente aos sorteados.
            $monthlyBonusDraw=max(0,(int)getRaffleSetting($pdo,'monthly_live_days_bonus',50));
            if($monthlyBonusDraw>0){
                $checkBonus=$pdo->prepare("SELECT COUNT(*) FROM point_transactions WHERE streamer_id=? AND reference_type='raffle_monthly_bonus' AND reference_id=?");
                $addTx=$pdo->prepare("INSERT INTO point_transactions(streamer_id,type,description,points,reference_type,reference_id,created_by) VALUES(?,'earn',?,?,?,?,?)");
                $addPts=$pdo->prepare("UPDATE streamers SET points=points+? WHERE id=?");
                foreach($winners as $winner){
                    $checkBonus->execute([(int)$winner['streamer_id'],$id]);
                    if((int)$checkBonus->fetchColumn()===0){
                        $addTx->execute([(int)$winner['streamer_id'],'Sorteio da meta mensal: +'.$monthlyBonusDraw.' pontos extras',$monthlyBonusDraw,'raffle_monthly_bonus',$id,(int)$_SESSION['staff_id']]);
                        $addPts->execute([$monthlyBonusDraw,(int)$winner['streamer_id']]);
                    }
                }
            }

            $ins=$pdo->prepare("INSERT INTO raffle_winners(raffle_id,streamer_id,draw_number) VALUES(?,?,?)");
            foreach($winners as $i=>$w) $ins->execute([$id,(int)$w['streamer_id'],$i+1]);
            $pdo->prepare("UPDATE raffles SET drawn_at=NOW(),active=0 WHERE id=?")->execute([$id]);
            $pdo->commit();
            $drawResults=$winners;
            $webhookResult=['ok'=>false,'code'=>0,'error'=>'Nenhuma webhook configurada.'];
            if(!empty($raffle['webhook_url'])){
                $names=array_map(fn($w)=>$w['name'],$winners);
                $webhookResult=sendDiscordWebhook($raffle['webhook_url'],'🎟️ SORTEIO REALIZADO',
                    "O sorteio **".($raffle['title'])."** foi realizado com sucesso.",
                    [
                        ['name'=>'🎁 Prêmio','value'=>(string)$raffle['prize'],'inline'=>false],
                        ['name'=>'🏆 Ganhador(es)','value'=>implode("\n",$names),'inline'=>false],
                        ['name'=>'📅 Data','value'=>(string)($raffle['draw_date']?:date('Y-m-d')),'inline'=>true],
                        ['name'=>'⏰ Horário','value'=>(string)($raffle['draw_time']?substr($raffle['draw_time'],0,5):date('H:i')),'inline'=>true]
                    ]);
            }
            if($webhookResult['ok']){
                $success='🎉 Sorteio realizado com sucesso e enviado para o Discord! (HTTP '.$webhookResult['code'].')';
            } else {
                $success='🎉 Sorteio realizado e salvo no sistema, porém a webhook NÃO foi enviada. Motivo: '.$webhookResult['error'].' HTTP: '.$webhookResult['code'];
            }
        }
    }catch(Throwable $e){
        if($pdo->inTransaction()) $pdo->rollBack();
        $error=$e->getMessage();
    }
}

$current=$pdo->query("SELECT * FROM raffles ORDER BY id DESC LIMIT 1")->fetch();
if($current && empty($current['drawn_at'])) syncRaffleEligible($pdo,(int)$current['id'],$current['draw_date']);
$target=max(0,(int)getRaffleSetting($pdo,'monthly_live_days_target',20));
$monthlyBonus=max(0,(int)getRaffleSetting($pdo,'monthly_live_days_bonus',50));
$entries=[];
if($current){
    $q=$pdo->prepare("SELECT re.*,s.name,s.category FROM raffle_entries re JOIN streamers s ON s.id=re.streamer_id WHERE re.raffle_id=? ORDER BY re.eligibility_days DESC,s.name");
    $q->execute([(int)$current['id']]); $entries=$q->fetchAll();
}
$winners=[];
if($current){
    $q=$pdo->prepare("SELECT rw.*,s.name FROM raffle_winners rw JOIN streamers s ON s.id=rw.streamer_id WHERE rw.raffle_id=? ORDER BY rw.draw_number");
    $q->execute([(int)$current['id']]); $winners=$q->fetchAll();
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configurar Sorteio</title><link rel="stylesheet" href="../assets/css/style.css">
<style>
.raffle-form{max-width:1000px}.form-card{background:#111722;border:1px solid #293448;border-radius:14px;padding:22px;margin-top:20px}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}.full{grid-column:1/-1}.form-card label{display:block;font-weight:700;margin-bottom:7px}
.form-card input,.form-card textarea{width:100%;box-sizing:border-box;background:#0b1018;border:1px solid #354158;color:#fff;border-radius:9px;padding:12px}.form-card textarea{min-height:110px;resize:vertical}.form-card small{display:block;color:#8f9bb3;margin-top:6px}
.switch{display:flex;gap:10px;align-items:center;margin-top:10px}.switch input{width:auto}.alert{padding:14px;border-radius:10px;margin:18px 0}.ok{background:#153c2b;border:1px solid #2f8b62;color:#bff5d9}.err{background:#421f27;border:1px solid #7d3a47;color:#ffd0d8}
.table-wrap{overflow:auto;margin-top:20px}.table{width:100%;border-collapse:collapse}.table th,.table td{padding:11px;border-bottom:1px solid #293448;text-align:left}.badge{display:inline-block;padding:4px 9px;border-radius:999px;background:#253047}.active-badge{background:#174b35;color:#bff5d9}.winner{background:#3d2e0b;color:#ffe7a3}.danger{background:#5b2630!important;border-color:#8a3a48!important}.actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.danger{background:#5b2630!important;border-color:#8a3a48!important}.draw-box{border:1px solid #5b4a18;background:#19170d;border-radius:14px;padding:20px;margin-top:20px}.draw-box h2{margin-top:0}.winner-list{font-size:18px;line-height:1.8}
@media(max-width:720px){.form-grid{grid-template-columns:1fr}.full{grid-column:auto}}
</style></head><body>
<header class="topbar"><div><b>MORNINGFALL</b> <span>CREATORS</span></div><div><?=htmlspecialchars($_SESSION['staff_name'])?> · <a href="../logout.php">Sair</a></div></header>
<main class="container">
<div class="page-head"><div><a href="index.php">← Dashboard</a><p class="eyebrow">EVENTOS</p><h1>🎟️ Configurar Sorteio</h1><p class="muted">Os streamers que atingirem a meta mensal entram automaticamente.</p></div></div>
<?php if($success):?><div class="alert ok">✅ <?=htmlspecialchars($success)?></div><?php endif;?>
<?php if($error):?><div class="alert err">❌ <?=htmlspecialchars($error)?></div><?php endif;?>
<?php if($drawResults):?><div class="draw-box"><h2>🎉 Resultado do sorteio</h2><div class="winner-list"><?php foreach($drawResults as $i=>$w):?><div>🏆 <strong><?=($i+1)?>º — <?=htmlspecialchars($w['name'])?></strong></div><?php endforeach;?></div></div><?php endif;?>
<div class="form-card"><h2>Configuração do sorteio</h2>
<form method="post"><input type="hidden" name="action" value="save"><div class="form-grid">
<div><label>Nome do sorteio</label><input name="title" value="<?=htmlspecialchars($current['title']??'')?>" required></div>
<div>
<label>Prêmio do sorteio</label>
<input value="+<?=number_format($monthlyBonus,0,',','.')?> pontos extras" readonly>
<input type="hidden" name="prize" value="+<?=htmlspecialchars($monthlyBonus,ENT_QUOTES)?> pontos extras">
<small>Valor controlado pelo Setup → Meta Mensal → Bônus da meta mensal. O valor pago ao(s) vencedor(es) é o configurado no momento do sorteio.</small>
</div>
<div class="full"><label>Descrição</label><textarea name="description"><?=htmlspecialchars($current['description']??'')?></textarea></div>
<div><label>Data</label><input type="date" name="draw_date" value="<?=htmlspecialchars($current['draw_date']??'')?>"></div>
<div><label>Horário</label><input type="time" name="draw_time" value="<?=htmlspecialchars(substr((string)($current['draw_time']??''),0,5))?>"></div>
<div><label>Número de ganhadores</label><input type="number" name="winners_count" min="1" max="100" value="<?=htmlspecialchars($current['winners_count']??1)?>"></div>
<div><label>Webhook do sorteio</label>
<input name="webhook_url" value="<?=htmlspecialchars($current['webhook_url']??'')?>" placeholder="https://discord.com/api/webhooks/...">
<?php if($current && !empty($current['webhook_url'])): ?>
<div style="margin-top:8px"><button class="btn" type="submit" formaction="" name="action" value="test_webhook">🧪 Testar webhook</button></div>
<?php endif; ?>
<small>Use a webhook exclusiva para o canal de resultados dos sorteios.</small></div>
<div class="full"><label>Regras adicionais</label><textarea name="eligibility_text"><?=htmlspecialchars($current['eligibility_text']??'')?></textarea></div>
<div class="full switch"><input type="checkbox" id="active" name="active" <?=!empty($current['active'])&&!$current['drawn_at']?'checked':''?>><label for="active" style="margin:0">Sorteio ativo</label></div>
</div><div style="margin-top:20px"><button class="btn primary" type="submit" <?=!empty($current['drawn_at'])?'disabled':''?>>💾 Salvar configuração</button></div></form></div>

<div class="form-card">
<h2>➕ Adicionar participante manualmente</h2>
<p class="muted">Mesmo que o streamer não tenha atingido os <?= $target ?> dias, a Staff pode incluí-lo excepcionalmente neste sorteio. A inclusão manual fica registrada e não altera a pontuação nem a meta do streamer.</p>
<form method="post" class="actions">
<input type="hidden" name="action" value="add_manual">
<input type="hidden" name="raffle_id" value="<?= (int)($current['id']??0) ?>">
<select name="streamer_id" required style="background:#0b1018;border:1px solid #354158;color:#fff;border-radius:9px;padding:12px;min-width:280px" <?=!$current||!empty($current['drawn_at'])?'disabled':''?>>
<option value="">Selecione um streamer...</option>
<?php
$allStreamers=$pdo->query("SELECT id,name,category,active FROM streamers ORDER BY name")->fetchAll();
foreach($allStreamers as $st): ?>
<option value="<?= (int)$st['id'] ?>"><?=htmlspecialchars($st['name'])?><?=!$st['active']?' — INATIVO':''?></option>
<?php endforeach; ?>
</select>
<button class="btn" type="submit" <?=!$current||!empty($current['drawn_at'])?'disabled':''?>>➕ Adicionar ao sorteio</button>
</form>
</div>

<div class="form-card"><h2>👥 Participantes do sorteio</h2><p class="muted">A lista contém elegíveis automáticos + inclusões manuais. Meta: <strong><?= $target ?> dias distintos</strong>. Total elegível: <strong><?=count($entries)?></strong>.</p>
<div class="actions"><form method="post"><input type="hidden" name="action" value="sync"><input type="hidden" name="raffle_id" value="<?= (int)($current['id']??0) ?>"><button class="btn" type="submit" <?=!$current||!empty($current['drawn_at'])?'disabled':''?>>🔄 Atualizar participantes</button></form>
<?php if($current && empty($current['drawn_at'])):?><form method="post" onsubmit="return confirm('Tem certeza? O sorteio será realizado uma única vez e não poderá ser repetido.');"><input type="hidden" name="action" value="draw"><input type="hidden" name="raffle_id" value="<?= (int)$current['id'] ?>"><button class="btn primary" type="submit">🎲 SORTEAR AGORA</button></form><?php endif;?></div>
<div class="table-wrap"><table class="table"><thead><tr><th>#</th><th>Streamer</th><th>Dias</th><th>Status</th><th>Ações</th></tr></thead><tbody>
<?php foreach($entries as $i=>$r):?>
<tr>
<td><?=$i+1?></td>
<td><strong><?=htmlspecialchars($r['name'])?></strong></td>
<td><?= (int)$r['eligibility_days']>0 ? ((int)$r['eligibility_days'].'/'.$target) : 'Inclusão manual' ?></td>
<td>
<?php if((int)$r['eligibility_days']>0): ?><span class="badge">ELEGÍVEL</span>
<?php else: ?><span class="badge winner">MANUAL</span><?php endif; ?>
</td>
<td>
<?php if($current && (int)$r['eligibility_days']===0): ?>
<form method="post" style="display:inline" onsubmit="return confirm('Retirar a inclusão manual de <?=htmlspecialchars($r['name'], ENT_QUOTES, 'UTF-8')?> deste participante do sorteio?');">
<input type="hidden" name="action" value="remove_manual">
<input type="hidden" name="raffle_id" value="<?= (int)$current['id'] ?>">
<input type="hidden" name="streamer_id" value="<?= (int)$r['streamer_id'] ?>">
<button class="btn danger" type="submit">🗑️ Retirar inclusão manual</button>
</form>
<?php else: ?>—
<?php endif; ?>
</td>
</tr>
<?php endforeach;?>
<?php if(!$entries):?><tr><td colspan="5">Nenhum streamer elegível ainda.</td></tr><?php endif;?></tbody></table></div></div>

<?php if($current && !empty($current['drawn_at'])):?><div class="draw-box"><h2>🏆 Sorteio já realizado</h2><p>Realizado em <?=htmlspecialchars($current['drawn_at'])?>.</p><div class="winner-list"><?php foreach($winners as $i=>$w):?><div>🏆 <?=($i+1)?>º — <strong><?=htmlspecialchars($w['name'])?></strong></div><?php endforeach;?></div></div><?php endif;?>

<footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
</main></body></html>
