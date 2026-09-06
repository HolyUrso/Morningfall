<?php
require_once 'config/database.php';

function mf_setting(PDO $pdo, string $key, int $default = 0): int {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        return ($value !== false && is_numeric($value)) ? (int)$value : $default;
    } catch (Throwable $e) {
        return $default;
    }
}

$livePoints       = mf_setting($pdo, 'live_points_per_hour', 1);
$collabPoints     = mf_setting($pdo, 'collab_raid_points', 3);
$commonPoints     = mf_setting($pdo, 'social_common_points', 3);
$humorPoints      = mf_setting($pdo, 'social_humor_points', 20);
$newsPoints       = mf_setting($pdo, 'social_news_points', 10);
$weeklyLimit      = mf_setting($pdo, 'weekly_social_points_limit', 30);
$weeklyDays       = mf_setting($pdo, 'weekly_live_days_target', 3);
$weeklyBonus      = mf_setting($pdo, 'weekly_live_days_bonus', 20);
$weeklyHours      = mf_setting($pdo, 'weekly_live_hours_target', 10);
$weeklyHoursBonus = mf_setting($pdo, 'weekly_live_hours_bonus', 30);
$monthlyDays      = mf_setting($pdo, 'monthly_live_days_target', 20);
$monthlyBonus     = mf_setting($pdo, 'monthly_live_days_bonus', 50);
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Regras Gerais — Criadores de Conteúdo · Carmesim Roleplay</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
.rules-wrap{max-width:1050px;margin:0 auto;padding:34px 22px 70px}
.rules-list{margin-top:28px;border:1px solid #293448;border-radius:14px;overflow:hidden;background:#111722}
.rule-row{display:grid;grid-template-columns:250px 1fr;gap:24px;padding:18px 22px;border-bottom:1px solid #293448;align-items:center}
.rule-row:last-child{border-bottom:0}
.rule-title{font-size:16px;font-weight:700;color:#fff}
.rule-title .tag{display:block;margin-top:5px;font-size:11px;font-weight:600;letter-spacing:1.5px;text-transform:uppercase;color:#8b67ff}
.rule-desc{margin:0;color:#b8c4d8;font-size:14px;line-height:1.5}
.rule-desc strong{color:#fff}
.rule-row.highlight{background:rgba(139,103,255,.045)}
.rule-row.warning{background:rgba(255,190,60,.035)}

.general-rules{margin-top:28px;border:1px solid #293448;border-radius:14px;background:#0f141e;overflow:hidden}
.general-rules-head{padding:20px 22px;border-bottom:1px solid #293448;background:rgba(139,103,255,.035)}
.general-rules-head .rule-title{font-size:18px}
.general-intro{margin:12px 0 0;color:#b8c4d8;font-size:14px;line-height:1.6}
.discord-rule-emoji{width:22px;height:22px;object-fit:contain;vertical-align:-5px;margin-right:4px}
.general-rule-block{padding:18px 22px;border-bottom:1px solid #293448}
.general-rule-block:last-child{border-bottom:0}
.general-rule-block h3{margin:0 0 10px;color:#fff;font-size:16px}
.general-rule-block ul{margin:0;padding-left:20px;color:#b8c4d8;line-height:1.65;font-size:14px}
.general-rule-block li{margin:4px 0}
.general-rule-block strong{color:#fff}

.notice{margin-top:18px;border:1px solid #283244;background:#0f141e;border-radius:12px;padding:16px 18px;color:#dbe3f2;line-height:1.5}
.back{display:inline-block;margin-bottom:0}.public-nav{margin-bottom:34px;display:flex;align-items:center;flex-wrap:wrap;gap:8px}
.eyebrow{letter-spacing:4px;color:#8b67ff}
@media(max-width:700px){
  .rule-row{grid-template-columns:1fr;gap:8px;padding:16px 18px}
}
</style>
</head>
<body>
<main class="rules-wrap">
  <div class="public-nav"><a class="back" href="streamer-login.php">← Área do Streamer</a></div>

  <p class="eyebrow">CARMESIM ROLEPLAY · CRIADORES DE CONTEÚDO</p>
  <h1>Regras Gerais</h1>
  <p class="muted">Consulte publicamente as regras do programa de Criadores de Conteúdo. Não é necessário estar logado.</p>

  <h2 style="margin:34px 0 12px">Regras de Pontuação</h2>
  <div class="rules-list">
    <div class="rule-row">
      <div class="rule-title">🎥 Lives / VODs</div>
      <p class="rule-desc"><strong><?= $livePoints ?> ponto<?= $livePoints == 1 ? '' : 's' ?> por hora completa</strong> de live aprovada. VODs do mesmo dia são acumuladas antes do cálculo.</p>
    </div>

    <div class="rule-row">
      <div class="rule-title">🤝 Collab / Raid</div>
      <p class="rule-desc"><strong>+<?= $collabPoints ?> pontos</strong> quando a Staff validar a Collab/Raid com outro streamer do Condado.</p>
    </div>

    <div class="rule-row">
      <div class="rule-title">🔗 Link Comum</div>
      <p class="rule-desc"><strong>+<?= $commonPoints ?> pontos</strong> após aprovação da Staff.</p>
    </div>

    <div class="rule-row">
      <div class="rule-title">😂 Vídeo Humorístico</div>
      <p class="rule-desc"><strong>+<?= $humorPoints ?> pontos</strong> após aprovação da Staff.</p>
    </div>

    <div class="rule-row">
      <div class="rule-title">📰 Vídeo de Novidades</div>
      <p class="rule-desc"><strong>+<?= $newsPoints ?> pontos</strong> após aprovação da Staff.</p>
    </div>

    <div class="rule-row highlight">
      <div class="rule-title">📱 Conteúdos Extras</div>
      <p class="rule-desc">Você pode ganhar <strong>até <?= $weeklyLimit ?> pontos por semana</strong> com conteúdos extras para redes sociais. O limite e os valores de cada tipo são definidos no Setup e podem ser alterados pela Staff.</p>
    </div>

    <div class="rule-row highlight">
      <div class="rule-title">📅 Meta Semanal — Dias</div>
      <p class="rule-desc">Faça live em <strong><?= $weeklyDays ?> dias distintos</strong> durante a semana e receba <strong>+<?= $weeklyBonus ?> pontos extras</strong>. O bônus é concedido no máximo uma vez por semana.</p>
    </div>

    <div class="rule-row highlight">
      <div class="rule-title">⏱️ Meta Semanal — Horas</div>
      <p class="rule-desc">Acumule <strong><?= $weeklyHours ?> horas completas</strong> de live aprovada durante a semana e receba <strong>+<?= $weeklyHoursBonus ?> pontos extras</strong>. O bônus é concedido no máximo uma vez por semana.</p>
    </div>

    <div class="rule-row warning">
      <div class="rule-title">🏆 Meta Mensal — Sorteio</div>
      <p class="rule-desc">Faça live em <strong><?= $monthlyDays ?> dias distintos</strong> no mês para se tornar <strong>elegível para o sorteio</strong>. <strong>Somente 1 streamer será sorteado como vencedor.</strong> 🏆 Prêmio: <strong>+<?= $monthlyBonus ?> pontos extras</strong>.</p>
    </div>

    <div class="rule-row">
      <div class="rule-title">🔥 Sequência de Lives</div>
      <p class="rule-desc">Lives aprovadas com <strong>mais de 2 horas</strong> podem aumentar sua sequência. No máximo <strong>1 live válida por dia</strong> conta para a sequência. <strong>Não concede pontos</strong>; serve exclusivamente para o Ranking de Sequência de Lives.</p>
    </div>

    <div class="rule-row">
      <div class="rule-title">🎁 Bonificação Staff</div>
      <p class="rule-desc">A Staff pode conceder <strong>pontos extras manualmente</strong> quando houver motivo excepcional. Essa bonificação não consome o limite semanal de conteúdos extras.</p>
    </div>
  </div>


  <section class="general-rules">
    <div class="general-rules-head">
      <div class="rule-title"><img src="https://cdn.discordapp.com/emojis/1498848483799601372.webp?size=44&animated=true" alt="" class="discord-rule-emoji"> REGRAS GERAIS · CRIADORES DE CONTEÚDO – CARMESIM ROLEPLAY</div>
      <p class="general-intro"><img src="https://cdn.discordapp.com/emojis/1498854499614462036.webp?size=44&animated=true" alt="" class="discord-rule-emoji"> Ao criar conteúdo dentro do condado de Carmesim, você assume total responsabilidade por seguir as diretrizes abaixo. <strong>O não cumprimento pode resultar na perda da TAG e em punições no servidor.</strong></p>
    </div>

    <div class="general-rule-block">
      <h3><img src="https://cdn.discordapp.com/emojis/1498848483799601372.webp?size=44&animated=true" alt="" class="discord-rule-emoji"> Postura e Responsabilidade</h3>
      <ul><li>Respeitar todas as regras do servidor.</li><li>Proibido vazar informações de RP ou expor outros players.</li><li>Não criar conteúdos que prejudiquem o andamento do servidor.</li><li>Assédio, racismo, discurso de ódio ou comportamento tóxico é <strong>perda imediata do cargo Streamer</strong>.</li></ul>
    </div>

    <div class="general-rule-block">
      <h3><img src="https://cdn.discordapp.com/emojis/1498848483799601372.webp?size=44&animated=true" alt="" class="discord-rule-emoji"> Qualidade das Transmissões</h3>
      <ul><li>Toda live deve ter <strong>VOD ativo</strong>, garantindo a contagem dos seus pontos.</li><li>VOIP ativo.</li><li>Áudio limpo.</li><li>Live muda ou sem interação não pontua.</li><li>Obrigatório usar <strong>[+18] CARMESIM ROLEPLAY</strong> no título da live.</li><li>Proibido usar a TAG enquanto transmite em outros servidores de RedM.</li></ul>
    </div>

    <div class="general-rule-block">
      <h3><img src="https://cdn.discordapp.com/emojis/1498848483799601372.webp?size=44&animated=true" alt="" class="discord-rule-emoji"> Mods e Condutas</h3>
      <ul><li>Antes de usar um mod, tenha autorização da Staff para verificar se é permitido.</li><li>Proibido distorcer cenas ou prejudicar a imagem do servidor.</li></ul>
    </div>

    <div class="general-rule-block">
      <h3><img src="https://cdn.discordapp.com/emojis/1498848483799601372.webp?size=44&animated=true" alt="" class="discord-rule-emoji"> Frequência</h3>
      <ul><li><strong>7 dias sem atividade</strong> ➤ advertência e contato da Staff.</li><li><strong>21 dias sem atividade, sem justificativa</strong> ➤ remoção da TAG e dos pontos, conforme avaliação da Staff.</li></ul>
    </div>
  </section>
  <div class="notice">
    <strong>ℹ️ Importante:</strong> enviar um conteúdo <strong>não garante pontos</strong>. A Staff pode corrigir tipo, data, duração e Collab/Raid antes de aprovar ou recusar. Os valores exibidos nesta página são os mesmos configurados no Painel Staff.
  </div>
</main>

<footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
</body>
</html>
