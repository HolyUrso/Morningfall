<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';
require_once '../config/streamer_webhook.php';

$error='';
$ok='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    $sid=(int)($_POST['streamer_id']??0);
    $points=(int)($_POST['points']??0);
    $reason=trim($_POST['reason']??'');
    $evidence=trim($_POST['evidence_url']??'');

    if($action!=='adjust') $error='Ação inválida.';
    elseif($sid<=0) $error='Selecione um streamer.';
    elseif($points===0) $error='Informe uma quantidade de pontos diferente de zero.';
    elseif($reason==='') $error='Informe o motivo da alteração.';
    elseif($evidence!=='' && !filter_var($evidence,FILTER_VALIDATE_URL)) $error='O link/evidência informado é inválido.';
    else{
        try{
            $pdo->beginTransaction();

            $st=$pdo->prepare("SELECT * FROM streamers WHERE id=? AND active=1 FOR UPDATE");
            $st->execute([$sid]);
            $streamer=$st->fetch();
            if(!$streamer) throw new RuntimeException('Streamer não encontrado.');

            $current=(int)$streamer['points'];
            $new=max(0,$current+$points);
            $applied=$new-$current;

            if($applied===0){
                throw new RuntimeException('A pontuação não pode ficar abaixo de zero. O saldo atual é '.$current.' pontos.');
            }

            $description='Ajuste manual Staff: '.$reason;
            if($evidence!=='') $description.=' | Evidência: '.$evidence;
            $tx=$pdo->prepare("INSERT INTO point_transactions(streamer_id,type,description,points,reference_type,reference_id,created_by) VALUES(?,'adjustment',?,?, 'staff_adjustment',NULL,?)");
            $tx->execute([$sid,$description,$applied,(int)$_SESSION['staff_id']]);

            $pdo->prepare("UPDATE streamers SET points=? WHERE id=?")->execute([$new,$sid]);
            auditLog($pdo,'Ajuste manual de pontos','Pontos',$sid,$streamer['name'],'Ajuste realizado pela Staff: '.$description,['points'=>$current],['points'=>$new,'delta'=>$applied,'reason'=>$description]);

            $pdo->commit();

            $label=$applied>0?'+' . $applied:$applied;
            $ok='Ajuste registrado: '.$label.' pontos para '.$streamer['name'].'. Saldo atual: '.$new.' pts.';

            try{
                sendStreamerWebhook(
                    $pdo,
                    $sid,
                    $applied>0?'🎁 Ajuste de pontuação':'📉 Ajuste de pontuação',
                    'A Staff realizou um ajuste manual na sua pontuação.',
                    $applied>0?'success':'warning',
                    [
                        'Pontos'=>$label,
                        'Motivo'=>$reason,
                        'Novo saldo'=>$new.' pts',
                        'Staff'=>$_SESSION['staff_name']??'Staff'
                    ]
                );
            }catch(Throwable $ignore){}
        }catch(Throwable $e){
            if($pdo->inTransaction()) $pdo->rollBack();
            $error=$e->getMessage();
        }
    }
}

$streamers=$pdo->query("SELECT id,name,category,points FROM streamers WHERE active=1 ORDER BY name")->fetchAll();

$filterStreamer=(int)($_GET['streamer_id']??0);
$filterType=trim($_GET['type']??'');
$allowedTypes=['earn','penalty','adjustment','redemption_refund'];

$where=[];
$params=[];
if($filterStreamer>0){$where[]='p.streamer_id=?';$params[]=$filterStreamer;}
if(in_array($filterType,$allowedTypes,true)){$where[]='p.type=?';$params[]=$filterType;}
$whereSql=$where?'WHERE '.implode(' AND ',$where):'';

$sql="SELECT p.*,s.name streamer_name,s.category,st.name staff_name
      FROM point_transactions p
      JOIN streamers s ON s.id=p.streamer_id
      LEFT JOIN staff_users st ON st.id=p.created_by
      $whereSql
      ORDER BY p.created_at DESC,p.id DESC
      LIMIT 300";
$q=$pdo->prepare($sql);$q->execute($params);$transactions=$q->fetchAll();

$selectedStreamer=null;
if($filterStreamer){
    $sq=$pdo->prepare("SELECT * FROM streamers WHERE id=?");
    $sq->execute([$filterStreamer]);$selectedStreamer=$sq->fetch();
}

$totals=[];
foreach($transactions as $t){
    $sid=(int)$t['streamer_id'];
    if(!isset($totals[$sid]))$totals[$sid]=0;
    $totals[$sid]+=(int)$t['points'];
}
function typeLabel($t){
    return [
        'earn'=>'Ganho',
        'penalty'=>'Penalidade',
        'adjustment'=>'Ajuste manual',
        'redemption_refund'=>'Estorno'
    ][$t]??$t;
}
function esc($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Extrato de Pontuação</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.point-tools{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin:20px 0}
.point-card{background:rgba(17,24,39,.92);border:1px solid #29344a;border-radius:16px;padding:20px}
.point-card h2{margin-top:0}
.form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.form-grid .full{grid-column:1/-1}
.filter-row{display:flex;gap:10px;align-items:end;flex-wrap:wrap}
.filter-row>div{min-width:190px}
.points-positive{color:#61e6a5}.points-negative{color:#ff7373}
.badge.adjustment{background:#34265e;color:#c8b5ff}
.small-note{font-size:13px;color:#91a0b8}
@media(max-width:850px){.point-tools{grid-template-columns:1fr}.form-grid{grid-template-columns:1fr}.form-grid .full{grid-column:auto}}
</style>
</head>
<body>
<header class="topbar"><div><b>MORNINGFALL</b> <span>CREATORS</span></div><div><?=esc($_SESSION['staff_name']??'Staff')?> · <a href="../logout.php">Sair</a></div></header>
<main class="container">
<a href="index.php">← Dashboard</a>
<div class="page-head">
<div><p class="eyebrow">CONTROLE DA STAFF</p><h1>Extrato de Pontuação</h1><p class="muted">Veja de onde vieram os pontos e registre ajustes manuais com motivo e auditoria.</p></div>
</div>

<?php if($error):?><div class="alert error"><?=esc($error)?></div><?php endif;?>
<?php if($ok):?><div class="alert success"><?=esc($ok)?></div><?php endif;?>

<div class="point-tools">
<section class="point-card">
<h2>✏️ Ajuste manual</h2>
<p class="small-note">Use para bonificação excepcional ou correção. O lançamento fica registrado no histórico com a Staff responsável.</p>
<form method="post" class="form-grid">
<input type="hidden" name="action" value="adjust">
<div class="full"><label>Streamer</label>
<select name="streamer_id" required>
<option value="">Selecione</option>
<?php foreach($streamers as $s):?>
<option value="<?=$s['id']?>"><?=esc($s['name'])?> — <?=number_format((int)$s['points'],0,',','.')?> pts</option>
<?php endforeach;?>
</select></div>
<div><label>Pontos</label><input type="number" name="points" step="1" required placeholder="Ex.: 10 ou -5"></div>
<div><label>Motivo</label><input name="reason" required placeholder="Vídeo viral / correção"></div>
<div class="full"><label>Link/evidência (opcional)</label><input type="url" name="evidence_url" placeholder="https://..."></div>
<div class="full"><button class="btn primary" type="submit" onclick="return confirm('Registrar este ajuste de pontuação?')">💾 Registrar ajuste</button></div>
</form>
</section>

<section class="point-card">
<h2>🔎 Filtrar extrato</h2>
<form method="get" class="filter-row">
<div><label>Streamer</label><select name="streamer_id"><option value="">Todos</option>
<?php foreach($streamers as $s):?><option value="<?=$s['id']?>" <?=$filterStreamer===$s['id']?'selected':''?>><?=esc($s['name'])?></option><?php endforeach;?>
</select></div>
<div><label>Tipo</label><select name="type"><option value="">Todos</option>
<?php foreach($allowedTypes as $t):?><option value="<?=$t?>" <?=$filterType===$t?'selected':''?>><?=typeLabel($t)?></option><?php endforeach;?>
</select></div>
<div><button class="btn" type="submit">🔎 Filtrar</button></div>
<div><a class="btn" href="pontuacao.php">Limpar</a></div>
</form>
<?php if($selectedStreamer):?>
<div style="margin-top:18px"><strong><?=esc($selectedStreamer['name'])?></strong> · Saldo atual: <strong><?=number_format((int)$selectedStreamer['points'],0,',','.')?> pts</strong></div>
<?php endif;?>
</section>
</div>

<section class="panel">
<div class="section-title"><div><p class="eyebrow">AUDITORIA</p><h2>Movimentações</h2></div><span class="muted"><?=count($transactions)?> exibidas</span></div>
<?php if(!$transactions):?><div class="empty">Nenhuma movimentação encontrada.</div>
<?php else:?>
<div class="table-wrap"><table>
<thead><tr><th>Data</th><th>Streamer</th><th>Tipo</th><th>Descrição</th><th>Pontos</th><th>Staff</th></tr></thead>
<tbody>
<?php foreach($transactions as $t):?>
<tr>
<td><?=date('d/m/Y H:i',strtotime($t['created_at']))?></td>
<td><strong><?=esc($t['streamer_name'])?></strong></td>
<td><span class="badge <?=esc($t['type'])?>"><?=esc(typeLabel($t['type']))?></span></td>
<td><?=esc($t['description'])?></td>
<td class="<?=((int)$t['points']>=0?'points-positive':'points-negative')?>"><strong><?=((int)$t['points']>0?'+':'')?><?=number_format((int)$t['points'],0,',','.')?></strong></td>
<td><?=esc($t['staff_name']??'Sistema')?></td>
</tr>
<?php endforeach;?>
</tbody>
</table></div>
<?php endif;?>
</section>

<div class="dashboard-tip" style="margin-top:18px"><span>🔐</span><div><strong>Regra de auditoria</strong><p>Cada ajuste manual cria uma transação própria. O saldo do streamer é atualizado junto com o lançamento e nunca é alterado silenciosamente.</p></div></div>
</main>
<footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
</body></html>
