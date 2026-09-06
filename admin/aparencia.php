<?php
require_once '../config/auth.php';
require_once '../config/database.php';

function getSettingLocal(PDO $pdo, string $key, string $default=''): string {
    $st=$pdo->prepare('SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1');
    $st->execute([$key]);
    $v=$st->fetchColumn();
    return $v===false ? $default : (string)$v;
}
function saveSettingLocal(PDO $pdo,string $key,string $value,string $description=''): void {
    $st=$pdo->prepare('INSERT INTO settings(setting_key,setting_value,description) VALUES(?,?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value), description=VALUES(description)');
    $st->execute([$key,$value,$description]);
}

$uploadDir=__DIR__.'/../assets/uploads/streamer';
$uploadWeb='assets/uploads/streamer/';
if(!is_dir($uploadDir)) @mkdir($uploadDir,0755,true);

$logo=getSettingLocal($pdo,'streamer_logo','');
$background=getSettingLocal($pdo,'streamer_background','assets/img/streamer-background-default.png');
$msg=''; $error='';

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    if($action==='remove_logo'){
        if($logo && strpos($logo,'assets/uploads/streamer/')===0){ @unlink(__DIR__.'/../'.$logo); }
        saveSettingLocal($pdo,'streamer_logo','','Logo da Área do Streamer'); $logo=''; $msg='Logo removida.';
    } elseif($action==='remove_background'){
        if($background && strpos($background,'assets/uploads/streamer/')===0){ @unlink(__DIR__.'/../'.$background); }
        $background='assets/img/streamer-background-default.png';
        saveSettingLocal($pdo,'streamer_background',$background,'Background da Área do Streamer'); $msg='Background restaurado para o padrão.';
    } elseif($action==='upload_logo' || $action==='upload_background'){
        $field=$action==='upload_logo'?'logo':'background';
        if(empty($_FILES[$field]) || $_FILES[$field]['error']!==UPLOAD_ERR_OK){ $error='Selecione uma imagem válida.'; }
        else {
            $f=$_FILES[$field];
            if($f['size']>8*1024*1024){ $error='A imagem deve ter no máximo 8 MB.'; }
            else {
                $fi=new finfo(FILEINFO_MIME_TYPE); $mime=$fi->file($f['tmp_name']);
                $allowed=['image/png'=>'png','image/jpeg'=>'jpg','image/webp'=>'webp'];
                if(!isset($allowed[$mime])) $error='Formato inválido. Use PNG, JPG ou WEBP.';
                else {
                    $name=$field.'_'.date('YmdHis').'_'.bin2hex(random_bytes(4)).'.'.$allowed[$mime];
                    if(move_uploaded_file($f['tmp_name'],$uploadDir.'/'.$name)){
                        $rel=$uploadWeb.$name;
                        if($field==='logo'){
                            if($logo && strpos($logo,'assets/uploads/streamer/')===0) @unlink(__DIR__.'/../'.$logo);
                            saveSettingLocal($pdo,'streamer_logo',$rel,'Logo da Área do Streamer'); $logo=$rel;
                        } else {
                            if($background && strpos($background,'assets/uploads/streamer/')===0) @unlink(__DIR__.'/../'.$background);
                            saveSettingLocal($pdo,'streamer_background',$rel,'Background da Área do Streamer'); $background=$rel;
                        }
                        $msg=($field==='logo'?'Logo':'Background').' atualizado com sucesso.';
                    } else $error='Não foi possível salvar a imagem.';
                }
            }
        }
    }
}
$logoUrl=$logo ? '../'.$logo : '';
$backgroundUrl='../'.$background;
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Aparência do Streamer</title><link rel="stylesheet" href="../assets/css/style.css"></head>
<body>
<header class="topbar"><div><b>MORNINGFALL</b> <span>CREATORS</span></div><div><?=htmlspecialchars($_SESSION['staff_name'])?> · <a href="../logout.php">Sair</a></div></header>
<main class="container appearance-page">
<div class="page-head"><div><a href="index.php">← Dashboard</a><p class="eyebrow">CONFIGURAÇÕES</p><h1>Aparência da Área do Streamer</h1><p class="muted">Configure a logo e o background exibidos na área dos streamers.</p></div><a class="btn primary" href="configuracoes.php">⚙ Configurações</a></div>
<?php if($msg): ?><div class="alert success"><?=htmlspecialchars($msg)?></div><?php endif; ?>
<?php if($error): ?><div class="alert danger"><?=htmlspecialchars($error)?></div><?php endif; ?>
<div class="appearance-grid">
<section class="panel appearance-card"><div class="appearance-title"><div><h2>Logo da Área do Streamer</h2><p class="muted">A logo será exibida no topo da tela de acesso e no painel do streamer.</p></div><span class="setup-badge">SETUP</span></div>
<div class="media-preview logo-preview"><?php if($logo): ?><img src="<?=htmlspecialchars($logoUrl)?>" alt="Logo do streamer"><?php else: ?><div class="fallback-logo"><b>CARMESIM</b><span>CREATORS</span></div><?php endif; ?></div>
<form method="post" enctype="multipart/form-data" class="upload-form"><input type="hidden" name="action" value="upload_logo"><input type="file" name="logo" accept="image/png,image/jpeg,image/webp" required><button class="btn primary">⬆ Alterar logo</button></form>
<?php if($logo): ?><form method="post" class="remove-form"><input type="hidden" name="action" value="remove_logo"><button class="btn secondary">Remover</button></form><?php endif; ?><small class="muted">PNG, JPG ou WEBP · recomendado até 400×200px.</small></section>
<section class="panel appearance-card"><div class="appearance-title"><div><h2>Background da Área do Streamer</h2><p class="muted">A imagem será exibida atrás do painel de acesso do streamer.</p></div><span class="setup-badge">SETUP</span></div>
<div class="media-preview background-preview" style="background-image:url('<?=htmlspecialchars($backgroundUrl)?>')"></div>
<form method="post" enctype="multipart/form-data" class="upload-form"><input type="hidden" name="action" value="upload_background"><input type="file" name="background" accept="image/png,image/jpeg,image/webp" required><button class="btn primary">⬆ Alterar background</button></form>
<form method="post" class="remove-form"><input type="hidden" name="action" value="remove_background"><button class="btn secondary">Restaurar padrão</button></form><small class="muted">JPG, PNG ou WEBP · recomendado 1920×1080px.</small></section>
</div>
<section class="panel appearance-preview"><div class="section-title"><div><p class="eyebrow">PRÉ-VISUALIZAÇÃO</p><h2>Como ficará para o streamer</h2></div></div><div class="streamer-preview" style="background-image:linear-gradient(rgba(7,8,15,.35),rgba(7,8,15,.65)),url('<?=htmlspecialchars($backgroundUrl)?>')"><div class="preview-card"><div class="preview-logo"><?php if($logo): ?><img src="<?=htmlspecialchars($logoUrl)?>" alt="Logo"><?php else: ?><div class="fallback-logo"><b>CARMESIM</b><span>CREATORS</span></div><?php endif; ?></div><h2>Área do <span>Streamer</span></h2><p>Informe seu código de acesso para consultar seus dados.</p><div class="preview-input">🔒 &nbsp; Código de acesso</div><div class="preview-button">Consultar</div></div></div></section>
</main>
<footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
</body></html>