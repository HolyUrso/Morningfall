<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';
require_once '../config/streamer_webhook.php';

$id=(int)($_GET['id']??$_POST['id']??0);
$action=$_POST['action']??$_GET['action']??'';
$note=trim($_POST['note']??'');

if($id<=0 || !in_array($action,['approve','reject'],true)){
    header('Location: resgates.php');exit;
}

try{
    $pdo->beginTransaction();

    $q=$pdo->prepare("SELECT r.*,s.name streamer_name,s.points streamer_points,s.category,p.name prize_name
                      FROM redemptions r
                      JOIN streamers s ON s.id=r.streamer_id
                      JOIN prizes p ON p.id=r.prize_id
                      WHERE r.id=? FOR UPDATE");
    $q->execute([$id]);$r=$q->fetch();
    if(!$r) throw new RuntimeException('Resgate não encontrado.');
    if($r['status']!=='pending') throw new RuntimeException('Este resgate já foi analisado.');

    if($action==='reject'){
        $u=$pdo->prepare("UPDATE redemptions SET status='rejected',staff_notes=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?");
        $u->execute([$note!==''?$note:'Resgate recusado pela Staff.',(int)$_SESSION['staff_id'],$id]);
        auditLog($pdo,'Resgate recusado','Resgate',(int)$r['streamer_id'],$r['streamer_name'],'Resgate recusado pela Staff.', ['status'=>$r['status'],'points'=>$r['points_spent']], ['status'=>'rejected','notes'=>$note]);
        $pdo->commit();

        try{ sendStreamerWebhook($pdo,(int)$r['streamer_id'],'❌ Resgate recusado','Sua solicitação de resgate foi recusada pela Staff.','warning',['Recompensa'=>$r['prize_name'],'Motivo'=>$note!==''?$note:'Não informado']); }catch(Throwable $ignore){}
        header('Location: resgates.php?msg='.urlencode('Resgate recusado.'));exit;
    }

    $cost=(int)$r['points_spent'];
    $current=(int)$r['streamer_points'];
    if($current<$cost) throw new RuntimeException("O streamer não possui mais pontos suficientes. Saldo atual: {$current} pts.");

    $u=$pdo->prepare("UPDATE streamers SET points=points-? WHERE id=? AND points>=?");
    $u->execute([$cost,(int)$r['streamer_id'],$cost]);
    if($u->rowCount()!==1) throw new RuntimeException('Não foi possível descontar os pontos.');

    $desc='Resgate aprovado: '.$r['prize_name'];
    $tx=$pdo->prepare("INSERT INTO point_transactions(streamer_id,type,description,points,reference_type,reference_id,created_by) VALUES(?,'redemption',?, ?, 'redemption',?,?)");
    $tx->execute([(int)$r['streamer_id'],$desc,-$cost,$id,(int)$_SESSION['staff_id']]);

    $u=$pdo->prepare("UPDATE redemptions SET status='approved',staff_notes=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?");
    $u->execute([$note!==''?$note:'Resgate aprovado.',(int)$_SESSION['staff_id'],$id]);
    auditLog($pdo,'Resgate aprovado','Resgate',(int)$r['streamer_id'],$r['streamer_name'],'Resgate aprovado e pontos descontados.', ['status'=>$r['status'],'points'=>$r['streamer_points']], ['status'=>'approved','points'=>$r['streamer_points']-$cost,'cost'=>$cost]);

    $pdo->commit();

    try{ sendStreamerWebhook($pdo,(int)$r['streamer_id'],'🎁 Resgate aprovado','Seu resgate foi aprovado pela Staff e os pontos foram descontados.','success',['Recompensa'=>$r['prize_name'],'Pontos descontados'=>'-'.$cost,'Saldo após resgate'=>($current-$cost).' pts']); }catch(Throwable $ignore){}
    header('Location: resgates.php?msg='.urlencode('Resgate aprovado e pontos descontados.'));exit;

}catch(Throwable $e){
    if($pdo->inTransaction())$pdo->rollBack();
    header('Location: resgates.php?error='.urlencode($e->getMessage()));exit;
}
