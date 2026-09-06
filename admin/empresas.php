<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';

if(($_SESSION['staff_role'] ?? '') !== 'master'){
    http_response_code(403); exit('Acesso restrito ao Staff Master.');
}

$pdo->exec("CREATE TABLE IF NOT EXISTS site_companies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    photo VARCHAR(500) NULL,
    category VARCHAR(120) NOT NULL,
    name VARCHAR(150) NOT NULL,
    description VARCHAR(700) NULL,
    link_url VARCHAR(1000) NULL,
    link_text VARCHAR(160) NULL,
    available TINYINT(1) NOT NULL DEFAULT 1,
    position INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_site_companies_active_position(active, position, name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Compatibilidade: adiciona o responsável caso a tabela já exista sem a coluna.
$colCheck=$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='site_companies' AND COLUMN_NAME='responsible'")->fetchColumn();
if(!(int)$colCheck){ $pdo->exec("ALTER TABLE site_companies ADD COLUMN responsible VARCHAR(180) NULL AFTER description"); }
$colLinkUrl=$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='site_companies' AND COLUMN_NAME='link_url'")->fetchColumn();
if(!(int)$colLinkUrl){ $pdo->exec("ALTER TABLE site_companies ADD COLUMN link_url VARCHAR(1000) NULL AFTER responsible"); }
$colLinkText=$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='site_companies' AND COLUMN_NAME='link_text'")->fetchColumn();
if(!(int)$colLinkText){ $pdo->exec("ALTER TABLE site_companies ADD COLUMN link_text VARCHAR(160) NULL AFTER link_url"); }

$msg=''; $error=''; $editing=null;
$uploadDir=dirname(__DIR__).'/assets/images/empresas';
$uploadUrl='../assets/images/empresas';
if(!is_dir($uploadDir)) @mkdir($uploadDir,0755,true);

function uploadCompanyPhoto($field,$uploadDir){
    if(empty($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE) return null;
    if($_FILES[$field]['error']!==UPLOAD_ERR_OK) throw new RuntimeException('Não foi possível enviar a foto.');
    if((int)$_FILES[$field]['size']>4*1024*1024) throw new RuntimeException('A foto deve ter no máximo 4 MB.');

    $tmp=$_FILES[$field]['tmp_name'];
    $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $allowed=['image/jpeg','image/png','image/webp','image/gif'];
    if(!in_array($mime,$allowed,true)) throw new RuntimeException('Formato de foto inválido. Use JPG, PNG, WEBP ou GIF.');
    if(!function_exists('imagewebp')) throw new RuntimeException('O servidor não possui suporte a WebP (GD).');

    switch($mime){
        case 'image/jpeg': $image=@imagecreatefromjpeg($tmp); break;
        case 'image/png':  $image=@imagecreatefrompng($tmp); break;
        case 'image/webp': $image=@imagecreatefromwebp($tmp); break;
        case 'image/gif':  $image=@imagecreatefromgif($tmp); break;
        default: $image=false;
    }
    if(!$image) throw new RuntimeException('Não foi possível processar a foto enviada.');

    // Preserva transparência para PNG/WebP e deixa o fundo transparente.
    imagealphablending($image,false);
    imagesavealpha($image,true);

    $filename='empresa_'.bin2hex(random_bytes(8)).'.webp';
    $destination=$uploadDir.'/'.$filename;
    if(!@imagewebp($image,$destination,88)){
        imagedestroy($image);
        throw new RuntimeException('Não foi possível converter a foto para WebP.');
    }
    imagedestroy($image);

    return $filename;
}

function parseCompanyTxt($content){
    $content = preg_replace('/^\\xEF\\xBB\\xBF/', '', $content);
    $lines = preg_split('/\\r\\n|\\n|\\r/', $content);
    $rows = [];
    foreach($lines as $line){
        $line = trim($line);
        if($line === '' || str_starts_with($line, '#')) continue;
        // Aceita o padrão principal "Categoria / Nome / Descrição"
        // e também TAB como separador para facilitar edição em planilhas.
        if(strpos($line, "\\t") !== false){
            $parts = array_map('trim', explode("\\t", $line, 3));
        }else{
            $parts = array_map('trim', explode('/', $line, 3));
        }
        if(count($parts) < 3 || $parts[0] === '' || $parts[1] === ''){
            throw new RuntimeException('Linha inválida no TXT: '.mb_substr($line,0,180));
        }
        $rows[] = ['category'=>$parts[0], 'name'=>$parts[1], 'description'=>$parts[2]];
    }
    return $rows;
}

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=$_POST['action']??'';
    try{
        if($action==='import_txt'){
            if(empty($_FILES['companies_txt']) || ($_FILES['companies_txt']['error'] ?? UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK){
                throw new RuntimeException('Selecione um arquivo TXT válido.');
            }
            if((int)$_FILES['companies_txt']['size'] > 2*1024*1024){
                throw new RuntimeException('O TXT deve ter no máximo 2 MB.');
            }
            $content=@file_get_contents($_FILES['companies_txt']['tmp_name']);
            if($content===false) throw new RuntimeException('Não foi possível ler o TXT.');
            $rows=parseCompanyTxt($content);
            if(!$rows) throw new RuntimeException('O TXT não possui empresas para importar.');

            $find=$pdo->prepare('SELECT id FROM site_companies WHERE category=? AND name=? LIMIT 1');
            $update=$pdo->prepare('UPDATE site_companies SET description=? WHERE id=?');
            $insert=$pdo->prepare('INSERT INTO site_companies(category,name,description,available,position,active) VALUES(?,?,?,1,?,1)');
            $maxPos=(int)$pdo->query('SELECT COALESCE(MAX(position),-1) FROM site_companies')->fetchColumn();
            $created=0; $updated=0;
            $pdo->beginTransaction();
            try{
                foreach($rows as $r){
                    $find->execute([$r['category'],$r['name']]);
                    $existing=$find->fetchColumn();
                    if($existing){
                        $update->execute([$r['description']!==''?$r['description']:null,(int)$existing]);
                        $updated++;
                    }else{
                        $maxPos++;
                        $insert->execute([$r['category'],$r['name'],$r['description']!==''?$r['description']:null,$maxPos]);
                        $created++;
                    }
                }
                $pdo->commit();
            }catch(Throwable $e){
                if($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
            auditLog($pdo,'Importação em massa de empresas','Empresa',0,'Importação TXT','Criadas: '.$created.'; atualizadas: '.$updated.'.');
            $msg='Importação concluída: '.$created.' cadastrada(s) e '.$updated.' atualizada(s).';
        }elseif($action==='save'){
            $id=(int)($_POST['id']??0);
            $category=trim($_POST['category']??'');
            $name=trim($_POST['name']??'');
            $description=trim($_POST['description']??'');
            $responsible=trim($_POST['responsible']??'');
            $linkUrl=trim($_POST['link_url']??'');
            $linkText=trim($_POST['link_text']??'');
            if($linkUrl!==''){
                if(!filter_var($linkUrl,FILTER_VALIDATE_URL) || !preg_match('/^https?:\/\//i',$linkUrl)){
                    throw new RuntimeException('O link deve ser uma URL válida começando com http:// ou https://.');
                }
            }
            if($linkUrl==='') $linkText='';
            if($linkUrl!=='' && $linkText==='') $linkText='Acessar link';
            $available=isset($_POST['available'])?1:0;
            if($available) $responsible='';
            $position=(int)($_POST['position']??0);
            if($category===''||$name==='') throw new RuntimeException('Categoria e nome são obrigatórios.');
            $newPhoto=uploadCompanyPhoto('photo',$uploadDir);
            if($id>0){
                $oldQ=$pdo->prepare('SELECT * FROM site_companies WHERE id=? LIMIT 1'); $oldQ->execute([$id]); $old=$oldQ->fetch();
                if(!$old) throw new RuntimeException('Empresa não encontrada.');
                $photo=$newPhoto ?: ($old['photo']??null);
                $st=$pdo->prepare('UPDATE site_companies SET photo=?,category=?,name=?,description=?,responsible=?,link_url=?,link_text=?,available=?,position=? WHERE id=?');
                $st->execute([$photo,$category,$name,$description?:null,$responsible?:null,$linkUrl?:null,$linkText?:null,$available,$position,$id]);
                if($newPhoto && !empty($old['photo']) && is_file($uploadDir.'/'.$old['photo'])) @unlink($uploadDir.'/'.$old['photo']);
                auditLog($pdo,'Empresa pública alterada','Empresa',$id,$name,'Empresa do Portal alterada.');
                $msg='Empresa atualizada com sucesso.';
            }else{
                $st=$pdo->prepare('INSERT INTO site_companies(photo,category,name,description,responsible,link_url,link_text,available,position) VALUES(?,?,?,?,?,?,?,?,?)');
                $st->execute([$newPhoto,$category,$name,$description?:null,$responsible?:null,$linkUrl?:null,$linkText?:null,$available,$position]);
                $newId=(int)$pdo->lastInsertId();
                auditLog($pdo,'Empresa pública criada','Empresa',$newId,$name,'Empresa adicionada ao Portal.');
                $msg='Empresa cadastrada com sucesso.';
            }
        }elseif($action==='delete'){
            $id=(int)($_POST['id']??0);
            $q=$pdo->prepare('SELECT name,photo FROM site_companies WHERE id=? LIMIT 1');$q->execute([$id]);$row=$q->fetch();
            if($row){
                $pdo->prepare('DELETE FROM site_companies WHERE id=?')->execute([$id]);
                if(!empty($row['photo']) && is_file($uploadDir.'/'.$row['photo'])) @unlink($uploadDir.'/'.$row['photo']);
                auditLog($pdo,'Empresa pública excluída','Empresa',$id,$row['name'],'Empresa removida do Portal.');
            }
            $msg='Empresa excluída.';
        }elseif($action==='toggle'){
            $id=(int)($_POST['id']??0);
            $st=$pdo->prepare('UPDATE site_companies SET available=IF(available=1,0,1) WHERE id=?');$st->execute([$id]);
            $msg='Status da empresa atualizado.';
        }
    }catch(Throwable $e){$error=$e->getMessage();}
}

if(isset($_GET['edit'])){
    $q=$pdo->prepare('SELECT * FROM site_companies WHERE id=? LIMIT 1');$q->execute([(int)$_GET['edit']]);$editing=$q->fetch() ?: null;
}
if(isset($_GET['download_template'])){
    $sample = "Categoria / Nome / Descrição\nSaloon / Saloon Valentine / Local de bebidas e entretenimento\nFerraria / Ferraria Rhodes / Serviços de armas e reparos\nComércio / Armazém Central / Venda de produtos diversos\n";
    header('Content-Type: text/plain; charset=UTF-8');
    header('Content-Disposition: attachment; filename="modelo_empresas.txt"');
    header('Content-Length: '.strlen($sample));
    echo $sample;
    exit;
}

$companies=$pdo->query('SELECT * FROM site_companies ORDER BY position ASC, name ASC')->fetchAll();
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Empresas • Master</title><link rel="stylesheet" href="../assets/css/style.css"><style>
body{background:#0b0e14}.company-wrap{max-width:1180px;margin:0 auto}.company-form{background:#111722;border:1px solid #29344a;border-radius:14px;padding:22px;margin-bottom:18px}.company-form h2{margin:0 0 5px;color:#fff}.company-form .sub{color:#8795ad;font-size:12px;margin:0 0 18px}.fields{display:grid;grid-template-columns:1fr 1fr;gap:12px}.field{display:grid;gap:6px}.field.full{grid-column:1/-1}.field label{font-size:11px;color:#9aa8bf;font-weight:700}.field input,.field textarea,.field select{width:100%;background:#0b111b;color:#fff;border:1px solid #33405a;border-radius:8px;padding:11px 12px;outline:none;font:inherit;font-size:12px}.field textarea{min-height:100px;resize:vertical}.field input:focus,.field textarea:focus,.field select:focus{border-color:#d4af37}.photo-preview{display:flex;align-items:center;gap:14px;margin-top:5px}.photo-preview img{width:72px;height:72px;border-radius:10px;object-fit:cover;border:1px solid #5b4b1d;background:#0b0e14}.check{display:flex;align-items:center;gap:8px;color:#dce2ec;font-size:12px}.check input{width:auto}.actions{display:flex;gap:9px;margin-top:16px}.head-actions{display:flex;gap:8px;align-items:center}@media(max-width:700px){.head-actions{flex-wrap:wrap}}.btnx{border:1px solid #34415a;background:#192235;color:#fff;border-radius:8px;padding:10px 14px;font-weight:700;font-size:12px;cursor:pointer}.btnx.primary{background:#8f2525;border-color:#a33131}.btnx.gold{background:#3b3113;border-color:#8f7620;color:#f1d76c}.btnx.danger{background:#38171b;border-color:#6e2830;color:#ff9a9a}.table-wrap{background:#111722;border:1px solid #29344a;border-radius:14px;overflow:auto}.company-table{width:100%;border-collapse:collapse;min-width:850px}.company-table th,.company-table td{padding:12px 14px;border-bottom:1px solid #202a3a;text-align:left;font-size:11px}.company-table th{color:#8d9bb2;font-size:10px;text-transform:uppercase;letter-spacing:.7px}.company-table td{color:#dce2ec}.company-thumb{width:54px;height:54px;border-radius:9px;object-fit:cover;background:#0b0e14;border:1px solid #29344a}.status-pill{display:inline-flex;padding:5px 9px;border-radius:999px;font-weight:800;font-size:9px;text-transform:uppercase}.status-pill.available{background:#18351f;border:1px solid #2d7740;color:#69dc86}.status-pill.unavailable{background:#42191d;border:1px solid #8c3039;color:#ff717a}.muted{color:#748198}.row-actions{display:flex;gap:6px;flex-wrap:wrap}.notice{padding:11px 14px;border-radius:9px;margin-bottom:14px;font-size:12px}.notice.ok{background:#18351f;border:1px solid #2d7740;color:#7de095}.notice.err{background:#42191d;border:1px solid #8c3039;color:#ff9ba2}@media(max-width:700px){.fields{grid-template-columns:1fr}.field.full{grid-column:auto}}
</style></head><body><header class="topbar"><div><b>CARMESIM</b> <span>CREATORS</span></div><div class="top-user"><?php if(!empty($_SESSION['staff_discord_avatar'])): ?><img class="discord-avatar-top" src="<?=htmlspecialchars($_SESSION['staff_discord_avatar'])?>" alt="Avatar Discord"><?php endif; ?><?=htmlspecialchars($_SESSION['staff_name'])?> · <a href="../logout.php">Sair</a></div></header>
<main class="container company-wrap"><div class="page-head"><div><p class="eyebrow">ÁREA MASTER</p><h1>Empresas do Portal</h1></div><div class="head-actions"><a class="btnx gold" href="relatorio_empresas_pdf.php" target="_blank">📄 Baixar relatório PDF</a><a class="btnx" href="index.php">← Dashboard</a></div></div>
<?php if($msg): ?><div class="notice ok"><?=htmlspecialchars($msg)?></div><?php endif; ?><?php if($error): ?><div class="notice err"><?=htmlspecialchars($error)?></div><?php endif; ?>
<section class="company-form"><h2>📥 Cadastro em massa por TXT</h2><p class="sub">Envie várias empresas de uma vez usando o padrão <strong>Categoria / Nome / Descrição</strong>. Uma empresa por linha.</p>
<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:12px"><a class="btnx gold" href="?download_template=1">📄 Baixar modelo TXT</a></div>
<form method="post" enctype="multipart/form-data" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center"><input type="hidden" name="action" value="import_txt"><input type="file" name="companies_txt" accept=".txt,text/plain" required><button class="btnx primary" type="submit">📥 Importar empresas</button></form>
<small class="muted" style="display:block;margin-top:10px">Se Categoria + Nome já existirem, o sistema atualiza a descrição em vez de duplicar. As novas empresas entram como <strong>Empresa Disponível</strong>. Não são exigidas fotos, links ou responsáveis no TXT.</small></section>

<section class="company-form"><h2><?= $editing ? 'Editar empresa' : 'Cadastrar empresa' ?></h2><p class="sub">Cadastre as empresas que aparecem na aba Empresas do Portal.</p>
<form method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($editing['id']??0) ?>">
<div class="fields"><div class="field"><label>Categoria</label><input name="category" value="<?=htmlspecialchars($editing['category']??'')?>" placeholder="Ex.: Saloon, Ferraria, Comércio" required></div><div class="field"><label>Nome</label><input name="name" value="<?=htmlspecialchars($editing['name']??'')?>" placeholder="Nome da empresa" required></div><div class="field full"><label>Descrição</label><textarea name="description" placeholder="Descrição da empresa"><?=htmlspecialchars($editing['description']??'')?></textarea></div><div class="field"><label>Link da empresa <span class="muted">(opcional)</span></label><input type="url" name="link_url" value="<?=htmlspecialchars($editing['link_url']??'')?>" placeholder="https://exemplo.com"><small class="muted">O link será aberto em uma nova aba.</small></div><div class="field"><label>Texto do link</label><input name="link_text" value="<?=htmlspecialchars($editing['link_text']??'')?>" placeholder="Ex.: Site oficial / Donate / Discord"><small class="muted">Se deixar vazio e houver URL, será usado “Acessar link”.</small></div><div class="field full" id="responsibleField"><label>Responsável <span class="muted">(somente quando estiver Não Disponível)</span></label><input name="responsible" id="responsibleInput" value="<?=htmlspecialchars($editing['responsible']??'')?>" placeholder="Nome do responsável pela empresa"><small class="muted">Somente para gerenciamento administrativo. Não aparece na aba Empresas.</small></div><div class="field"><label>Foto</label><input type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif"><small class="muted">A imagem enviada será convertida automaticamente para WebP.</small><?php if(!empty($editing['photo'])): ?><div class="photo-preview"><img src="../assets/images/empresas/<?=htmlspecialchars($editing['photo'])?>" alt=""><span class="muted">Foto atual. Envie outra para substituir.</span></div><?php endif; ?></div><div class="field"><label>Posição</label><input type="number" name="position" value="<?= (int)($editing['position']??0) ?>" min="0"><small class="muted">Menor número aparece primeiro.</small></div><div class="field full"><label class="check"><input type="checkbox" id="availableInput" name="available" value="1" <?=!isset($editing['available'])||$editing['available']?'checked':''?>> Empresa Disponível</label></div></div>
<div class="actions"><button class="btnx primary" type="submit"><?= $editing ? 'Salvar alterações' : 'Cadastrar empresa' ?></button><?php if($editing): ?><a class="btnx" href="empresas.php">Cancelar</a><?php endif; ?></div></form></section>
<section class="table-wrap"><table class="company-table"><thead><tr><th>Foto</th><th>Categoria</th><th>Nome</th><th>Descrição</th><th>Responsável</th><th>Status</th><th>Posição</th><th>Ações</th></tr></thead><tbody><?php if(!$companies): ?><tr><td colspan="8" class="muted">Nenhuma empresa cadastrada.</td></tr><?php else: foreach($companies as $c): ?><tr><td><?php if(!empty($c['photo'])):?><img class="company-thumb" src="../assets/images/empresas/<?=htmlspecialchars($c['photo'])?>" alt=""><?php else:?><div class="company-thumb"></div><?php endif;?></td><td><?=htmlspecialchars($c['category'])?></td><td><strong><?=htmlspecialchars($c['name'])?></strong></td><td><?=htmlspecialchars($c['description']?:'—')?></td><td><?=((int)$c['available']===0 && !empty($c['responsible']))?htmlspecialchars($c['responsible']):'<span class="muted">—</span>'?></td><td><span class="status-pill <?=$c['available']?'available':'unavailable'?>"><?=$c['available']?'Empresa Disponível':'Não Disponível'?></span></td><td><?= (int)$c['position'] ?></td><td><div class="row-actions"><a class="btnx gold" href="empresas.php?edit=<?=(int)$c['id']?>">Editar</a><form method="post" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=(int)$c['id']?>"><button class="btnx" type="submit">Alternar status</button></form><form method="post" style="display:inline" onsubmit="return confirm('Excluir esta empresa?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=(int)$c['id']?>"><button class="btnx danger" type="submit">Excluir</button></form></div></td></tr><?php endforeach; endif; ?></tbody></table></section>
<script>
(function(){
 const a=document.getElementById('availableInput'), f=document.getElementById('responsibleField'), i=document.getElementById('responsibleInput');
 function sync(){const on=!!(a&&a.checked); if(f) f.style.display=on?'none':'grid'; if(on&&i) i.value='';}
 if(a){a.addEventListener('change',sync);sync();}
})();
</script></main></body></html>
