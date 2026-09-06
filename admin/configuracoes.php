<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';

// Configurações de pontuação são exclusivas do Master.
if (($_SESSION['staff_role'] ?? '') !== 'master') {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

$fields = [
    'live_points_per_hour' => [
        'label' => 'VOD / Live — pontos por hora completa',
        'description' => 'Cada hora completa acumulada no mesmo dia gera esta quantidade de pontos.',
        'min' => 0,
        'max' => 100,
        'step' => 1,
        'suffix' => ' pts/h'
    ],
    'live_max_points' => [
        'label' => 'VOD / Live — limite diário',
        'description' => 'Limite máximo de pontos de VOD/Live que podem ser gerados em um único dia.',
        'min' => 0,
        'max' => 1000,
        'step' => 1,
        'suffix' => ' pts/dia'
    ],
    'collab_raid_points' => [
        'label' => 'Collab / Raid',
        'description' => 'Pontos concedidos quando a Staff valida a Collab/Raid.',
        'min' => 0,
        'max' => 1000,
        'step' => 1,
        'suffix' => ' pts'
    ],
    'social_common_points' => [
        'label' => 'Link Comum',
        'description' => 'Pontos de um Link Comum após aprovação da Staff.',
        'min' => 0,
        'max' => 1000,
        'step' => 1,
        'suffix' => ' pts'
    ],
    'social_humor_points' => [
        'label' => 'Vídeo Humorístico',
        'description' => 'Pontos de um Vídeo Humorístico após aprovação da Staff.',
        'min' => 0,
        'max' => 1000,
        'step' => 1,
        'suffix' => ' pts'
    ],
    'social_news_points' => [
        'label' => 'Vídeo de Novidades',
        'description' => 'Pontos de um Vídeo de Novidades após aprovação da Staff.',
        'min' => 0,
        'max' => 1000,
        'step' => 1,
        'suffix' => ' pts'
    ],
    'weekly_social_points_limit' => [
        'label' => 'Limite semanal de conteúdos sociais',
        'description' => 'Máximo de pontos por semana gerados pelos tipos Link Comum, Humor e Novidades.',
        'min' => 0,
        'max' => 10000,
        'step' => 1,
        'suffix' => ' pts/semana'
    ],
    'weekly_live_days_target' => [
        'label' => 'Meta semanal de dias com live',
        'description' => 'Quantidade de dias distintos com live aprovada necessária para atingir a meta semanal.',
        'min' => 0,
        'max' => 7,
        'step' => 1,
        'suffix' => ' dias'
    ],
    'weekly_live_days_bonus' => [
        'label' => 'Bônus da meta semanal',
        'description' => 'Pontos extras concedidos automaticamente ao atingir a meta semanal de dias com live.',
        'min' => 0,
        'max' => 10000,
        'step' => 1,
        'suffix' => ' pts'
    ],
    'weekly_live_hours_target' => [
        'label' => 'Meta semanal de horas com live',
        'description' => 'Quantidade de horas completas de live aprovada necessária para atingir a meta semanal de horas.',
        'min' => 0,
        'max' => 168,
        'step' => 1,
        'suffix' => ' horas'
    ],
    'weekly_live_hours_bonus' => [
        'label' => 'Bônus da meta semanal de horas',
        'description' => 'Pontos extras concedidos automaticamente ao atingir a meta semanal de horas com live.',
        'min' => 0,
        'max' => 10000,
        'step' => 1,
        'suffix' => ' pts'
    ],
    'monthly_live_days_target' => [
        'label' => 'Meta mensal de dias com live',
        'description' => 'Quantidade de dias distintos com live aprovada necessária para atingir a meta mensal.',
        'min' => 0,
        'max' => 31,
        'step' => 1,
        'suffix' => ' dias'
    ],
    'monthly_live_days_bonus' => [
        'label' => 'Bônus da meta mensal',
        'description' => 'Pontos extras concedidos ao atingir a quantidade de dias definida acima.',
        'min' => 0,
        'max' => 10000,
        'step' => 1,
        'suffix' => ' pts'
    ],
    'redemption_cooldown_days' => [
        'label' => 'Intervalo entre resgates de itens',
        'description' => 'Quantidade de dias que o streamer precisa aguardar após um resgate aprovado antes de poder solicitar outro item.',
        'min' => 0,
        'max' => 3650,
        'step' => 1,
        'suffix' => ' dias'
    ],
    'proof_retention_days' => [
        'label' => 'Retenção de comprovantes de VOD',
        'description' => 'Após este número de dias do envio, os prints/fotos do comprovante são apagados automaticamente. O registro da VOD continua no sistema.',
        'min' => 1,
        'max' => 3650,
        'step' => 1,
        'suffix' => ' dias'
    ],
];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($fields as $key => $meta) {
            $value = filter_input(INPUT_POST, $key, FILTER_VALIDATE_INT);
            if ($value === false || $value === null) {
                throw new RuntimeException('Informe valores numéricos válidos em todos os campos.');
            }
            $value = max((int)$meta['min'], min((int)$meta['max'], (int)$value));

            $check = $pdo->prepare("SELECT COUNT(*) FROM settings WHERE setting_key=?");
            $check->execute([$key]);

            if ((int)$check->fetchColumn() > 0) {
                $oldQ = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1");
                $oldQ->execute([$key]);
                $oldValue = $oldQ->fetchColumn();
                $up = $pdo->prepare("UPDATE settings SET setting_value=? WHERE setting_key=?");
                $up->execute([(string)$value, $key]);
                if ((string)$oldValue !== (string)$value) {
                    auditLog($pdo,'Configuração alterada','Configurações',null,'','Valor de configuração alterado: '.$key,['value'=>$oldValue],['value'=>$value]);
                }
            } else {
                $ins = $pdo->prepare("INSERT INTO settings(setting_key,setting_value,description) VALUES(?,?,?)");
                $ins->execute([(string)$key, (string)$value, $meta['description']]);
                auditLog($pdo,'Configuração criada','Configurações',null,'','Configuração criada: '.$key,null,['value'=>$value]);
            }
        }
        $success = 'Configurações de pontuação salvas com sucesso.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$settingsRows = $pdo->query("SELECT setting_key, setting_value, description FROM settings")->fetchAll();
$rows = [];
foreach ($settingsRows as $settingRow) {
    $rows[$settingRow['setting_key']] = $settingRow['setting_value'];
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configurações de Pontuação</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.settings-section{margin:24px 0}
.settings-section h2{margin:0 0 6px}
.settings-section .section-help{margin:0 0 18px;color:#8f9bb3}
.settings-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
.setting-card{background:rgba(17,23,34,.9);border:1px solid #283244;border-radius:14px;padding:18px}
.setting-card label{display:block;font-weight:700;margin-bottom:8px}
.setting-card .field-row{display:flex;align-items:center;gap:10px}
.setting-card input{width:100%;box-sizing:border-box;background:#0b1018;border:1px solid #354158;color:#fff;border-radius:9px;padding:12px;font-size:16px}
.setting-card .suffix{white-space:nowrap;color:#9ba8c0;font-size:14px}
.setting-card small{display:block;color:#8f9bb3;line-height:1.45;margin-top:9px}
.alert-success{padding:14px 16px;border-radius:10px;background:#153c2b;border:1px solid #2f8b62;color:#bff5d9;margin:18px 0}
.alert-error{padding:14px 16px;border-radius:10px;background:#421f27;border:1px solid #7d3a47;color:#ffd0d8;margin:18px 0}
.rules-note{margin-top:20px;padding:16px;border-radius:12px;background:#141a25;border:1px solid #2a3447;color:#aeb9cc}
@media(max-width:800px){.settings-grid{grid-template-columns:1fr}}
</style>
</head>
<body>
<header class="topbar">
  <div><b>MORNINGFALL</b> <span>CREATORS</span></div>
  <div><?=htmlspecialchars($_SESSION['staff_name'])?> · <a href="../logout.php">Sair</a></div>
</header>

<main class="container">
  <div class="page-head">
    <div>
      <a href="index.php">← Dashboard</a>
      <p class="eyebrow">SETUP</p>
      <h1>Configurações de Pontuação</h1>
      <p class="muted">Altere as regras sem precisar editar o código do site.</p>
    </div>
    <a class="btn" href="aparencia.php">🎨 Setup · Aparência</a>
    <?php if(($_SESSION["staff_role"] ?? "")==="master"): ?><a class="btn" href="discord.php">🔗 Discord / Relay · Master</a><?php endif; ?>
  </div>

  <?php if ($success): ?><div class="alert-success">✅ <?=htmlspecialchars($success)?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert-error">❌ <?=htmlspecialchars($error)?></div><?php endif; ?>

  <form method="post">
    <section class="settings-section">
      <h2>🎥 VOD / Live</h2>
      <p class="section-help">A soma é feita por data: VODs/lives picadas no mesmo dia são acumuladas antes do cálculo.</p>
      <div class="settings-grid">
        <?php foreach (['live_points_per_hour','live_max_points'] as $key): $meta=$fields[$key]; $value=(int)($rows[$key] ?? 0); ?>
        <div class="setting-card">
          <label for="<?=$key?>"><?=htmlspecialchars($meta['label'])?></label>
          <div class="field-row">
            <input id="<?=$key?>" name="<?=$key?>" type="number" min="<?=$meta['min']?>" max="<?=$meta['max']?>" step="<?=$meta['step']?>" value="<?=$value?>" required>
            <span class="suffix"><?=htmlspecialchars($meta['suffix'])?></span>
          </div>
          <small><?=htmlspecialchars($meta['description'])?></small>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="settings-section">
      <h2>🤝 Collab / Raid</h2>
      <p class="section-help">Só entra na pontuação depois que a Staff validar o envio.</p>
      <div class="settings-grid">
        <?php $key='collab_raid_points'; $meta=$fields[$key]; $value=(int)($rows[$key] ?? 0); ?>
        <div class="setting-card">
          <label for="<?=$key?>"><?=htmlspecialchars($meta['label'])?></label>
          <div class="field-row"><input id="<?=$key?>" name="<?=$key?>" type="number" min="<?=$meta['min']?>" max="<?=$meta['max']?>" value="<?=$value?>" required><span class="suffix"><?=htmlspecialchars($meta['suffix'])?></span></div>
          <small><?=htmlspecialchars($meta['description'])?></small>
        </div>
      </div>
    </section>

    <section class="settings-section">
      <h2>📱 Conteúdo para Redes Sociais</h2>
      <p class="section-help">Todos os conteúdos são enviados pelo streamer e precisam ser analisados pela Staff.</p>
      <div class="settings-grid">
        <?php foreach (['social_common_points','social_humor_points','social_news_points','weekly_social_points_limit'] as $key): $meta=$fields[$key]; $value=(int)($rows[$key] ?? 0); ?>
        <div class="setting-card">
          <label for="<?=$key?>"><?=htmlspecialchars($meta['label'])?></label>
          <div class="field-row"><input id="<?=$key?>" name="<?=$key?>" type="number" min="<?=$meta['min']?>" max="<?=$meta['max']?>" value="<?=$value?>" required><span class="suffix"><?=htmlspecialchars($meta['suffix'])?></span></div>
          <small><?=htmlspecialchars($meta['description'])?></small>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="settings-section">
      <h2>📅 Meta Semanal</h2>
      <p class="section-help">Conta dias distintos com live aprovada de segunda a domingo. Ao atingir a meta, o bônus é concedido automaticamente uma vez por semana.</p>
      <div class="settings-grid">
        <?php foreach (['weekly_live_days_target','weekly_live_days_bonus'] as $key): $meta=$fields[$key]; $value=(int)($rows[$key] ?? 0); ?>
        <div class="setting-card">
          <label for="<?=$key?>"><?=htmlspecialchars($meta['label'])?></label>
          <div class="field-row"><input id="<?=$key?>" name="<?=$key?>" type="number" min="<?=$meta['min']?>" max="<?=$meta['max']?>" value="<?=$value?>" required><span class="suffix"><?=htmlspecialchars($meta['suffix'])?></span></div>
          <small><?=htmlspecialchars($meta['description'])?></small>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="settings-section">
      <h2>⏱️ Meta Semanal de Horas</h2>
      <p class="section-help">Soma as horas completas de VOD/Live aprovadas de segunda a domingo. Ao atingir a meta, o bônus é concedido automaticamente uma vez por semana.</p>
      <div class="settings-grid">
        <?php foreach (['weekly_live_hours_target','weekly_live_hours_bonus'] as $key): $meta=$fields[$key]; $value=(int)($rows[$key] ?? 0); ?>
        <div class="setting-card">
          <label for="<?=$key?>"><?=htmlspecialchars($meta['label'])?></label>
          <div class="field-row"><input id="<?=$key?>" name="<?=$key?>" type="number" min="<?=$meta['min']?>" max="<?=$meta['max']?>" step="<?=$meta['step']?>" value="<?=$value?>" required><span class="suffix"><?=htmlspecialchars($meta['suffix'])?></span></div>
          <small><?=htmlspecialchars($meta['description'])?></small>
        </div>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="settings-section">
      <h2>🏆 Meta Mensal</h2>
      <p class="section-help">Conta dias distintos com live aprovada. Várias lives no mesmo dia contam como 1 dia.</p>
      <div class="settings-grid">
        <?php foreach (['monthly_live_days_target','monthly_live_days_bonus'] as $key): $meta=$fields[$key]; $value=(int)($rows[$key] ?? 0); ?>
        <div class="setting-card">
          <label for="<?=$key?>"><?=htmlspecialchars($meta['label'])?></label>
          <div class="field-row"><input id="<?=$key?>" name="<?=$key?>" type="number" min="<?=$meta['min']?>" max="<?=$meta['max']?>" value="<?=$value?>" required><span class="suffix"><?=htmlspecialchars($meta['suffix'])?></span></div>
          <small><?=htmlspecialchars($meta['description'])?></small>
        </div>
        <?php endforeach; ?>
      </div>
    </section>




    <section class="settings-section">
      <h2>🎁 Resgates</h2>
      <p class="section-help">Defina o intervalo entre resgates aprovados. O prazo é contado a partir da aprovação do último resgate.</p>
      <div class="settings-grid">
        <?php $key='redemption_cooldown_days'; $meta=$fields[$key]; $value=(int)($rows[$key] ?? 7); ?>
        <div class="setting-card">
          <label for="<?=$key?>"><?=htmlspecialchars($meta['label'])?></label>
          <div class="field-row"><input id="<?=$key?>" name="<?=$key?>" type="number" min="<?=$meta['min']?>" max="<?=$meta['max']?>" step="<?=$meta['step']?>" value="<?=$value?>" required><span class="suffix"><?=htmlspecialchars($meta['suffix'])?></span></div>
          <small><?=htmlspecialchars($meta['description'])?></small>
        </div>
      </div>
    </section>

    <section class="settings-section">
      <h2>📸 Comprovantes de VOD</h2>
      <p class="section-help">Defina por quantos dias os prints/fotos enviados pelo streamer ficam armazenados. Depois desse prazo, os arquivos são apagados automaticamente, mas o registro do envio permanece.</p>
      <div class="settings-grid">
        <?php $key='proof_retention_days'; $meta=$fields[$key]; $value=(int)($rows[$key] ?? 45); ?>
        <div class="setting-card">
          <label for="<?=$key?>"><?=htmlspecialchars($meta['label'])?></label>
          <div class="field-row"><input id="<?=$key?>" name="<?=$key?>" type="number" min="<?=$meta['min']?>" max="<?=$meta['max']?>" step="<?=$meta['step']?>" value="<?=$value?>" required><span class="suffix"><?=htmlspecialchars($meta['suffix'])?></span></div>
          <small><?=htmlspecialchars($meta['description'])?></small>
        </div>
      </div>
    </section>

    <div style="display:flex;gap:12px;align-items:center;margin:26px 0">
      <button class="btn primary" type="submit">💾 Salvar Configurações de Pontuação</button>
      <a class="btn" href="index.php">Cancelar</a>
    </div>
  </form>

  <div class="rules-note">
    <strong>ℹ️ Histórico:</strong> alterar os valores não modifica pontos já concedidos. As novas regras passam a valer para os próximos cálculos/aprovações.
  </div>
</main>

<footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
</body>
</html>
