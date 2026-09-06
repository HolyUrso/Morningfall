<?php
require_once '../config/streamer_auth.php';
require_once '../config/database.php';
require_once '../config/vod_points.php';
require_once '../config/content_points.php';

function streamerSetting(PDO $pdo, string $key, string $default=''): string { $st=$pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1'); $st->execute([$key]); $v=$st->fetchColumn(); return $v===false?$default:(string)$v; }
$streamerBgSetting=streamerSetting($pdo,'streamer_background','assets/img/streamer-background-default.png');
$streamerBg='../'.$streamerBgSetting;
$streamerLogoSetting=streamerSetting($pdo,'streamer_logo','');
$streamerLogo=$streamerLogoSetting ? '../'.$streamerLogoSetting : '';

$id=(int)$_SESSION['streamer_id'];
$st=$pdo->prepare("SELECT * FROM streamers WHERE id=? AND active=1");
$st->execute([$id]); $s=$st->fetch();
if(!$s){session_destroy();header('Location: ../streamer-login.php');exit;}

// Garante que VODs antigas estejam com a pontuação diária correta.
try { ensureVodPointsMigrated($pdo); } catch (Throwable $e) { /* não bloqueia o painel */ }

function formatDurationHM(int $minutes): string { return sprintf('%02d:%02d', intdiv($minutes,60), $minutes%60); }
function esc($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$today=date('Y-m-d');
$monthStart=date('Y-m-01');
$weekStart=date('Y-m-d', strtotime('monday this week'));

$q=$pdo->prepare("SELECT COALESCE(SUM(duration_minutes),0), COUNT(*) FROM vods WHERE streamer_id=?");
$q->execute([$id]); [$totalMinutes,$totalVods]=array_map('intval',$q->fetch(PDO::FETCH_NUM));

$q=$pdo->prepare("SELECT COALESCE(SUM(duration_minutes),0), COUNT(*) FROM vods WHERE streamer_id=? AND vod_date BETWEEN ? AND ?");
$q->execute([$id,$monthStart,$today]); [$monthMinutes,$monthVods]=array_map('intval',$q->fetch(PDO::FETCH_NUM));

$q=$pdo->prepare("SELECT COALESCE(SUM(duration_minutes),0), COUNT(*) FROM vods WHERE streamer_id=? AND vod_date BETWEEN ? AND ?");
$q->execute([$id,$weekStart,$today]); [$weekMinutes,$weekVods]=array_map('intval',$q->fetch(PDO::FETCH_NUM));

$q=$pdo->prepare("SELECT COALESCE(SUM(duration_minutes),0), COUNT(*) FROM vods WHERE streamer_id=? AND vod_date=?");
$q->execute([$id,$today]); [$todayMinutes,$todayVods]=array_map('intval',$q->fetch(PDO::FETCH_NUM));

$q=$pdo->prepare("SELECT COALESCE(SUM(points),0) FROM point_transactions WHERE streamer_id=? AND points>0 AND created_at>=? AND created_at<?");
$q->execute([$id,$monthStart.' 00:00:00',date('Y-m-d',strtotime($today.' +1 day')).' 00:00:00']);
$monthPoints=(int)$q->fetchColumn();

$q=$pdo->prepare("SELECT COALESCE(SUM(points),0) FROM point_transactions WHERE streamer_id=? AND points>0 AND created_at>=? AND created_at<?");
$q->execute([$id,$weekStart.' 00:00:00',date('Y-m-d',strtotime($today.' +1 day')).' 00:00:00']);
$weekPoints=(int)$q->fetchColumn();

$vod=$pdo->prepare("SELECT * FROM vods WHERE streamer_id=? ORDER BY vod_date DESC,created_at DESC LIMIT 8");
$vod->execute([$id]); $vods=$vod->fetchAll();
$pts=$pdo->prepare("SELECT * FROM point_transactions WHERE streamer_id=? ORDER BY created_at DESC LIMIT 8");
$pts->execute([$id]); $points=$pts->fetchAll();

// Metas semanais configuradas por categoria.
$cat=$s['category'];
$labels=['pending'=>'Pendente','approved'=>'Aprovado','delivered'=>'Entregue','cancelled'=>'Cancelado'];
$weeklyLives=(int)getSetting($pdo,$cat.'_weekly_lives',3);
$minLiveHours=(int)getSetting($pdo,$cat.'_min_live_hours',0);
$shortTarget=(int)getSetting($pdo,$cat.'_weekly_short_content',0);

$validLives=0;
if($minLiveHours>0){
    $q=$pdo->prepare("SELECT COUNT(*) FROM vods WHERE streamer_id=? AND vod_date BETWEEN ? AND ? AND duration_minutes>=?");
    $q->execute([$id,$weekStart,$today,$minLiveHours*60]);
    $validLives=(int)$q->fetchColumn();
} else { $validLives=$weekVods; }

$shortTypes=['video','viral_video'];
$ph=implode(',',array_fill(0,count($shortTypes),'?'));
$q=$pdo->prepare("SELECT COUNT(*) FROM activities WHERE streamer_id=? AND activity_date BETWEEN ? AND ? AND type IN ($ph)");
$q->execute(array_merge([$id,$weekStart,$today],$shortTypes));
$shortContent=(int)$q->fetchColumn();

$subQ=$pdo->prepare("SELECT c.*,cs.name collab_name FROM content_submissions c LEFT JOIN streamers cs ON cs.id=c.collab_streamer_id WHERE c.streamer_id=? ORDER BY c.created_at DESC LIMIT 10");
$subQ->execute([$id]); $submissions=$subQ->fetchAll();
$pendingSub=(int)count(array_filter($submissions,fn($x)=>$x['status']==='pending'));
$monthLiveDaysQ=$pdo->prepare("SELECT COUNT(DISTINCT vod_date) FROM vods WHERE streamer_id=? AND vod_date BETWEEN ? AND ?");
$monthLiveDaysQ->execute([$id,$monthStart,$today]); $monthLiveDays=(int)$monthLiveDaysQ->fetchColumn();
$weeklyLiveDays=weeklyLiveDays($pdo,$id,$today);
$weeklyLiveDaysTarget=contentPoint($pdo,'weekly_live_days_target',3);
$weeklyLiveDaysBonus=contentPoint($pdo,'weekly_live_days_bonus',20);
$weeklyLiveDaysProgress=$weeklyLiveDaysTarget>0?min(100,(int)floor($weeklyLiveDays/$weeklyLiveDaysTarget*100)):100;
$weeklyLiveDaysBonusAchieved=weeklyLiveDaysBonusAmount($pdo,$id,$today)>0;
$weeklyLiveHours=weeklyLiveHours($pdo,$id,$today);
$weeklyLiveHoursTarget=contentPoint($pdo,'weekly_live_hours_target',10);
$weeklyLiveHoursBonus=contentPoint($pdo,'weekly_live_hours_bonus',30);
$weeklyLiveHoursProgress=$weeklyLiveHoursTarget>0?min(100,(int)floor($weeklyLiveHours/$weeklyLiveHoursTarget*100)):100;
$weeklyLiveHoursBonusAchieved=weeklyLiveHoursBonusAmount($pdo,$id,$today)>0;
$liveSequence=liveSequenceStats($pdo,$id,$today);
$monthlyTarget=contentPoint($pdo,'monthly_live_days_target',20);
$monthlyBonus=contentPoint($pdo,'monthly_live_days_bonus',50);
$monthlyProgress=$monthlyTarget>0?min(100,(int)floor($monthLiveDays/$monthlyTarget*100)):100;
$weeklySocialUsed=socialPointsUsed($pdo,$id,$today);
$weeklySocialLimit=contentPoint($pdo,'weekly_social_points_limit',30);
$collabQ=$pdo->prepare("SELECT COUNT(*) FROM content_submissions WHERE streamer_id=? AND content_type='vod' AND status='approved' AND collab=1 AND submission_date BETWEEN ? AND ?");
$collabQ->execute([$id,$monthStart,$today]); $monthCollabs=(int)$collabQ->fetchColumn();
$bonusAchievedQ=$pdo->prepare("SELECT COUNT(*) FROM point_transactions WHERE streamer_id=? AND reference_type='monthly_live_days' AND reference_id=?");
$bonusAchievedQ->execute([$id,(int)date('Ym',strtotime($monthStart))]); $monthlyBonusAchieved=(int)$bonusAchievedQ->fetchColumn()>0;

$nextPrize=null;
$prizeStmt=$pdo->prepare("SELECT * FROM prizes WHERE active=1 ORDER BY points_{$cat} ASC, id ASC");
$prizeStmt->execute();
foreach($prizeStmt as $pr){
    $cost=(int)$pr['points_'.$cat];
    if($cost>(int)$s['points']) { $nextPrize=$pr; break; }
}
$progress=0;
if($nextPrize){ $cost=(int)$nextPrize['points_'.$cat]; $progress=$cost>0?min(100,(int)floor(((int)$s['points']/$cost)*100)):0; }

$weekLivesProgress=$weeklyLives>0?min(100,(int)floor(($weekVods/$weeklyLives)*100)):100;
$weekValidProgress=$weeklyLives>0?min(100,(int)floor(($validLives/$weeklyLives)*100)):100;
$shortProgress=$shortTarget>0?min(100,(int)floor(($shortContent/$shortTarget)*100)):100;
?>
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Meu Painel · <?=esc($s['name'])?></title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body class="streamer-area" style="--streamer-bg:url('<?=htmlspecialchars($streamerBg,ENT_QUOTES,'UTF-8')?>');background-image:linear-gradient(rgba(7,8,15,.70),rgba(7,8,15,.84)),url('<?=htmlspecialchars($streamerBg,ENT_QUOTES,'UTF-8')?>') !important;">
<header class="topbar"><div class="streamer-top-brand"><?php if($streamerLogo): ?><img src="<?=htmlspecialchars($streamerLogo)?>" alt="Logo"><span>CREATORS</span><?php else: ?><b>CARMESIM</b> <span>CREATORS</span><?php endif; ?></div><div class="top-user"><?php if(!empty($s['discord_avatar'])): ?><img class="discord-avatar-top" src="<?=esc($s['discord_avatar'])?>" alt="Avatar Discord"><?php endif; ?><?=esc($s['name'])?> · <a href="../streamer-logout.php">Sair</a></div></header>
<main class="container streamer-dashboard">
<div class="page-head"><div><p class="eyebrow">ÁREA DO STREAMER</p><h1>Olá, <?=esc($s['name'])?> 👋</h1><p class="muted">Acompanhe suas horas, pontos e metas em um só lugar.</p></div><span class="badge <?=$cat?>"><?=ucfirst($cat)?></span></div>

<?php if(isset($_GET['submission'])): ?><div class="alert success">Seu envio foi registrado e está aguardando análise da Staff. Nenhum ponto é concedido antes da aprovação.</div><?php endif; ?>

<div class="stats dashboard-stats">
<div class="stat stat-highlight"><small>PONTOS ATUAIS</small><strong><?=number_format((int)$s['points'],0,',','.')?></strong><span class="stat-note">+<?=number_format($monthPoints,0,',','.')?> este mês</span></div>
<div class="stat"><small>TEMPO TOTAL</small><strong><?=formatDurationHM($totalMinutes)?></strong><span class="stat-note"><?=number_format($totalVods,0,',','.')?> VODs registradas</span></div>
<div class="stat"><small>ESTE MÊS</small><strong><?=formatDurationHM($monthMinutes)?></strong><span class="stat-note"><?=number_format($monthVods,0,',','.')?> VODs · +<?=number_format($monthPoints,0,',','.')?> pts</span></div>
<div class="stat"><small>ESTA SEMANA</small><strong><?=formatDurationHM($weekMinutes)?></strong><span class="stat-note"><?=number_format($weekVods,0,',','.')?> VODs · +<?=number_format($weekPoints,0,',','.')?> pts</span></div>
</div>

<section class="panel dashboard-panel sequence-card"><div class="section-title"><div><p class="eyebrow">🔥 RANKING</p><h2>Sequência de Lives</h2></div><span class="muted">Sem pontos</span></div>
<div class="goal-highlight"><strong>🔥 <?=number_format($liveSequence['current'])?> Lives</strong><span>Melhor: <?=number_format($liveSequence['best'])?> Lives</span></div>
<div class="goal-foot"><b>Regra:</b> somente Lives aprovadas com <b>mais de 2 horas</b> entram na sequência. No máximo 1 Live válida por dia conta para aumentar a sequência. <b>Esta mecânica não concede pontos</b>; ela serve exclusivamente para o <b>Ranking de Sequência de Lives</b>.</div>
<div style="margin-top:14px"><a class="btn primary" href="sequencia.php">🔥 Ver Ranking de Sequência</a></div>
</section>

<div class="dashboard-grid creator-goals-grid">
<section class="panel dashboard-panel"><div class="section-title"><div><p class="eyebrow">META MENSAL</p><h2>Meta Mensal — Sorteio</h2></div><span class="muted"><?=date('m/Y')?></span></div>
<div class="goal-highlight"><strong><?=number_format($monthLiveDays)?> / <?=number_format($monthlyTarget)?> dias</strong><span><?=$monthlyBonusAchieved?'🎟️ Elegível ao sorteio':'Faltam '.number_format(max(0,$monthlyTarget-$monthLiveDays)).' dias'?></span></div>
<div class="progress large"><i style="width:<?=$monthlyProgress?>%"></i></div><div class="progress-label"><span>0 dias</span><span><?=number_format($monthlyTarget)?> dias</span></div>
<div class="goal-foot"><b>🎟️ Atingiu a meta? Você está elegível para o sorteio.</b><br>Somente <b>1 streamer</b> será sorteado como vencedor.<br>🏆 <b>Prêmio: +<?=number_format($monthlyBonus)?> pontos extras.</b></div>
</section>
<section class="panel dashboard-panel"><div class="section-title"><div><p class="eyebrow">META SEMANAL</p><h2>Meta semanal</h2></div><span class="muted"><?=date('d/m',strtotime($weekStart))?> — <?=date('d/m',strtotime($weekStart.' +6 days'))?></span></div>
<div class="goal-highlight"><strong><?=number_format($weeklyLiveDays)?> / <?=number_format($weeklyLiveDaysTarget)?> dias</strong><span><?=$weeklyLiveDaysBonusAchieved?'🎉 Meta atingida':'Faltam '.number_format(max(0,$weeklyLiveDaysTarget-$weeklyLiveDays)).' dias'?></span></div>
<div class="progress large"><i style="width:<?=$weeklyLiveDaysProgress?>%"></i></div><div class="progress-label"><span>0 dias</span><span><?=number_format($weeklyLiveDaysTarget)?> dias</span></div>
<div class="goal-foot">Ao atingir <?=number_format($weeklyLiveDaysTarget)?> dias distintos com live aprovada na semana, você recebe <b>+<?=number_format($weeklyLiveDaysBonus)?> pontos extras</b>. O valor da bonificação é definido no Setup e pode ser alterado pela Staff.</div>
</section>
<section class="panel dashboard-panel"><div class="section-title"><div><p class="eyebrow">⏱️ META SEMANAL</p><h2>Horas de live</h2></div><span class="muted"><?=date('d/m',strtotime($weekStart))?> — <?=date('d/m',strtotime($weekStart.' +6 days'))?></span></div>
<div class="goal-highlight"><strong><?=number_format($weeklyLiveHours)?> / <?=number_format($weeklyLiveHoursTarget)?> horas</strong><span><?=$weeklyLiveHoursBonusAchieved?'🎉 Meta atingida':'Faltam '.number_format(max(0,$weeklyLiveHoursTarget-$weeklyLiveHours)).' horas'?></span></div>
<div class="progress large"><i style="width:<?=$weeklyLiveHoursProgress?>%"></i></div><div class="progress-label"><span>0 horas</span><span><?=number_format($weeklyLiveHoursTarget)?> horas</span></div>
<div class="goal-foot">Ao atingir <?=number_format($weeklyLiveHoursTarget)?> horas completas de live aprovada na semana, você recebe <b>+<?=number_format($weeklyLiveHoursBonus)?> pontos extras</b>. O valor da bonificação é definido no Setup e pode ser alterado pela Staff.</div>
</section>
<section class="panel dashboard-panel"><div class="section-title"><div><p class="eyebrow">CONTEÚDO SOCIAL</p><h2>Limite semanal</h2></div><span class="muted"><?=date('d/m',strtotime($weekStart))?> — <?=date('d/m',strtotime($weekStart.' +6 days'))?></span></div>
<div class="goal-highlight"><strong><?=number_format(min($weeklySocialUsed,$weeklySocialLimit))?> / <?=number_format($weeklySocialLimit)?> pts</strong><span><?=max(0,$weeklySocialLimit-$weeklySocialUsed)?> pts disponíveis</span></div>
<div class="progress large"><i style="width:<?=($weeklySocialLimit>0?min(100,(int)floor($weeklySocialUsed/$weeklySocialLimit*100)):0)?>%"></i></div><div class="progress-label"><span>0 pts</span><span><?=number_format($weeklySocialLimit)?> pts</span></div>
<div class="goal-foot"><b>📱 Conteúdos extras:</b> ganhe pontos bônus enviando conteúdos adicionais para as redes sociais. O limite semanal é definido nas configurações do sistema e pode ser alterado pela Staff. <b>Limite atual: <?=number_format($weeklySocialLimit)?> pontos.</b><br>Link Comum +<?=contentPoint($pdo,'social_common_points',3)?> · Humor +<?=contentPoint($pdo,'social_humor_points',20)?> · Novidades +<?=contentPoint($pdo,'social_news_points',10)?>.</div>
</section>
</div>

<section class="panel streamer-actions-panel"><div class="section-title"><div><p class="eyebrow">ENVIE SUAS ATIVIDADES</p><h2>O que você quer enviar?</h2><p class="muted">Todos os envios passam pela análise da Staff. Você pode informar os dados e a Staff pode corrigir antes de aprovar.</p></div></div>
<div class="quick-grid creator-actions"><a class="quick-card quick-primary" href="enviar_vod.php"><b>🎥 Enviar VOD / Live</b><span>Link da VOD, data e Collab/Raid. A Staff define a data e duração oficiais.</span></a><a class="quick-card" href="enviar_conteudo.php"><b>📱 Enviar Conteúdo Extra</b><span>Link Comum, Vídeo Humorístico ou Vídeo de Novidades.</span></a><a class="quick-card" href="#envios"><b>📋 Acompanhar meus envios</b><span>Veja pendentes, aprovados, recusados e pontos concedidos.</span></a><a class="quick-card" href="regras.php"><b>📜 Ver regras e pontos</b><span>Confira Collab/Raid, limite semanal, meta mensal e bonificações.</span></a></div></section>
<section class="ranking-mini creator-central-card"><div><p class="eyebrow">🎥 ATIVIDADE</p><h2>Minhas VODs</h2><p class="muted">Veja suas transmissões aprovadas em cards.</p></div><a class="btn primary" href="vods.php">Ver VODs</a></section>
<section class="ranking-mini creator-central-card"><div><p class="eyebrow">⭐ PONTUAÇÃO</p><h2>Histórico de pontos</h2><p class="muted">Acompanhe todas as movimentações da sua pontuação.</p></div><a class="btn primary" href="pontos.php">Ver histórico</a></section>
<section class="ranking-mini creator-central-card"><div><p class="eyebrow">🎁 RECOMPENSAS</p><h2>Resgatar pontos</h2><p class="muted">Troque seus pontos pelas recompensas disponíveis.</p></div><a class="btn primary" href="recompensas.php">Ver recompensas</a></section>
<section class="ranking-mini creator-central-card"><div><p class="eyebrow">📋 RESGATES</p><h2>Meus resgates</h2><p class="muted">Acompanhe o status dos seus pedidos de resgate.</p></div><a class="btn primary" href="resgates.php">Ver resgates</a></section>
<section class="ranking-mini creator-central-card"><div><p class="eyebrow">🔥 RANKING DE SEQUÊNCIA</p><h2>Sequência de Lives</h2><p class="muted">Somente Lives com mais de 2 horas. Sem pontos.</p></div><a class="btn primary" href="sequencia.php">Ver ranking</a></section>
<section class="ranking-mini"><div><p class="eyebrow">🏆 RANKING</p><h2>Veja sua posição no Condado</h2><p class="muted">Compare seus pontos com os demais streamers.</p></div><a class="btn primary" href="ranking.php">Ver ranking</a></section>

<div id="envios" class="dashboard-section"><div class="section-title"><div><p class="eyebrow">ANÁLISE</p><h2>Meus envios</h2></div><span class="muted"><?=$pendingSub?> pendentes</span></div>
<div class="stats"><div class="stat"><small>DIAS DE LIVE NA SEMANA</small><strong><?=$weeklyLiveDays?>/<?=$weeklyLiveDaysTarget?></strong><span class="stat-note">+<?=$weeklyLiveDaysBonus?> pts ao atingir a meta</span></div><div class="stat"><small>HORAS DE LIVE NA SEMANA</small><strong><?=$weeklyLiveHours?>/<?=$weeklyLiveHoursTarget?></strong><span class="stat-note">+<?=$weeklyLiveHoursBonus?> pts ao atingir a meta</span></div><div class="stat"><small>DIAS DE LIVE NO MÊS</small><strong><?=$monthLiveDays?>/<?=$monthlyTarget?></strong><span class="stat-note">+<?=$monthlyBonus?> pts ao atingir a meta</span></div><div class="stat"><small>CONTEÚDO SOCIAL</small><strong><?=$weeklySocialUsed?>/<?=$weeklySocialLimit?></strong><span class="stat-note">pontos usados nesta semana</span></div></div>
<?php if(!$submissions): ?><div class="empty">Nenhum envio realizado.</div><?php else: ?><div class="table-wrap"><table><thead><tr><th>Tipo</th><th>Data</th><th>Collab</th><th>Pontos</th><th>Status</th><th>Link</th></tr></thead><tbody><?php foreach($submissions as $e): ?><tr><td><?=esc(['vod'=>'VOD de Live','link_comum'=>'Link Comum','video_humor'=>'Vídeo Humorístico','video_novidades'=>'Vídeo de Novidades'][$e['content_type']]??$e['content_type'])?></td><td><?=date('d/m/Y',strtotime($e['submission_date']))?></td><td><?=$e['collab']?'Sim':'Não'?></td><td><?=((int)$e['points_awarded']>0?'+':'').number_format((int)$e['points_awarded'])?></td><td><span class="status <?=esc($e['status'])?>"><?=esc(['pending'=>'Pendente','approved'=>'Aprovado','rejected'=>'Recusado'][$e['status']]??$e['status'])?></span></td><td><a href="<?=esc($e['url'])?>" target="_blank" rel="noopener">Abrir ↗</a></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></div>

<div id="meta" class="dashboard-section dashboard-tip"><span>💡</span><div><strong>Como sua pontuação funciona?</strong><p>Você envia VODs e conteúdos para análise. A Staff pode corrigir data, duração, tipo e Collab/Raid antes de aprovar. Lives do mesmo dia continuam sendo somadas, a meta semanal conta dias distintos e horas completas de segunda a domingo, com bonificações configuráveis, conteúdos sociais respeitam o limite semanal e a meta mensal coloca você no sorteio do bônus extra.</p></div></div>
<div style="margin:18px 0"><a class="btn" href="sorteio.php">🎟️ Ver sorteio ativo</a></div></main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></body></html>
