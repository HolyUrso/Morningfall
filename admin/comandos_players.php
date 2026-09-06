<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';

if (($_SESSION['staff_role'] ?? '') !== 'master') {
    http_response_code(403);
    exit('Acesso restrito ao Staff Master.');
}

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
    CONSTRAINT fk_player_commands_created_by FOREIGN KEY(created_by) REFERENCES staff_users(id) ON DELETE SET NULL,
    INDEX idx_player_commands_active(active),
    INDEX idx_player_commands_position(position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// PLAYER_COMMAND_DESCRIPTION_MIGRATION
// Atualiza apenas descrições antigas conhecidas, preservando edições personalizadas.
$playerDescriptionUpdates = [
    '/painel' => ['Abre o Menu de painel médico', 'Abre o menu principal do painel médico para acessar as ferramentas e funções disponíveis aos médicos.'],
    '/cuff' => ['Comando utilizado pela Cavalaria para algemar outro jogador.', 'Algema outro jogador, permitindo realizar a contenção durante uma abordagem ou procedimento da Cavalaria.'],
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

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function normalizeCommand($value){
    $value = trim((string)$value);
    if ($value === '') return '';
    return '/' . ltrim($value, '/');
}
function compactPlayerOrder(PDO $pdo): void {
    $ids = array_map('intval', $pdo->query('SELECT id FROM player_commands ORDER BY position ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN));
    $up = $pdo->prepare('UPDATE player_commands SET position=? WHERE id=?');
    foreach ($ids as $pos => $id) $up->execute([$pos + 1, $id]);
}
function reorderPlayer(PDO $pdo, int $id, int $requested): void {
    $ids = array_map('intval', $pdo->query('SELECT id FROM player_commands ORDER BY position ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN));
    $ids = array_values(array_filter($ids, fn($v) => $v !== $id));
    $index = $requested <= 0 ? count($ids) : min(max($requested - 1, 0), count($ids));
    array_splice($ids, $index, 0, [$id]);
    $up = $pdo->prepare('UPDATE player_commands SET position=? WHERE id=?');
    foreach ($ids as $pos => $pid) $up->execute([$pos + 1, $pid]);
}

if ((int)$pdo->query('SELECT COUNT(*) FROM player_commands')->fetchColumn() === 0) {
    $st = $pdo->prepare('INSERT IGNORE INTO player_commands(name,command,description,position,active,created_by) VALUES(?,?,?,?,1,?)');
    $st->execute([
        'Algemar', '/cuff',
        'Comando utilizado pela Cavalaria para algemar outro jogador.',
        1, (int)$_SESSION['staff_id']
    ]);
}

$msg=''; $error='';

// Aparência exclusiva da página pública de Comandos Básicos de Players.
$pdo->exec("CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$playersUploadDir = __DIR__ . '/../assets/uploads/players_public';
$playersUploadWeb = 'assets/uploads/players_public/';
if (!is_dir($playersUploadDir)) @mkdir($playersUploadDir, 0755, true);

/** Converte a imagem enviada para WEBP antes de salvar. */
function convertPlayerPublicImageToWebp(string $source, string $destination, int $quality=88): void {
    $raw = @file_get_contents($source);
    if ($raw === false) throw new RuntimeException('Não foi possível ler a imagem enviada.');

    if (function_exists('imagecreatefromstring') && function_exists('imagewebp')) {
        $img = @imagecreatefromstring($raw);
        if ($img !== false) {
            if (function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($img);
            if (function_exists('imagealphablending')) imagealphablending($img, false);
            if (function_exists('imagesavealpha')) imagesavealpha($img, true);
            if (!@imagewebp($img, $destination, $quality)) {
                imagedestroy($img);
                throw new RuntimeException('O servidor não conseguiu converter a imagem para WEBP.');
            }
            imagedestroy($img);
            return;
        }
    }

    if (class_exists('Imagick')) {
        try {
            $im = new Imagick();
            $im->readImageBlob($raw);
            $im->setImageFormat('webp');
            $im->setImageCompressionQuality($quality);
            if (!$im->writeImage($destination)) throw new RuntimeException('Falha ao salvar WEBP.');
            $im->clear();
            $im->destroy();
            return;
        } catch (Throwable $e) {
            throw new RuntimeException('O servidor não possui suporte disponível para conversão em WEBP.');
        }
    }

    throw new RuntimeException('O servidor não possui suporte disponível para conversão em WEBP.');
}
function playerPublicSetting(PDO $pdo, string $key, string $default=''): string {
    $st=$pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
    $st->execute([$key]);
    $v=$st->fetchColumn();
    return $v===false ? $default : (string)$v;
}
function savePlayerPublicSetting(PDO $pdo,string $key,string $value,string $description=''): void {
    $st=$pdo->prepare('INSERT INTO settings(setting_key,setting_value,description) VALUES(?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), description=VALUES(description)');
    $st->execute([$key,$value,$description]);
}
$playerPublicLogo = playerPublicSetting($pdo,'players_public_logo','');
$playerPublicBackground = playerPublicSetting($pdo,'players_public_background','');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'upload_public_logo' || $action === 'upload_public_background') {
            $field = $action === 'upload_public_logo' ? 'logo' : 'background';
            if (empty($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Selecione uma imagem válida.');
            }
            $file = $_FILES[$field];
            if ($file['size'] > 8 * 1024 * 1024) throw new RuntimeException('A imagem deve ter no máximo 8 MB.');
            $fi = new finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($file['tmp_name']);
            $allowed = ['image/png','image/jpeg','image/webp'];
            if (!in_array($mime, $allowed, true)) throw new RuntimeException('Formato inválido. Use PNG, JPG ou WEBP.');
            if (@getimagesize($file['tmp_name']) === false) throw new RuntimeException('O arquivo enviado não é uma imagem válida.');

            // Sempre converte para WEBP, independentemente do formato enviado.
            $name = $field.'_'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'.webp';
            $destination = $playersUploadDir.'/'.$name;
            convertPlayerPublicImageToWebp($file['tmp_name'], $destination, $field === 'background' ? 86 : 90);
            $rel = $playersUploadWeb.$name;
            if ($field === 'logo') {
                if ($playerPublicLogo && str_starts_with($playerPublicLogo, $playersUploadWeb)) @unlink(__DIR__.'/../'.$playerPublicLogo);
                savePlayerPublicSetting($pdo,'players_public_logo',$rel,'Logo da página pública de Comandos Básicos de Players');
                $playerPublicLogo=$rel;
                $msg='Logo da página pública atualizada.';
                auditLog($pdo,'Alterou logo da página de comandos de players','Comandos Players',null,'Logo','Logo pública atualizada',null,['path'=>$rel]);
            } else {
                if ($playerPublicBackground && str_starts_with($playerPublicBackground, $playersUploadWeb)) @unlink(__DIR__.'/../'.$playerPublicBackground);
                savePlayerPublicSetting($pdo,'players_public_background',$rel,'Background da página pública de Comandos Básicos de Players');
                $playerPublicBackground=$rel;
                $msg='Background da página pública atualizado.';
                auditLog($pdo,'Alterou background da página de comandos de players','Comandos Players',null,'Background','Background público atualizado',null,['path'=>$rel]);
            }
        }
        if ($action === 'remove_public_logo') {
            if ($playerPublicLogo && str_starts_with($playerPublicLogo, $playersUploadWeb)) @unlink(__DIR__.'/../'.$playerPublicLogo);
            savePlayerPublicSetting($pdo,'players_public_logo','','Logo da página pública de Comandos Básicos de Players');
            $playerPublicLogo=''; $msg='Logo removida.';
        }
        if ($action === 'remove_public_background') {
            if ($playerPublicBackground && str_starts_with($playerPublicBackground, $playersUploadWeb)) @unlink(__DIR__.'/../'.$playerPublicBackground);
            savePlayerPublicSetting($pdo,'players_public_background','','Background da página pública de Comandos Básicos de Players');
            $playerPublicBackground=''; $msg='Background removido. A página usará o fundo padrão Carmesim.';
        }

        if ($action === 'create') {
            $name = trim((string)($_POST['name'] ?? ''));
            $command = normalizeCommand($_POST['command'] ?? '');
            $description = trim((string)($_POST['description'] ?? ''));
            $position = max(0, (int)($_POST['position'] ?? 0));
            if ($name === '' || mb_strlen($name) > 120) throw new RuntimeException('Informe um nome de comando válido.');
            if ($command === '' || !preg_match('/^\/[a-zA-Z0-9_\-]+(?:\s+[a-zA-Z0-9_\-]+)*$/', $command)) throw new RuntimeException('Informe um comando válido, por exemplo: /cuff.');
            if ($description === '') throw new RuntimeException('Informe a descrição do comando.');
            if ($position <= 0) $position = (int)$pdo->query('SELECT COALESCE(MAX(position),0)+1 FROM player_commands')->fetchColumn();
            $pdo->beginTransaction();
            try {
                $st=$pdo->prepare('INSERT INTO player_commands(name,command,description,position,active,created_by) VALUES(?,?,?,?,1,?)');
                $st->execute([$name,$command,$description,$position,(int)$_SESSION['staff_id']]);
                $newId=(int)$pdo->lastInsertId();
                reorderPlayer($pdo,$newId,$position);
                $pdo->commit();
            } catch(Throwable $e){ if($pdo->inTransaction())$pdo->rollBack(); throw $e; }
            auditLog($pdo,'Criou comando de player','Comandos Players',null,$name,'Comando criado',null,['name'=>$name,'command'=>$command,'description'=>$description,'position'=>$position]);
            $msg='Comando de player criado com sucesso.';
        }
        if ($action === 'edit') {
            $id=(int)($_POST['id']??0); $name=trim((string)($_POST['name']??'')); $command=normalizeCommand($_POST['command']??''); $description=trim((string)($_POST['description']??'')); $position=max(0,(int)($_POST['position']??0));
            if($id<=0)throw new RuntimeException('Comando inválido.');
            if($name===''||mb_strlen($name)>120)throw new RuntimeException('Informe um nome válido.');
            if($command===''||!preg_match('/^\/[a-zA-Z0-9_\-]+(?:\s+[a-zA-Z0-9_\-]+)*$/',$command))throw new RuntimeException('Informe um comando válido.');
            if($description==='')throw new RuntimeException('Informe a descrição.');
            $old=$pdo->prepare('SELECT * FROM player_commands WHERE id=?');$old->execute([$id]);$before=$old->fetch(PDO::FETCH_ASSOC);if(!$before)throw new RuntimeException('Comando não encontrado.');
            $requested=$position>0?$position:(int)$before['position'];
            $st=$pdo->prepare('UPDATE player_commands SET name=?,command=?,description=? WHERE id=?');$st->execute([$name,$command,$description,$id]);
            reorderPlayer($pdo,$id,$requested);
            auditLog($pdo,'Editou comando de player','Comandos Players',null,$name,'Comando alterado',$before,['name'=>$name,'command'=>$command,'description'=>$description,'position'=>$requested,'active'=>(int)$before['active']]);
            $msg='Comando de player atualizado.';
        }
        if ($action === 'toggle') {
            $id=(int)($_POST['id']??0);$st=$pdo->prepare('SELECT * FROM player_commands WHERE id=?');$st->execute([$id]);$row=$st->fetch(PDO::FETCH_ASSOC);if(!$row)throw new RuntimeException('Comando não encontrado.');
            $new=(int)!((int)$row['active']);$up=$pdo->prepare('UPDATE player_commands SET active=? WHERE id=?');$up->execute([$new,$id]);
            auditLog($pdo,$new?'Ativou comando de player':'Desativou comando de player','Comandos Players',null,$row['name'],'Status alterado',['active'=>(int)$row['active']],['active'=>$new]);$msg=$new?'Comando ativado.':'Comando desativado.';
        }
        if ($action === 'delete') {
            $id=(int)($_POST['id']??0);$st=$pdo->prepare('SELECT * FROM player_commands WHERE id=?');$st->execute([$id]);$row=$st->fetch(PDO::FETCH_ASSOC);if(!$row)throw new RuntimeException('Comando não encontrado.');
            $pdo->prepare('DELETE FROM player_commands WHERE id=?')->execute([$id]);compactPlayerOrder($pdo);
            auditLog($pdo,'Excluiu comando de player','Comandos Players',null,$row['name'],'Comando excluído',$row,null);$msg='Comando excluído e ordem reorganizada.';
        }
    } catch (Throwable $e) {
        $error = $e instanceof PDOException && $e->errorInfo[1] == 1062 ? 'Já existe um comando com esse endereço.' : $e->getMessage();
    }
}
$rows=$pdo->query('SELECT * FROM player_commands ORDER BY position ASC,id ASC')->fetchAll(PDO::FETCH_ASSOC);
$brand=$pdo->query('SELECT top_name,eyebrow_name FROM staff_commands_settings WHERE id=1')->fetch(PDO::FETCH_ASSOC) ?: ['top_name'=>'CARMESIM STAFF','eyebrow_name'=>'CARMESIM ROLEPLAY · STAFF'];
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Comandos de Players · <?=e($brand['top_name'])?></title><link rel="stylesheet" href="../assets/css/style.css"><style>
.public-look-panel{margin-bottom:18px}.public-look-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.public-look-card{background:#111722;border:1px solid #2a475e;border-radius:10px;padding:16px}.public-look-card h3{margin:0 0 5px;color:#fff;font-size:15px}.public-look-preview{margin:12px 0;border:1px solid #2a475e;background:#0b1118;min-height:120px;display:flex;align-items:center;justify-content:center;overflow:hidden}.public-look-preview.logo img{max-width:100%;max-height:170px;object-fit:contain}.public-look-preview.background{min-height:150px;background-size:cover;background-position:center}.upload-line{display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:10px 0}.upload-line input[type=file]{max-width:100%}.public-look-card form:not(.upload-line){margin:8px 0}..player-note{background:#16202d;border:1px solid #2a475e;padding:14px 16px;color:#aeb8c4;margin-bottom:18px}.player-form-grid{display:grid;grid-template-columns:1.1fr 1fr 2fr 100px;gap:10px;align-items:end}.player-list{display:grid;gap:10px;margin-top:18px}.player-row{background:#111722;border:1px solid #252e3e;border-radius:10px;padding:15px}.player-row-head{display:flex;justify-content:space-between;gap:15px;align-items:flex-start}.player-name{font-size:16px;font-weight:800;color:#fff}.player-command{font:700 16px Consolas,monospace;color:#66c0f4;margin-top:3px}.player-desc{color:#9eabc0;font-size:12px;margin-top:7px}.player-order{color:#718096;font-size:10px;margin-top:7px}.player-actions{display:flex;gap:7px;flex-wrap:wrap;margin-top:12px}.player-actions form{display:inline}.active-pill{font-size:10px;padding:4px 8px;border-radius:999px;background:#123b2a;color:#5be38a}.inactive-pill{font-size:10px;padding:4px 8px;border-radius:999px;background:#3a2025;color:#ff8f9a}.msg{padding:10px 12px;border-radius:8px;margin-bottom:14px;background:#123426;color:#76e2a0;border:1px solid #245e46}.err{padding:10px 12px;border-radius:8px;margin-bottom:14px;background:#3a2025;color:#ff9ba3;border:1px solid #6c3039}@media(max-width:900px){.player-form-grid{grid-template-columns:1fr 1fr}.player-form-grid textarea{grid-column:1/-1}}@media(max-width:900px){.public-look-grid{grid-template-columns:1fr}}@media(max-width:600px){.player-form-grid{grid-template-columns:1fr}}
</style></head><body><header class="topbar"><div><b><?=e(strtok($brand['top_name'],' '))?></b> <span><?=e(trim(substr($brand['top_name'],strlen(strtok($brand['top_name'],' ')))))?></span></div><div><?=e($_SESSION['staff_name']??'')?> · <a href="../logout.php">Sair</a></div></header>
<main class="container"><a href="index.php">← Dashboard</a><div class="page-head"><div><p class="eyebrow">ÁREA ADMINISTRATIVA</p><h1>👥 Comandos Básicos de Players</h1><p class="muted">Cadastre os comandos utilizados pelos jogadores. A finalidade de cada comando fica na própria descrição.</p></div></div>
<?php if($msg):?><div class="msg">✅ <?=e($msg)?></div><?php endif;?><?php if($error):?><div class="err">❌ <?=e($error)?></div><?php endif;?>
<section class="panel public-look-panel"><div class="section-title"><div><p class="eyebrow">APARÊNCIA PÚBLICA</p><h2>🎨 Página Carmesim dos Players</h2><p class="muted">Essas imagens afetam somente a página pública <b>Comandos Básicos de Players</b>.</p></div><span class="setup-badge">MASTER</span></div><div class="public-look-grid"><div class="public-look-card"><h3>🖼️ Logo / Ícone da Carmesim</h3><p class="muted">Será exibida centralizada acima do título.</p><?php if($playerPublicLogo): ?><div class="public-look-preview logo"><img src="../<?=e($playerPublicLogo)?>" alt="Logo Carmesim"></div><?php endif; ?><form method="post" enctype="multipart/form-data" class="upload-line"><input type="hidden" name="action" value="upload_public_logo"><input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required><button class="btn primary">⬆ Subir logo</button></form><?php if($playerPublicLogo): ?><form method="post"><input type="hidden" name="action" value="remove_public_logo"><button class="btn secondary">🗑 Remover logo</button></form><?php endif; ?><small class="muted">PNG, JPG ou WEBP no envio · convertido automaticamente para WEBP · recomendado até 600×300px.</small></div><div class="public-look-card"><h3>🌄 Background Carmesim</h3><p class="muted">Será usado somente nesta tela pública, com uma camada escura para leitura.</p><?php if($playerPublicBackground): ?><div class="public-look-preview background" style="background-image:url('../<?=e($playerPublicBackground)?>')"></div><?php endif; ?><form method="post" enctype="multipart/form-data" class="upload-line"><input type="hidden" name="action" value="upload_public_background"><input type="file" name="background" accept="image/png,image/jpeg,image/webp" required><button class="btn primary">⬆ Subir background</button></form><?php if($playerPublicBackground): ?><form method="post"><input type="hidden" name="action" value="remove_public_background"><button class="btn secondary">🗑 Remover background</button></form><?php endif; ?><small class="muted">JPG, PNG ou WEBP no envio · convertido automaticamente para WEBP · recomendado 1920×1080px ou maior.</small></div></div></section>
<div class="player-note"><strong>Exemplo:</strong> <b>Algemar</b> · <code>/cuff</code> · <span>Comando utilizado pela Cavalaria para algemar outro jogador.</span><br><small>Não existe campo de categoria. Use a descrição para informar a que profissão/função o comando pertence.</small></div>
<section class="panel"><div class="section-title"><div><p class="eyebrow">NOVO COMANDO</p><h2>Adicionar comando de player</h2></div><span class="setup-badge">MASTER</span></div><form method="post" class="form"><input type="hidden" name="action" value="create"><div class="player-form-grid"><div><label>Nome do comando</label><input name="name" placeholder="Algemar" required></div><div><label>Comando</label><input name="command" placeholder="/cuff" required></div><div><label>Descrição</label><input name="description" placeholder="Comando utilizado pela Cavalaria para algemar outro jogador." required></div><div><label>Ordem</label><input type="number" name="position" min="0" value="0"><small class="muted">0 = automática</small></div></div><div style="margin-top:12px"><button class="btn primary">+ Cadastrar comando</button></div></form></section>
<section class="panel" style="margin-top:18px"><div class="section-title"><div><p class="eyebrow">CADASTRADOS</p><h2>Comandos de Players</h2></div><span class="muted"><?=count($rows)?> comando(s)</span></div><div class="player-list"><?php if(!$rows):?><div class="player-note">Nenhum comando cadastrado.</div><?php else: foreach($rows as $r):?><article class="player-row"><div class="player-row-head"><div><div class="player-name"><?=e($r['name'])?></div><div class="player-command"><?=e($r['command'])?></div><div class="player-desc"><?=e($r['description'])?></div><div class="player-order">Ordem: <?=e($r['position'])?></div></div><span class="<?=((int)$r['active']?'active-pill':'inactive-pill')?>"><?=((int)$r['active']?'ATIVO':'INATIVO')?></span></div><div class="player-actions"><button type="button" class="btn secondary" onclick='openPlayerEdit(<?=json_encode($r,JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>)'>✏️ Editar</button><form method="post"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn toggle"><?=((int)$r['active']?'⏸ Desativar':'▶ Ativar')?></button></form><form method="post" onsubmit="return confirm('Excluir este comando de player permanentemente?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn delete">🗑 Excluir</button></form></div></article><?php endforeach;endif;?></div></section>
</main><footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
<div id="editPlayerModal" class="modal-overlay" style="display:none"><div class="modal-card"><div class="section-title"><div><p class="eyebrow">EDITAR</p><h2>Editar comando de player</h2></div><button type="button" class="modal-close" onclick="closePlayerEdit()">×</button></div><form method="post" class="form"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="pe_id"><label>Nome do comando</label><input name="name" id="pe_name" required><label>Comando</label><input name="command" id="pe_command" required><label>Descrição</label><textarea name="description" id="pe_description" rows="4" required></textarea><label>Ordem</label><input type="number" name="position" id="pe_position" min="0"><div style="margin-top:12px"><button class="btn primary">💾 Salvar alterações</button></div></form></div></div>
<script>function openPlayerEdit(r){document.getElementById('pe_id').value=r.id;document.getElementById('pe_name').value=r.name;document.getElementById('pe_command').value=r.command;document.getElementById('pe_description').value=r.description;document.getElementById('pe_position').value=r.position;document.getElementById('editPlayerModal').style.display='flex'}function closePlayerEdit(){document.getElementById('editPlayerModal').style.display='none'}</script></body></html>
