<?php
/**
 * Regras de pontuação de VOD/Live.
 * A pontuação é calculada pelo tempo TOTAL de VODs do streamer na mesma data.
 * 1 ponto por hora completa, com limite diário de 10 pontos.
 */
function getSetting(PDO $pdo, string $key, $default = null) {
    $st = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false ? $default : $v;
}

function calculateDailyVodPoints(PDO $pdo, int $streamerId, string $vodDate): int {
    $st = $pdo->prepare('SELECT COALESCE(SUM(duration_minutes),0) FROM vods WHERE streamer_id=? AND vod_date=?');
    $st->execute([$streamerId, $vodDate]);
    $minutes = (int)$st->fetchColumn();

    $perHour = max(0, (int)getSetting($pdo, 'live_points_per_hour', 1));
    $dailyMax = max(0, (int)getSetting($pdo, 'live_max_points', 10));
    return min($dailyMax, intdiv($minutes, 60) * $perHour);
}

/**
 * Recalcula a pontuação das VODs de uma data específica.
 * Retorna o total de pontos de VOD daquela data depois do recálculo.
 */
function recalculateVodPointsForDate(PDO $pdo, int $streamerId, string $vodDate, ?int $createdBy = null): int {
    $st = $pdo->prepare('SELECT id, duration_minutes FROM vods WHERE streamer_id=? AND vod_date=? ORDER BY id ASC FOR UPDATE');
    $st->execute([$streamerId, $vodDate]);
    $vods = $st->fetchAll();

    $ids = array_map(fn($v) => (int)$v['id'], $vods);
    $oldTotal = 0;
    if ($ids) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $q = $pdo->prepare("SELECT COALESCE(SUM(points_awarded),0) FROM vods WHERE id IN ($ph)");
        $q->execute($ids);
        $oldTotal = (int)$q->fetchColumn();

        // Remove somente os lançamentos de pontuação ligados às VODs deste dia.
        $q = $pdo->prepare("DELETE FROM point_transactions WHERE reference_type='vod' AND reference_id IN ($ph)");
        $q->execute($ids);
    }

    $perHour = max(0, (int)getSetting($pdo, 'live_points_per_hour', 1));
    $dailyMax = max(0, (int)getSetting($pdo, 'live_max_points', 10));
    $cumulativeMinutes = 0;
    $newTotal = 0;

    foreach ($vods as $v) {
        $cumulativeMinutes += (int)$v['duration_minutes'];
        $target = min($dailyMax, intdiv($cumulativeMinutes, 60) * $perHour);
        $award = max(0, $target - $newTotal);
        $newTotal += $award;

        $u = $pdo->prepare('UPDATE vods SET points_awarded=? WHERE id=?');
        $u->execute([$award, (int)$v['id']]);

        if ($award > 0) {
            $ins = $pdo->prepare("INSERT INTO point_transactions(streamer_id,type,description,points,reference_type,reference_id,created_by) VALUES(?,'earn',?,?,?,?,?)");
            $ins->execute([$streamerId, 'VOD / Live - ' . date('d/m/Y', strtotime($vodDate)), $award, 'vod', (int)$v['id'], $createdBy]);
        }
    }

    // Ajusta o saldo do streamer somente pela diferença das VODs deste dia.
    $delta = $newTotal - $oldTotal;
    if ($delta !== 0) {
        $u = $pdo->prepare('UPDATE streamers SET points = GREATEST(0, points + ?) WHERE id=?');
        $u->execute([$delta, $streamerId]);
    }

    return $newTotal;
}

function ensureVodPointsMigrated(PDO $pdo, ?int $createdBy = null): void {
    $check = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key='vod_daily_points_migrated' LIMIT 1");
    $check->execute();
    if ($check->fetchColumn() !== false) return;

    $pdo->beginTransaction();
    try {
        $groups = $pdo->query("SELECT DISTINCT streamer_id, vod_date FROM vods ORDER BY vod_date, streamer_id")->fetchAll();
        foreach ($groups as $g) {
            recalculateVodPointsForDate($pdo, (int)$g['streamer_id'], $g['vod_date'], $createdBy);
        }
        $pdo->prepare("INSERT INTO settings(setting_key,setting_value,description) VALUES('vod_daily_points_migrated','1','VODs existentes recalculadas pela regra diária')")->execute();
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
