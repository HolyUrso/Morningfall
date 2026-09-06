<?php
require_once __DIR__ . '/config/database.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS player_commands (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    command VARCHAR(150) NOT NULL UNIQUE,
    description VARCHAR(500) NOT NULL,
    position INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_player_commands_active(active),
    INDEX idx_player_commands_position(position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// PLAYER_COMMAND_DESCRIPTION_MIGRATION
// Atualiza apenas descrições antigas conhecidas, preservando edições personalizadas.
$playerDescriptionUpdates = [
    '/painel' => ['Abre o Menu de painel médico', 'Abre o menu principal do painel médico para acessar as ferramentas e funções disponíveis aos médicos.'],
    '/cuff' => ['Algema outro jogador, permitindo realizar a contenção durante uma abordagem ou procedimento da Cavalaria.', 'Algema outro jogador, permitindo realizar a contenção durante uma abordagem ou procedimento da Cavalaria.'],
    '/escoltar' => ['Escolta de uso da Cavalaria', 'Permite escoltar outro jogador, mantendo-o sob acompanhamento durante uma abordagem ou condução.'],
    '/revistar' => ['Revista usada da Cavalaria', 'Realiza a revista de outro jogador para verificar seus pertences durante uma abordagem da Cavalaria.'],
    '/lawbadge' => ['Ajuste distintivo da Cavalaria', 'Exibe ou ajusta o distintivo da Cavalaria, permitindo identificar o agente durante o serviço.'],
    '/prender' => ['Abrir menu de Prisão da Cavalaria', 'Permite realizar a prisão e condução de um jogador conforme os procedimentos da Cavalaria.'],
    '/mdt' => ['Painel Policial', 'Abre o Painel Policial (MDT), utilizado pela Cavalaria para consultar e acessar informações do serviço.'],
    '/pid' => ['Cavalaria verifica documentação', 'Permite verificar a documentação e identificação de um cidadão durante uma abordagem da Cavalaria.'],
    '/vercarroca' => ['Revistar Carroça', 'Permite revistar uma carroça para verificar seus compartimentos e possíveis itens transportados.'],
    '/chamarcarroca' => ['Opção de Chamar Carroça', 'Solicita uma carroça para atendimento ou transporte, conforme as funções disponíveis ao jogador.'],
    '/sleeve' => ['Levantar mangas das camisas', 'Levanta as mangas da camisa, alterando a aparência do personagem para determinadas situações ou animações.'],
    '/gc' => ['Guarda a Carroça', 'Guarda a carroça utilizada pelo jogador, retirando-a do local após o uso.'],
    '/animacao' => ['Menu de Animação', 'Abre o menu de animações disponíveis para o personagem, permitindo escolher diferentes ações e poses.'],
    '/anuncio' => ['Para Médicos, Cavalaria, Prefeitura e Maquinista', 'Envia um anúncio público relacionado aos serviços de Médicos, Cavalaria, Prefeitura e Maquinistas.'],
    '/inspecao' => ['Inspecionar a arma', 'Inicia uma inspeção da arma equipada, permitindo verificar o armamento utilizado pelo personagem.'],
];
try {
    $upd = $pdo->prepare('UPDATE player_commands SET description=? WHERE command=? AND description=?');
    foreach ($playerDescriptionUpdates as $cmd => [$old, $new]) { $upd->execute([$new, $cmd, $old]); }
} catch (Throwable $e) {}

if ((int)$pdo->query('SELECT COUNT(*) FROM player_commands')->fetchColumn() === 0) {
    $st=$pdo->prepare('INSERT IGNORE INTO player_commands(name,command,description,position,active) VALUES(?,?,?,?,1)');
    $st->execute(['Algemar','/cuff','Comando utilizado pela Cavalaria para algemar outro jogador.',1]);
}
$rows=$pdo->query('SELECT name,command,description FROM player_commands WHERE active=1 ORDER BY position ASC,id ASC')->fetchAll(PDO::FETCH_ASSOC);
$brand=$pdo->query('SELECT top_name,eyebrow_name FROM staff_commands_settings WHERE id=1')->fetch(PDO::FETCH_ASSOC) ?: ['top_name'=>'CARMESIM STAFF','eyebrow_name'=>'CARMESIM ROLEPLAY · STAFF'];
$publicLogo=''; $publicBackground=''; try { $st=$pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1'); $st->execute(['players_public_logo']); $publicLogo=(string)($st->fetchColumn() ?: ''); $st->execute(['players_public_background']); $publicBackground=(string)($st->fetchColumn() ?: ''); } catch(Throwable $e) {}
$logoUrl=$publicLogo; $backgroundUrl=$publicBackground;
function e($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Comandos Básicos de Players · <?=e($brand['top_name'])?></title><link rel="stylesheet" href="assets/css/style.css"><style>
:root{--nv-gold:#d7a93d;--nv-gold2:#f0c96b;--nv-cream:#f4ead5;--nv-dark:#090a0c;--nv-border:rgba(215,169,61,.35)}
html,body{min-height:100%;background:#080a0c;color:var(--nv-cream)} body{font-family:Poppins,Arial,sans-serif;overflow-x:hidden}.background{display:none!important}
html,body{background:#090a0c!important}
.carmesim-player-page{position:relative;isolation:isolate;min-height:100vh;padding:42px 24px 80px;max-width:1180px;margin:0 auto}.carmesim-player-page:before{content:'';position:fixed;inset:0;z-index:-2;background:#090a0c var(--nv-bg) center/cover no-repeat}.carmesim-player-page:after{content:'';position:fixed;inset:0;z-index:-1;background:linear-gradient(rgba(8,9,10,.48),rgba(8,7,6,.88)),radial-gradient(circle at 50% 15%,rgba(215,169,61,.16),transparent 42%);pointer-events:none}.carmesim-player-page>.carmesim-hero,.carmesim-player-page>.carmesim-panel{position:relative;z-index:1}
.carmesim-hero{position:relative;text-align:center;padding:32px 26px 30px;margin-bottom:20px;background:linear-gradient(180deg,rgba(27,20,14,.86),rgba(15,12,10,.93));border:1px solid var(--nv-border);box-shadow:0 18px 50px rgba(0,0,0,.42)}.carmesim-hero:before{content:'';position:absolute;inset:8px;border:1px solid rgba(215,169,61,.12);pointer-events:none}.carmesim-logo{position:relative;display:flex;justify-content:center;align-items:center;min-height:120px;margin-bottom:12px}.carmesim-logo img{max-width:min(520px,82vw);max-height:210px;object-fit:contain;filter:drop-shadow(0 8px 16px rgba(0,0,0,.55))}.carmesim-logo-fallback{font-family:Georgia,serif;font-size:46px;font-weight:800;letter-spacing:6px;color:var(--nv-gold2);text-shadow:0 3px 12px #000}.carmesim-eyebrow{color:var(--nv-gold2);letter-spacing:4px;font-size:12px;font-weight:700;margin:0 0 8px;text-transform:uppercase}.carmesim-hero h1{font-family:Georgia,'Times New Roman',serif;color:var(--nv-cream);font-size:34px;letter-spacing:.5px;margin:0 0 9px;text-shadow:0 3px 12px #000}.carmesim-hero>p:last-child{color:#cdbfa9;margin:0;line-height:1.6}.carmesim-reminder{display:inline-block;margin-top:18px;padding:10px 16px;border:1px solid rgba(215,169,61,.45);background:rgba(70,46,27,.58);color:#e7d3ad;font-size:13px;line-height:1.5;box-shadow:inset 0 1px 0 rgba(255,255,255,.03)}.carmesim-reminder strong{color:var(--nv-gold2)}
.carmesim-panel{background:linear-gradient(180deg,rgba(24,18,13,.9),rgba(14,12,10,.94));border:1px solid var(--nv-border);padding:25px;box-shadow:0 18px 48px rgba(0,0,0,.38)}.carmesim-panel h2{font-family:Georgia,'Times New Roman',serif;color:var(--nv-gold2);font-size:24px;margin:0 0 20px;text-align:center;letter-spacing:1px}.carmesim-panel h2:after{content:'';display:block;width:150px;height:1px;margin:13px auto 0;background:linear-gradient(90deg,transparent,var(--nv-gold),transparent)}.carmesim-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}.carmesim-card{position:relative;background:linear-gradient(145deg,rgba(43,29,19,.92),rgba(19,16,13,.95));border:1px solid rgba(215,169,61,.24);padding:18px;min-height:150px;box-shadow:inset 0 1px 0 rgba(255,255,255,.03),0 8px 22px rgba(0,0,0,.24);transition:.16s}.carmesim-card:hover{transform:translateY(-2px);border-color:rgba(240,201,107,.58)}.carmesim-card:after{content:'';position:absolute;left:0;right:0;bottom:0;height:3px;background:linear-gradient(90deg,transparent,var(--nv-gold),transparent);opacity:.7}.carmesim-card-top{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:13px}.carmesim-card strong{color:var(--nv-gold2);font:700 18px/1.25 Georgia,'Times New Roman',serif}.carmesim-command{color:#f4f0e8;font:700 15px/1.3 Consolas,monospace;margin-bottom:10px}.carmesim-card p{margin:0;color:#c3b8a8;line-height:1.55;font-size:12px}.copy-command{border:1px solid rgba(215,169,61,.55);background:rgba(88,57,36,.72);color:var(--nv-cream);padding:7px 10px;font:700 11px Poppins,sans-serif;cursor:pointer}.copy-command:hover{background:rgba(119,77,45,.9)}.copy-command.copied{background:#426b32;border-color:#78a85e;color:#fff}.carmesim-footer{background:rgba(8,9,10,.9)!important;border-top:1px solid rgba(215,169,61,.25)!important;color:#9f927f!important;text-align:center;padding:18px 20px!important;font-size:12px}.carmesim-footer a{color:var(--nv-gold2);text-decoration:none;font-weight:700}
@media(max-width:900px){.carmesim-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:600px){.carmesim-player-page{padding:22px 12px 50px}.carmesim-grid{grid-template-columns:1fr}.carmesim-hero{padding:28px 16px}.carmesim-hero h1{font-size:28px}.carmesim-logo{min-height:90px}.carmesim-logo-fallback{font-size:32px}}
</style></head><body><main class="carmesim-player-page" style="--nv-bg: <?= $backgroundUrl !== "" ? "url('" . e($backgroundUrl) . "')" : "none" ?>"><section class="carmesim-hero"><div class="carmesim-logo"><?php if($logoUrl): ?><img src="<?=e($logoUrl)?>" alt="Logo Carmesim"><?php else: ?><div class="carmesim-logo-fallback">CARMESIM</div><?php endif; ?></div><p class="carmesim-eyebrow">COMANDOS CARMESIM</p><h1>👥 Comandos Básicos de Players</h1><p>Consulte os comandos utilizados pelos jogadores. A descrição informa para qual profissão ou função o comando serve.</p><div class="carmesim-reminder">⚠️ <strong>Lembrete:</strong> Demais comandos pelo <strong>F6 - Radial Menu</strong>.</div></section><section class="carmesim-panel"><h2>👥 COMANDOS BÁSICOS DE PLAYERS</h2><?php if(!$rows):?><div style="color:#aeb8c4">Nenhum comando ativo cadastrado.</div><?php else:?><div class="carmesim-grid"><?php foreach($rows as $r):?><article class="carmesim-card"><div class="carmesim-card-top"><strong><?=e($r['name'])?></strong><button type="button" class="copy-command" data-command="<?=e($r['command'])?>" onclick="copyCommand(this)">📋 Copiar</button></div><div class="carmesim-command"><?=e($r['command'])?></div><p><?=e($r['description'])?></p></article><?php endforeach;?></div><?php endif;?></section></main><footer class="site-footer carmesim-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer><script>async function copyCommand(btn){try{await navigator.clipboard.writeText(btn.dataset.command||'');btn.textContent='✓ Copiado';btn.classList.add('copied');setTimeout(()=>{btn.textContent='📋 Copiar';btn.classList.remove('copied')},1200)}catch(e){alert('Não foi possível copiar o comando.')}};</script></body></html>
