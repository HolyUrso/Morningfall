<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/vod_points.php';
require_once '../config/content_points.php';
require_once '../config/streamer_webhook.php';

$id=(int)($_GET['id']??0);
$st=$pdo->prepare("SELECT v.*, s.name streamer_name FROM vods v JOIN streamers s ON s.id=v.streamer_id WHERE v.id=?");
$st->execute([$id]);
$v=$st->fetch();

if(!$v){
    header('Location: ../admin/vods.php');
    exit;
}

$sid=(int)$v['streamer_id'];
$oldDuration=(int)$v['duration_minutes'];
$oldPoints=(int)$v['points_awarded'];
$date=$v['vod_date'];

try{
    $pdo->beginTransaction();

    // Remove o lançamento da VOD e a própria VOD.
    $pdo->prepare("DELETE FROM point_transactions WHERE reference_type='vod' AND reference_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM vods WHERE id=?")->execute([$id]);

    // Recalcula todos os pontos da data para manter o saldo correto,
    // inclusive quando a exclusão faz uma VOD anterior perder pontos.
    recalculateVodPointsForDate($pdo,$sid,$date,(int)$_SESSION['staff_id']);

    $dayQ=$pdo->prepare("SELECT COALESCE(SUM(points_awarded),0) FROM vods WHERE streamer_id=? AND vod_date=?");
    $dayQ->execute([$sid,$date]);
    $newDayPoints=(int)$dayQ->fetchColumn();

    // Se esta VOD tinha Collab/Raid validada, remove também a bonificação da Collab.
    $subQ=$pdo->prepare("SELECT id FROM content_submissions WHERE vod_id=? LIMIT 1");
    $subQ->execute([$id]);
    $submissionId=$subQ->fetchColumn();
    if($submissionId){
        $collabQ=$pdo->prepare("SELECT COALESCE(SUM(points),0) FROM point_transactions WHERE streamer_id=? AND reference_type='collab' AND reference_id=?");
        $collabQ->execute([$sid,(int)$submissionId]);
        $collabPoints=(int)$collabQ->fetchColumn();
        if($collabPoints){
            $pdo->prepare("DELETE FROM point_transactions WHERE streamer_id=? AND reference_type='collab' AND reference_id=?")->execute([$sid,(int)$submissionId]);
            $pdo->prepare("UPDATE streamers SET points=GREATEST(0,points-?) WHERE id=?")->execute([$collabPoints,$sid]);
        }
        $pdo->prepare("UPDATE content_submissions SET status='rejected', staff_notes=CONCAT(COALESCE(staff_notes,''),' | VOD excluída pela Staff.'), updated_at=NOW() WHERE id=?")->execute([(int)$submissionId]);
    }

    // Se a exclusão fizer a meta mensal de 20 dias deixar de existir, estorna o bônus mensal.
    $monthStart=date('Y-m-01',strtotime($date)); $monthEnd=date('Y-m-t',strtotime($date));
    $target=contentPoint($pdo,'monthly_live_days_target',20);
    $bonusRef=(int)date('Ym',strtotime($monthStart));
    $daysQ=$pdo->prepare("SELECT COUNT(DISTINCT vod_date) FROM vods WHERE streamer_id=? AND vod_date BETWEEN ? AND ?");
    $daysQ->execute([$sid,$monthStart,$monthEnd]);
    $daysAfter=(int)$daysQ->fetchColumn();
    if($daysAfter<$target){
        $bonusQ=$pdo->prepare("SELECT COALESCE(SUM(points),0) FROM point_transactions WHERE streamer_id=? AND reference_type='monthly_live_days' AND reference_id=?");
        $bonusQ->execute([$sid,$bonusRef]);
        $monthlyBonus=(int)$bonusQ->fetchColumn();
        if($monthlyBonus){
            $pdo->prepare("DELETE FROM point_transactions WHERE streamer_id=? AND reference_type='monthly_live_days' AND reference_id=?")->execute([$sid,$bonusRef]);
            $pdo->prepare("UPDATE streamers SET points=GREATEST(0,points-?) WHERE id=?")->execute([$monthlyBonus,$sid]);
        }
    }

    $weeklyBonusRemoved=removeWeeklyLiveDaysBonusIfNeeded($pdo,$sid,$date);

    $pdo->commit();

    $staffQ=$pdo->prepare("SELECT name FROM staff_users WHERE id=?");
    $staffQ->execute([(int)$_SESSION['staff_id']]);
    $staffName=(string)($staffQ->fetchColumn() ?: 'Staff');

    sendStreamerWebhook($pdo, $sid, '🗑️ VOD excluída',
        'Uma VOD foi removida do histórico e a pontuação da data foi recalculada.',
        'warning',
        [
            'Data' => date('d/m/Y', strtotime($date)),
            'Duração removida' => sprintf('%02d:%02d', intdiv($oldDuration,60), $oldDuration%60),
            'Pontos removidos da VOD' => '-'.$oldPoints,
            'Total de pontos no dia' => $newDayPoints,
            'Bônus meta semanal removido' => $weeklyBonusRemoved ? '-'.$weeklyBonusRemoved.' pts' : 'Não',
            'Staff' => $staffName
        ]
    );
}catch(Throwable $e){
    if($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
}

header('Location: ../admin/vods.php');
exit;
