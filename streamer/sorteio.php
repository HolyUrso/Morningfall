<?php
require_once '../config/streamer_auth.php';
require_once '../config/database.php';
require_once '../config/vod_points.php';
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
$id=(int)$_SESSION['streamer_id'];
$raffle=$pdo->query("SELECT * FROM raffles WHERE active=1 ORDER BY id DESC LIMIT 1")->fetch();
$eligible=false; $eligibleDays=0; $target=20;
$monthlyBonus=max(0,(int)getSetting($pdo,'monthly_live_days_bonus',50));
if($raffle && $raffle['draw_date']){
    $month=date('Y-m',strtotime($raffle['draw_date']));
    $start=$month.'-01'; $end=date('Y-m-t',strtotime($start));
    $target=(int)($pdo->query("SELECT setting_value FROM settings WHERE setting_key='monthly_live_days_target'")->fetchColumn() ?: 20);
    $q=$pdo->prepare("SELECT COUNT(DISTINCT vod_date) FROM vods WHERE streamer_id=? AND vod_date BETWEEN ? AND ?");
    $q->execute([$id,$start,$end]); $eligibleDays=(int)$q->fetchColumn();
    $eligible=$eligibleDays >= $target;
}
?>
<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sorteio — Morningfall Creators</title><link rel="stylesheet" href="../assets/css/style.css">
<style>
.wrap{max-width:950px;margin:0 auto;padding:34px 22px 70px}.hero{background:linear-gradient(135deg,#151323,#111722);border:1px solid #3a2b6d;border-radius:18px;padding:28px;margin-top:22px}.prize{font-size:28px;font-weight:800;margin:10px 0 20px}.meta{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:20px}.meta div{background:#0d131d;border:1px solid #293448;border-radius:12px;padding:14px}.meta small{display:block;color:#8f9bb3}.meta strong{display:block;margin-top:5px}.rules{margin-top:20px;padding:18px;border:1px solid #293448;border-radius:12px;background:#0e141e}.empty{margin-top:22px;padding:30px;text-align:center;border:1px dashed #34415a;border-radius:15px;color:#9aa8be}.eligible{margin-top:20px;padding:18px;border-radius:12px;background:#153c2b;border:1px solid #2f8b62;color:#bff5d9}.noteligible{margin-top:20px;padding:18px;border-radius:12px;background:#291b21;border:1px solid #68404b;color:#ffd4dc}
@media(max-width:700px){.meta{grid-template-columns:1fr}}
</style></head>
<body><main class="wrap">
<a href="index.php">← Meu painel</a><p class="eyebrow" style="margin-top:35px">MORNINGFALL CREATORS</p><h1>🎟️ Sorteio</h1>
<?php if($raffle):?><section class="hero">
<div class="eyebrow">SORTEIO ATIVO</div><h2><?=e($raffle['title'])?></h2><div class="prize">🎁 +<?=number_format($monthlyBonus,0,',','.')?> pontos extras</div><p class="muted">Valor definido no Setup da Meta Mensal.</p>
<?php if(trim((string)$raffle['description'])!==''):?><p><?=nl2br(e($raffle['description']))?></p><?php endif;?>
<div class="meta"><div><small>Data</small><strong><?=e($raffle['draw_date']?:'A definir')?></strong></div><div><small>Horário</small><strong><?=e($raffle['draw_time']?substr($raffle['draw_time'],0,5):'A definir')?></strong></div><div><small>Ganhadores</small><strong><?= (int)$raffle['winners_count']?></strong></div></div>
<div class="<?= $eligible?'eligible':'noteligible' ?>">
<?php if($eligible):?>🏆 <strong>Você está elegível!</strong><br>Você realizou <?= $eligibleDays ?> dias distintos de live no mês e atingiu a meta de <?= $target ?> dias.<?php else:?>⏳ <strong>Ainda não elegível.</strong><br>Você realizou <?= $eligibleDays ?> de <?= $target ?> dias distintos de live no mês.<?php endif;?>
</div>
<?php if(trim((string)$raffle['eligibility_text'])!==''):?><div class="rules"><strong>📜 Regras adicionais:</strong><br><?=nl2br(e($raffle['eligibility_text']))?></div><?php endif;?>
<div class="rules"><strong>ℹ️ Participação automática:</strong> não é necessário clicar em participar. Os streamers que atingirem a meta entram automaticamente na lista oficial do sorteio. Os pontos do prêmio são concedidos somente ao(s) ganhador(es).</div>
</section>
<?php else:?><div class="empty">🎟️ Não há nenhum sorteio ativo no momento.</div><?php endif;?>
<footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
</main></body></html>
