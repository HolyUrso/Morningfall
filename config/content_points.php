<?php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/vod_points.php';

function contentPoint(PDO $pdo, string $key, int $default): int {
    return max(0, (int)getSetting($pdo, $key, $default));
}

function socialWeekBounds(string $date): array {
    $ts = strtotime($date);
    $monday = date('Y-m-d', strtotime('monday this week', $ts));
    $sunday = date('Y-m-d', strtotime($monday . ' +6 days'));
    return [$monday, $sunday];
}

function socialPointsUsed(PDO $pdo, int $streamerId, string $date): int {
    [$start,$end] = socialWeekBounds($date);
    $types = ['link_comum','video_humor','video_novidades'];
    $ph = implode(',', array_fill(0,count($types),'?'));
    $st = $pdo->prepare("SELECT COALESCE(SUM(points_awarded),0) FROM content_submissions
        WHERE streamer_id=? AND status='approved' AND content_type IN ($ph) AND submission_date BETWEEN ? AND ?");
    $st->execute(array_merge([$streamerId],$types,[$start,$end]));
    return (int)$st->fetchColumn();
}


function weeklyLiveDays(PDO $pdo, int $streamerId, string $weekDate): int {
    [$start,$end] = socialWeekBounds($weekDate);
    $st = $pdo->prepare("SELECT COUNT(DISTINCT vod_date) FROM vods WHERE streamer_id=? AND vod_date BETWEEN ? AND ?");
    $st->execute([$streamerId, $start, $end]);
    return (int)$st->fetchColumn();
}

function weeklyLiveDaysReference(string $weekDate): int {
    [$start,] = socialWeekBounds($weekDate);
    return (int)date('Ymd', strtotime($start));
}

function weeklyLiveDaysBonusAmount(PDO $pdo, int $streamerId, string $weekDate): int {
    $ref = weeklyLiveDaysReference($weekDate);
    $st = $pdo->prepare("SELECT COALESCE(SUM(points),0) FROM point_transactions WHERE streamer_id=? AND reference_type='weekly_live_days' AND reference_id=?");
    $st->execute([$streamerId, $ref]);
    return (int)$st->fetchColumn();
}

function applyWeeklyLiveDaysBonus(PDO $pdo, int $streamerId, string $weekDate, ?int $createdBy = null): int {
    $target = contentPoint($pdo, 'weekly_live_days_target', 3);
    $bonus = contentPoint($pdo, 'weekly_live_days_bonus', 20);
    if ($target <= 0 || $bonus <= 0) return 0;
    if (weeklyLiveDays($pdo, $streamerId, $weekDate) < $target) return 0;
    if (weeklyLiveDaysBonusAmount($pdo, $streamerId, $weekDate) > 0) return 0;

    $ref = weeklyLiveDaysReference($weekDate);
    $desc = 'Bônus meta semanal - semana de ' . date('d/m/Y', strtotime(socialWeekBounds($weekDate)[0]));
    $tx = $pdo->prepare("INSERT INTO point_transactions(streamer_id,type,description,points,reference_type,reference_id,created_by) VALUES(?,'earn',?,?,?,?,?)");
    $tx->execute([$streamerId, $desc, $bonus, 'weekly_live_days', $ref, $createdBy]);
    $pdo->prepare('UPDATE streamers SET points=points+? WHERE id=?')->execute([$bonus, $streamerId]);
    return $bonus;
}

function removeWeeklyLiveDaysBonusIfNeeded(PDO $pdo, int $streamerId, string $weekDate): int {
    $target = contentPoint($pdo, 'weekly_live_days_target', 3);
    if ($target <= 0 || weeklyLiveDays($pdo, $streamerId, $weekDate) >= $target) return 0;
    $ref = weeklyLiveDaysReference($weekDate);
    $bonus = weeklyLiveDaysBonusAmount($pdo, $streamerId, $weekDate);
    if ($bonus <= 0) return 0;
    $pdo->prepare("DELETE FROM point_transactions WHERE streamer_id=? AND reference_type='weekly_live_days' AND reference_id=?")->execute([$streamerId, $ref]);
    $pdo->prepare('UPDATE streamers SET points=GREATEST(0,points-?) WHERE id=?')->execute([$bonus, $streamerId]);
    return $bonus;
}

function weeklyLiveHours(PDO $pdo, int $streamerId, string $weekDate): int {
    [$start,$end] = socialWeekBounds($weekDate);
    $st = $pdo->prepare("SELECT COALESCE(SUM(duration_minutes),0) FROM vods WHERE streamer_id=? AND vod_date BETWEEN ? AND ?");
    $st->execute([$streamerId, $start, $end]);
    return intdiv((int)$st->fetchColumn(), 60);
}

function weeklyLiveHoursReference(string $weekDate): int {
    [$start,] = socialWeekBounds($weekDate);
    return (int)date('Ymd', strtotime($start));
}

function weeklyLiveHoursBonusAmount(PDO $pdo, int $streamerId, string $weekDate): int {
    $ref = weeklyLiveHoursReference($weekDate);
    $st = $pdo->prepare("SELECT COALESCE(SUM(points),0) FROM point_transactions WHERE streamer_id=? AND reference_type='weekly_live_hours' AND reference_id=?");
    $st->execute([$streamerId, $ref]);
    return (int)$st->fetchColumn();
}

function applyWeeklyLiveHoursBonus(PDO $pdo, int $streamerId, string $weekDate, ?int $createdBy = null): int {
    $target = contentPoint($pdo, 'weekly_live_hours_target', 10);
    $bonus = contentPoint($pdo, 'weekly_live_hours_bonus', 30);
    if ($target <= 0 || $bonus <= 0) return 0;
    if (weeklyLiveHours($pdo, $streamerId, $weekDate) < $target) return 0;
    if (weeklyLiveHoursBonusAmount($pdo, $streamerId, $weekDate) > 0) return 0;

    $ref = weeklyLiveHoursReference($weekDate);
    $desc = 'Bônus meta semanal de horas - semana de ' . date('d/m/Y', strtotime(socialWeekBounds($weekDate)[0]));
    $tx = $pdo->prepare("INSERT INTO point_transactions(streamer_id,type,description,points,reference_type,reference_id,created_by) VALUES(?,'earn',?,?,?,?,?)");
    $tx->execute([$streamerId, $desc, $bonus, 'weekly_live_hours', $ref, $createdBy]);
    $pdo->prepare('UPDATE streamers SET points=points+? WHERE id=?')->execute([$bonus, $streamerId]);
    return $bonus;
}

function removeWeeklyLiveHoursBonusIfNeeded(PDO $pdo, int $streamerId, string $weekDate): int {
    $target = contentPoint($pdo, 'weekly_live_hours_target', 10);
    if ($target <= 0 || weeklyLiveHours($pdo, $streamerId, $weekDate) >= $target) return 0;
    $ref = weeklyLiveHoursReference($weekDate);
    $bonus = weeklyLiveHoursBonusAmount($pdo, $streamerId, $weekDate);
    if ($bonus <= 0) return 0;
    $pdo->prepare("DELETE FROM point_transactions WHERE streamer_id=? AND reference_type='weekly_live_hours' AND reference_id=?")->execute([$streamerId, $ref]);
    $pdo->prepare('UPDATE streamers SET points=GREATEST(0,points-?) WHERE id=?')->execute([$bonus, $streamerId]);
    return $bonus;
}

function monthlyLiveDays(PDO $pdo, int $streamerId, string $monthDate): int {
    $monthStart = date('Y-m-01', strtotime($monthDate));
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $st = $pdo->prepare("SELECT COUNT(DISTINCT vod_date) FROM vods WHERE streamer_id=? AND vod_date BETWEEN ? AND ?");
    $st->execute([$streamerId, $monthStart, $monthEnd]);
    return (int)$st->fetchColumn();
}

function isMonthlyLiveDaysEligible(PDO $pdo, int $streamerId, string $monthDate): bool {
    $target = contentPoint($pdo, 'monthly_live_days_target', 20);
    return $target > 0 && monthlyLiveDays($pdo, $streamerId, $monthDate) >= $target;
}



/**
 * Sequência de Lives:
 * - somente lives aprovadas com MAIS de 2 horas (> 120 minutos)
 * - no máximo 1 live válida por dia
 * - não concede pontos; serve apenas para o ranking de sequência
 */
function qualifyingLiveDays(PDO $pdo, int $streamerId): array {
    $st=$pdo->prepare("SELECT DISTINCT vod_date FROM vods WHERE streamer_id=? AND duration_minutes>120 ORDER BY vod_date ASC");
    $st->execute([$streamerId]);
    return array_values(array_map('strval',$st->fetchAll(PDO::FETCH_COLUMN)));
}

function liveSequenceStats(PDO $pdo, int $streamerId, ?string $today=null): array {
    $today=$today ?: date('Y-m-d');
    $days=qualifyingLiveDays($pdo,$streamerId);
    if(!$days) return ['current'=>0,'best'=>0,'valid_days'=>0];

    $set=array_fill_keys($days,true);
    $best=0; $run=0; $prev=null;
    foreach($days as $day){
        if($prev!==null && (strtotime($day)-strtotime($prev))===86400){ $run++; }
        else { $run=1; }
        $best=max($best,$run);
        $prev=$day;
    }

    $current=0;
    $last=end($days);
    if($last=== $today || $last===date('Y-m-d',strtotime($today.' -1 day'))){
        $current=1;
        $cursor=$last;
        while(isset($set[date('Y-m-d',strtotime($cursor.' -1 day'))])){
            $cursor=date('Y-m-d',strtotime($cursor.' -1 day'));
            $current++;
        }
    }
    return ['current'=>$current,'best'=>$best,'valid_days'=>count($days)];
}

function approveSocialContent(PDO $pdo, int $submissionId, int $staffId, string $contentType, string $notes=''): array {
    $allowed=['link_comum','video_humor','video_novidades'];
    if(!in_array($contentType,$allowed,true)) throw new RuntimeException('Tipo de conteúdo inválido.');

    $pdo->beginTransaction();
    try {
        $st=$pdo->prepare("SELECT * FROM content_submissions WHERE id=? FOR UPDATE");
        $st->execute([$submissionId]); $sub=$st->fetch();
        if(!$sub) throw new RuntimeException('Envio não encontrado.');
        if($sub['status']!=='pending') throw new RuntimeException('Este envio já foi analisado.');

        $pointsMap=[
            'link_comum'=>contentPoint($pdo,'social_common_points',3),
            'video_humor'=>contentPoint($pdo,'social_humor_points',20),
            'video_novidades'=>contentPoint($pdo,'social_news_points',10)
        ];
        $base=$pointsMap[$contentType];
        $limit=contentPoint($pdo,'weekly_social_points_limit',30);
        $used=socialPointsUsed($pdo,(int)$sub['streamer_id'],$sub['submission_date']);
        $award=max(0,min($base,$limit-$used));

        $up=$pdo->prepare("UPDATE content_submissions SET content_type=?,status='approved',points_awarded=?,staff_notes=?,reviewed_by=?,reviewed_at=NOW() WHERE id=?");
        $up->execute([$contentType,$award,$notes,$staffId,$submissionId]);

        if($award>0){
            $descMap=['link_comum'=>'Link Comum','video_humor'=>'Vídeo Humorístico','video_novidades'=>'Vídeo de Novidades'];
            $tx=$pdo->prepare("INSERT INTO point_transactions(streamer_id,type,description,points,reference_type,reference_id,created_by) VALUES(?,'earn',?,?,?,?,?)");
            $tx->execute([(int)$sub['streamer_id'],$descMap[$contentType],$award,'content',$submissionId,$staffId]);
            $pdo->prepare("UPDATE streamers SET points=points+? WHERE id=?")->execute([$award,(int)$sub['streamer_id']]);
        }
        $pdo->commit();
        return ['points'=>$award,'used_before'=>$used,'limit'=>$limit,'type'=>$contentType,'streamer_id'=>(int)$sub['streamer_id'],'url'=>$sub['url']];
    } catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); throw $e; }
}
