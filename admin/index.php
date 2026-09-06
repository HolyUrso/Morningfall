<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$stats = [
    'streamers'=>0,'novatos'=>0,'oficiais'=>0,'afiliados'=>0,
    'vods'=>0,'points'=>0,'pending'=>0
];

try {
    $stats['streamers']=(int)$pdo->query("SELECT COUNT(*) FROM streamers WHERE active=1")->fetchColumn();
    $stats['novatos']=(int)$pdo->query("SELECT COUNT(*) FROM streamers WHERE category='novato' AND active=1")->fetchColumn();
    $stats['oficiais']=(int)$pdo->query("SELECT COUNT(*) FROM streamers WHERE category='oficial' AND active=1")->fetchColumn();
    $stats['afiliados']=(int)$pdo->query("SELECT COUNT(*) FROM streamers WHERE category='afiliado' AND active=1")->fetchColumn();
    $stats['vods']=(int)$pdo->query("SELECT COUNT(*) FROM vods")->fetchColumn();
    $stats['points']=(int)$pdo->query("SELECT COALESCE(SUM(points),0) FROM point_transactions WHERE points>0")->fetchColumn();
    $stats['pending']=(int)$pdo->query("SELECT COUNT(*) FROM redemptions WHERE status='pending'")->fetchColumn();
} catch (Throwable $e) {
    // Mantém o Dashboard disponível mesmo se uma consulta opcional falhar.
}

$inactive7=[];
$inactive21=[];
try {
    $inactiveRows=$pdo->query("SELECT s.id, s.name, s.category, s.joined_at, MAX(v.vod_date) AS last_vod_activity
        FROM streamers s
        LEFT JOIN vods v ON v.streamer_id=s.id
        WHERE s.active=1
        GROUP BY s.id, s.name, s.category, s.joined_at
        ORDER BY s.name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $today=new DateTime('today');
    foreach($inactiveRows as $ir){
        // Antes da primeira atividade, a contagem começa na data de cadastro.
        // Depois da primeira VOD/LIVE, passa a contar desde a última atividade registrada.
        $hasActivity = !empty($ir['last_vod_activity']);
        $referenceDate = $hasActivity ? $ir['last_vod_activity'] : ($ir['joined_at'] ?? null);
        $days=$referenceDate ? max(0, (int)(new DateTime($referenceDate))->diff($today)->days) : null;
        if($days!==null && $days>=7){
            $ir['inactive_days']=$days;
            $ir['last_activity']=$hasActivity ? $ir['last_vod_activity'] : null;
            $ir['activity_reference']=$hasActivity ? 'Última atividade' : 'Cadastro';
            if($days>=21) $inactive21[]=$ir; else $inactive7[]=$ir;
        }
    }
} catch(Throwable $e) {
    // Alertas são auxiliares e nunca devem impedir o acesso ao Dashboard.
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Dashboard</title><link rel="stylesheet" href="../assets/css/style.css"><style>
.master-section{grid-column:1/-1;margin-top:8px;padding-top:22px;border-top:1px solid #273247}.master-section-head{display:flex;align-items:end;justify-content:space-between;gap:16px;margin-bottom:14px}.master-section-head span{font-size:15px;font-weight:800;color:#f0f3fa}.master-section-head small{font-size:12px;color:#7f8da6}.master-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.master-card{position:relative;border-color:#3b315c!important}.master-card:hover{border-color:#6b4ca8!important}.master-card .setup-chip{background:#3a276b;color:#d8c9ff;border-color:#6549a9}@media(max-width:800px){.master-grid{grid-template-columns:1fr}.master-section-head{align-items:flex-start;flex-direction:column}}
.inactivity-alerts{grid-column:1/-1;margin-top:8px;display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.inactivity-card{border:1px solid #3b315c;background:#111722;border-radius:12px;padding:16px}.inactivity-card.critical{border-color:#63333f;background:rgba(110,35,50,.10)}.inactivity-head{display:flex;justify-content:space-between;align-items:center;gap:10px;margin-bottom:10px}.inactivity-head strong{font-size:15px;color:#fff}.inactivity-head span{font-size:11px;font-weight:800;padding:4px 8px;border-radius:999px;background:#3a276b;color:#d8c9ff;border:1px solid #6549a9}.inactivity-card.critical .inactivity-head span{background:#5a1f2b;color:#ffd1d8;border-color:#79303e}.inactivity-list{display:grid;gap:8px}.inactive-item{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:9px 0;border-top:1px solid #273247}.inactive-item:first-child{border-top:0}.inactive-name{font-weight:700;color:#fff}.inactive-meta{font-size:11px;color:#8f9db4;margin-top:2px}.inactive-days{font-weight:800;color:#f0f3fa;white-space:nowrap}.inactivity-empty{font-size:13px;color:#91a0b8;padding-top:4px}@media(max-width:800px){.inactivity-alerts{grid-template-columns:1fr}}
</style><style>.admin-section{grid-column:1/-1;margin-top:18px;width:100%}.admin-section .master-section-head{margin-bottom:10px}.admin-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}.admin-grid .setup-menu-card{min-height:88px}@media(max-width:800px){.admin-grid{grid-template-columns:1fr}}</style></head>
<body><header class="topbar"><div><b>MORNINGFALL</b> <span>CREATORS</span></div><div class="top-user"><?php if(!empty($_SESSION['staff_discord_avatar'])): ?><img class="discord-avatar-top" src="<?=htmlspecialchars($_SESSION['staff_discord_avatar'])?>" alt="Avatar Discord"><?php endif; ?><?=htmlspecialchars($_SESSION['staff_name'])?> · <a href="../logout.php">Sair</a></div></header>
<main class="container"><div class="page-head"><div><p class="eyebrow">PAINEL ADMINISTRATIVO</p><h1>Dashboard</h1></div></div>
<div class="stats">
<?php foreach([['STREAMERS',$stats['streamers']],['NOVATOS',$stats['novatos']],['OFICIAIS',$stats['oficiais']],['AFILIADOS',$stats['afiliados']],['VODS',$stats['vods']],['PONTOS GERADOS',$stats['points']],['RESGATES PENDENTES',$stats['pending']]] as $x): ?><div class="stat"><small><?=$x[0]?></small><strong><?=$x[1]?></strong></div><?php endforeach;?>
</div>
<div class="inactivity-alerts">
  <section class="inactivity-card">
    <div class="inactivity-head"><strong>⚠️ 7 dias sem atividade</strong><span>ADVERTÊNCIA / CONTATO</span></div>
    <div class="inactivity-list">
      <?php if(!$inactive7): ?><div class="inactivity-empty">Nenhum streamer ativo está há 7 a 20 dias sem atividade.</div><?php else: foreach($inactive7 as $ir): ?>
        <div class="inactive-item"><div><div class="inactive-name"><?=htmlspecialchars($ir['name'])?></div><div class="inactive-meta"><?=htmlspecialchars($ir['activity_reference'])?>: <?=htmlspecialchars($ir['last_activity'] ?: ($ir['joined_at'] ?: 'Não informado'))?></div></div><div class="inactive-days"><?=number_format((int)$ir['inactive_days'])?> dias</div></div>
      <?php endforeach; endif; ?>
    </div>
  </section>
  <section class="inactivity-card critical">
    <div class="inactivity-head"><strong>🚨 21 dias sem atividade</strong><span>AVALIAR TAG / PUNIÇÃO</span></div>
    <div class="inactivity-list">
      <?php if(!$inactive21): ?><div class="inactivity-empty">Nenhum streamer ativo está há 21 dias ou mais sem atividade.</div><?php else: foreach($inactive21 as $ir): ?>
        <div class="inactive-item"><div><div class="inactive-name"><?=htmlspecialchars($ir['name'])?></div><div class="inactive-meta"><?=htmlspecialchars($ir['activity_reference'])?>: <?=htmlspecialchars($ir['last_activity'] ?: ($ir['joined_at'] ?: 'Não informado'))?></div></div><div class="inactive-days"><?=number_format((int)$ir['inactive_days'])?> dias</div></div>
      <?php endforeach; endif; ?>
    </div>
  </section>
</div>
<div class="grid-menu">
<a href="streamers.php">👤 Streamers<small>Cadastro e perfis</small></a><a href="vods.php">🎥 VODs<small>Lives e horas</small></a><a href="submissoes.php">📨 Conteúdos Enviados<small>VODs e redes sociais</small></a><a href="bonus.php">🎁 Bonificação Extra<small>Viralização e bônus manual</small></a><a href="../streamer/regras.php" target="_blank">📜 Regras de Pontuação<small>Visualizar regras</small></a><a href="comandos_lista.php">⚡ Comandos Staff<small>Comandos administrativos</small></a><a href="atividades.php">⭐ Atividades<small>Bonificações</small></a><a href="pontuacao.php">📊 Pontuação<small>Histórico</small></a><a href="relatorios.php">📊 Relatórios<small>Desempenho mensal</small></a><a href="relatorio_streamers.php">📄 Streamers Ativos<small>Planilha em PDF</small><span class="setup-chip">PDF</span></a><a href="ranking.php">🏆 Ranking<small>Classificação dos streamers</small></a><a href="sorteio.php">🎟️ Sorteio<small>Configurar evento e prêmio</small><span class="setup-chip">SETUP</span></a><a href="penalidades.php">⚠️ Penalidades<small>Advertências</small></a><a href="premios.php">🎁 Prêmios<small>Troca de pontos</small></a><a href="resgates.php">📦 Resgates<small>Pedidos</small></a><a href="aparencia.php">🎨 Aparência do Streamer<small>Logo e background</small><span class="setup-chip">SETUP</span></a><?php if(($_SESSION["staff_role"] ?? "")==="master"): ?><div class="master-section"><div class="master-section-head"><span>🔐 Área Master</span><small>Acessos exclusivos do Master</small></div><div class="master-grid"><a href="configuracoes.php" class="setup-menu-card master-card">⚙️ Configurações<small>Regras de pontuação</small><span class="setup-chip">MASTER</span></a><a href="staff.php" class="setup-menu-card master-card">👥 Staff<small>Equipe e acessos</small><span class="setup-chip">MASTER</span></a><a href="equipe_site.php" class="setup-menu-card master-card">🌟 Equipe do Site<small>Liderança e colaboradores públicos</small><span class="setup-chip">MASTER</span></a><a href="precificacao.php" class="setup-menu-card master-card">💰 Precificação<small>Categorias, produtos e importação TXT</small><span class="setup-chip">MASTER</span></a><a href="empresas.php" class="setup-menu-card master-card">🏢 Empresas do Portal<small>Cadastro, fotos, categoria e disponibilidade</small><span class="setup-chip">MASTER</span></a><a href="relatorio_empresas_pdf.php" class="setup-menu-card master-card">📄 Relatório de Empresas<small>Baixar todas as empresas em PDF</small><span class="setup-chip">PDF</span></a><a href="discord.php" class="setup-menu-card master-card">🔗 Discord / Relay<small>Cloudflare e Secret</small><span class="setup-chip">MASTER</span></a><a href="discord_ranking.php" class="setup-menu-card master-card">🏆 Ranking Discord<small>Carmesim Creators • Top 10</small><span class="setup-chip">MASTER</span></a><a href="backup.php" class="setup-menu-card master-card">🗄️ Backups<small>Gerar, baixar e enviar</small><span class="setup-chip">MASTER</span></a><a href="auditoria.php" class="setup-menu-card master-card">🧾 Logs de Alterações<small>Auditoria e relatórios PDF/XLS</small><span class="setup-chip">MASTER</span></a></div></div><div class="admin-section"><div class="master-section-head"><span>🛠️ Área Administrativa</span><small>Ferramentas administrativas separadas</small></div><div class="admin-grid"><a href="comandos.php" class="setup-menu-card master-card">⚡ Comandos Administrativos<small>Criar, editar e excluir comandos da Staff</small><span class="setup-chip">MASTER</span></a><a href="comandos_players.php" class="setup-menu-card master-card">👥 Comandos de Players<small>Criar, editar e excluir comandos de jogadores</small><span class="setup-chip">MASTER</span></a></div></div><?php endif; ?>
</div><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer></main></body></html>
