<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';

if (($_SESSION['staff_role'] ?? '') !== 'master') {
    http_response_code(403);
    exit('Acesso restrito ao Staff Master.');
}

$pdo->exec("CREATE TABLE IF NOT EXISTS pricing_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL UNIQUE,
    position INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pricing_categories_position(active,position,name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$pdo->exec("CREATE TABLE IF NOT EXISTS pricing_products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    photo VARCHAR(500) NULL,
    item_name VARCHAR(180) NOT NULL,
    price_min DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    price_max DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    position INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_pricing_products_category(category_id,active,position,item_name),
    FOREIGN KEY(category_id) REFERENCES pricing_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$categoryId=(int)($_GET['id'] ?? $_POST['category_id'] ?? 0);
if($categoryId<=0){ header('Location: precificacao.php'); exit; }

$st=$pdo->prepare('SELECT * FROM pricing_categories WHERE id=? AND active=1');
$st->execute([$categoryId]);
$category=$st->fetch();
if(!$category){ http_response_code(404); exit('Categoria não encontrada.'); }

$uploadDir=dirname(__DIR__).'/assets/images/precificacao';
if(!is_dir($uploadDir)) @mkdir($uploadDir,0755,true);

function priceVal($v){
    $v=trim((string)$v);
    $v=str_replace(['R$','r$',' '],'',$v);
    if(str_contains($v,',')){ $v=str_replace('.','',$v); $v=str_replace(',','.',$v); }
    return is_numeric($v)?(float)$v:null;
}
function imageStem(string $name):string{
    $name=trim($name);
    $name=str_replace([' ','\t','\r','\n'],'_',$name);
    $name=@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$name) ?: $name;
    $name=preg_replace('/[^A-Za-z0-9_-]+/','_',$name);
    $name=preg_replace('/_+/','_',$name);
    return trim($name,'_');
}


function resizeTo500Square($im) {
    $srcW = imagesx($im);
    $srcH = imagesy($im);
    if ($srcW <= 0 || $srcH <= 0) return $im;

    $dst = imagecreatetruecolor(500, 500);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, 499, 499, $transparent);

    // Mantém a proporção e corta apenas o excesso para chegar exatamente a 500x500.
    $scale = max(500 / $srcW, 500 / $srcH);
    $newW = max(1, (int)round($srcW * $scale));
    $newH = max(1, (int)round($srcH * $scale));
    $tmp = imagecreatetruecolor($newW, $newH);
    imagealphablending($tmp, false);
    imagesavealpha($tmp, true);
    imagefilledrectangle($tmp, 0, 0, $newW - 1, $newH - 1, $transparent);
    imagecopyresampled($tmp, $im, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);

    $srcX = max(0, (int)floor(($newW - 500) / 2));
    $srcY = max(0, (int)floor(($newH - 500) / 2));
    imagecopy($dst, $tmp, 0, 0, $srcX, $srcY, 500, 500);
    imagedestroy($tmp);
    imagedestroy($im);
    return $dst;
}

function saveWebpUpload(string $field,string $dir,string $itemName):?string{
    if(empty($_FILES[$field]) || $_FILES[$field]['error']===UPLOAD_ERR_NO_FILE) return null;
    if($_FILES[$field]['error']!==UPLOAD_ERR_OK) throw new RuntimeException('Não foi possível enviar a imagem.');
    if($_FILES[$field]['size']>8*1024*1024) throw new RuntimeException('A imagem deve ter no máximo 8 MB.');
    $info=@getimagesize($_FILES[$field]['tmp_name']);
    if(!$info) throw new RuntimeException('O arquivo enviado não é uma imagem válida.');
    $mime=$info['mime']??'';
    if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)) throw new RuntimeException('Use JPG, PNG ou WebP.');
    if(!function_exists('imagewebp')) throw new RuntimeException('O servidor não possui suporte à conversão WebP.');
    if($mime==='image/jpeg') $im=@imagecreatefromjpeg($_FILES[$field]['tmp_name']);
    elseif($mime==='image/png') $im=@imagecreatefrompng($_FILES[$field]['tmp_name']);
    else $im=@imagecreatefromwebp($_FILES[$field]['tmp_name']);
    if(!$im) throw new RuntimeException('Não foi possível processar a imagem.');
    if(function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($im);
    imagealphablending($im,false); imagesavealpha($im,true);
    $im=resizeTo500Square($im);
    $stem=imageStem($itemName);
    if($stem==='') $stem='item';

    // A biblioteca de imagens é permanente: nunca sobrescrevemos uma foto existente.
    // O nome principal segue o nome do item; em caso de colisão, criamos uma nova versão.
    $name=$stem.'.webp';
    $i=2;
    while(is_file($dir.'/'.$name)){
        $name=$stem.'_'.$i.'.webp';
        $i++;
    }

    $ok=@imagewebp($im,$dir.'/'.$name,88); imagedestroy($im);
    if(!$ok) throw new RuntimeException('Não foi possível converter a imagem para WebP.');
    return $name;
}

$msg=''; $error=''; $editingProduct=null;

if($_SERVER['REQUEST_METHOD']==='POST'){
    try{
        $action=$_POST['action']??'';
        if($action==='product_save'){
            $id=(int)($_POST['id']??0);
            $name=trim($_POST['item_name']??'');
            $photo=trim($_POST['photo']??'');
            $min=priceVal($_POST['price_min']??'');
            $max=priceVal($_POST['price_max']??'');
            $pos=(int)($_POST['position']??0);
            if($name===''||$min===null||$max===null) throw new RuntimeException('Nome, preço mínimo e preço máximo são obrigatórios.');
            if($min<0||$max<0||$min>$max) throw new RuntimeException('Os preços devem ser válidos e o mínimo não pode ser maior que o máximo.');
            $oldPhoto=null;
            if($id){
                $q=$pdo->prepare('SELECT photo,item_name FROM pricing_products WHERE id=? AND category_id=?');
                $q->execute([$id,$categoryId]); $old=$q->fetch();
                if(!$old) throw new RuntimeException('Produto não encontrado nesta categoria.');
                $oldPhoto=$old['photo'];
            }
            $newPhoto=saveWebpUpload('photo_file',$uploadDir,$name);
            if($newPhoto){ 
    /* A foto antiga é mantida na biblioteca. Apenas vinculamos a nova foto ao item. */
    $photo=$newPhoto; 
}
            $photo=$photo!==''?basename($photo):null;
            if($id){
                $q=$pdo->prepare('UPDATE pricing_products SET photo=?,item_name=?,price_min=?,price_max=?,position=? WHERE id=? AND category_id=?');
                $q->execute([$photo,$name,$min,$max,$pos,$id,$categoryId]);
                auditLog($pdo,'Produto de precificação alterado','Precificação',$id,$name,'Produto alterado.');
                $msg='Produto atualizado.';
            }else{
                $q=$pdo->prepare('INSERT INTO pricing_products(category_id,photo,item_name,price_min,price_max,position) VALUES(?,?,?,?,?,?)');
                $q->execute([$categoryId,$photo,$name,$min,$max,$pos]);
                $new=(int)$pdo->lastInsertId();
                auditLog($pdo,'Produto de precificação criado','Precificação',$new,$name,'Produto criado.');
                $msg='Produto cadastrado.';
            }
                }elseif($action==='product_delete_all'){
            $q=$pdo->prepare('SELECT id,item_name,photo FROM pricing_products WHERE category_id=?');
            $q->execute([$categoryId]);
            $all=$q->fetchAll();
            $pdo->beginTransaction();
            $del=$pdo->prepare('DELETE FROM pricing_products WHERE id=? AND category_id=?');
            foreach($all as $r){
                $del->execute([(int)$r['id'],$categoryId]);
            }
            $pdo->commit();
            auditLog($pdo,'Todos os produtos de uma categoria foram excluídos','Precificação',$categoryId,$category['name'],'Excluídos '.count($all).' produto(s).');
            $msg='Todos os '.count($all).' item(ns) da categoria foram excluídos.';
        }elseif($action==='product_delete'){
            $id=(int)($_POST['id']??0);
            $q=$pdo->prepare('SELECT item_name,photo FROM pricing_products WHERE id=? AND category_id=?');
            $q->execute([$id,$categoryId]); $r=$q->fetch();
            if($r){
                $pdo->prepare('DELETE FROM pricing_products WHERE id=? AND category_id=?')->execute([$id,$categoryId]);
                auditLog($pdo,'Produto de precificação excluído','Precificação',$id,$r['item_name'],'Produto excluído.');
            }
            $msg='Produto excluído.';
        }elseif($action==='import_txt'){
            if(empty($_FILES['import_package'])||$_FILES['import_package']['error']!==UPLOAD_ERR_OK) throw new RuntimeException('Envie um arquivo TXT ou ZIP contendo o TXT e, opcionalmente, as imagens.');
            if($_FILES['import_package']['size']>60*1024*1024) throw new RuntimeException('O arquivo deve ter no máximo 60 MB.');

            $uploadTmp=$_FILES['import_package']['tmp_name'];
            $uploadExt=strtolower(pathinfo($_FILES['import_package']['name'],PATHINFO_EXTENSION));
            $raw='';
            $packageImages=[];
            $tempFiles=[];

            /*
             * Cadastro rápido aceita:
             *  - TXT tradicional; ou
             *  - ZIP contendo o TXT + imagens.
             * As imagens recebidas são convertidas para WebP 500x500 e nunca
             * sobrescrevem nem apagam arquivos existentes da biblioteca.
             */
            if($uploadExt==='zip'){
                if(!class_exists('ZipArchive')) throw new RuntimeException('O servidor não possui suporte ao ZIP (ZipArchive).');
                if(!function_exists('imagewebp')) throw new RuntimeException('O servidor não possui suporte à conversão WebP.');
                $zip=new ZipArchive();
                if($zip->open($uploadTmp)!==true) throw new RuntimeException('Não foi possível abrir o ZIP enviado.');
                $txtFound=false;
                for($i=0;$i<$zip->numFiles;$i++){
                    $entry=$zip->statIndex($i);
                    if(!$entry||empty($entry['name'])) continue;
                    $entryName=str_replace('\\','/',$entry['name']);
                    if(str_ends_with($entryName,'/')) continue;
                    $base=basename($entryName);
                    if($base===''||$base[0]==='.') continue;
                    $ext=strtolower(pathinfo($base,PATHINFO_EXTENSION));
                    if(!$txtFound && $ext==='txt'){
                        $candidate=$zip->getFromIndex($i);
                        if($candidate!==false){$raw=$candidate;$txtFound=true;}
                        continue;
                    }
                    if(in_array($ext,['jpg','jpeg','png','webp'],true)){
                        $data=$zip->getFromIndex($i);
                        if($data===false) continue;
                        $tmpImg=tempnam(sys_get_temp_dir(),'carmesim_import_img_');
                        if($tmpImg===false) continue;
                        if(@file_put_contents($tmpImg,$data)===false){@unlink($tmpImg);continue;}
                        $info=@getimagesize($tmpImg); $mime=$info['mime']??'';
                        if(!in_array($mime,['image/jpeg','image/png','image/webp'],true)){@unlink($tmpImg);continue;}
                        $packageImages[$base]=$tmpImg;
                        $tempFiles[]=$tmpImg;
                    }
                }
                $zip->close();
                if(!$txtFound) throw new RuntimeException('O ZIP precisa conter um arquivo TXT no padrão da precificação.');
            }else{
                if($uploadExt!=='txt') throw new RuntimeException('Envie um arquivo TXT ou um ZIP com TXT + imagens.');
                if($_FILES['import_package']['size']>2*1024*1024) throw new RuntimeException('O TXT deve ter no máximo 2 MB.');
                $raw=file_get_contents($uploadTmp);
            }

            $raw=preg_replace('/^\xEF\xBB\xBF/','',$raw);
            $lines=preg_split('/\r\n|\r|\n/',$raw);
            $pdo->beginTransaction(); $count=0; $imagesAdded=0; $lineNo=0;
            foreach($lines as $line){
                $lineNo++; $line=trim($line);
                if($line===''||str_starts_with($line,'#')) continue;
                $parts=preg_split('/\s*\/\s*/',$line);
                if(count($parts)!==4) throw new RuntimeException("Linha {$lineNo} inválida. Use: NomeFoto / NomeItem / PrecoMin / PrecoMax");
                $photo=preg_replace('/[^A-Za-z0-9._-]/','',trim($parts[0]));
                $name=trim($parts[1]); $min=priceVal($parts[2]); $max=priceVal($parts[3]);
                if($name===''||$min===null||$max===null||$min<0||$max<0||$min>$max) throw new RuntimeException("Linha {$lineNo} possui dados inválidos.");

                /* Se o ZIP trouxe a foto indicada no TXT, cria uma nova cópia WebP 500x500. */
                if($photo!=='' && isset($packageImages[$photo])){
                    $source=$packageImages[$photo];
                    $info=@getimagesize($source); $mime=$info['mime']??'';
                    $im=false;
                    if($mime==='image/jpeg') $im=@imagecreatefromjpeg($source);
                    elseif($mime==='image/png') $im=@imagecreatefrompng($source);
                    elseif($mime==='image/webp') $im=@imagecreatefromwebp($source);
                    if($im){
                        if(function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($im);
                        imagealphablending($im,false); imagesavealpha($im,true);
                        $im=resizeTo500Square($im);
                        $stem=imageStem($name); if($stem==='') $stem='item';
                        $newPhoto=$stem.'.webp'; $n=2;
                        while(is_file($uploadDir.'/'.$newPhoto)){$newPhoto=$stem.'_'.$n.'.webp';$n++;}
                        if(@imagewebp($im,$uploadDir.'/'.$newPhoto,88)){$photo=$newPhoto;$imagesAdded++;}
                        imagedestroy($im);
                    }
                }

                $find=$pdo->prepare('SELECT id FROM pricing_products WHERE category_id=? AND item_name=? LIMIT 1');
                $find->execute([$categoryId,$name]);
                $existing=$find->fetchColumn();
                if($existing){
                    $up=$pdo->prepare('UPDATE pricing_products SET photo=?,price_min=?,price_max=?,active=1 WHERE id=? AND category_id=?');
                    $up->execute([$photo?:null,$min,$max,(int)$existing,$categoryId]);
                }else{
                    $up=$pdo->prepare('INSERT INTO pricing_products(category_id,photo,item_name,price_min,price_max,position) VALUES(?,?,?,?,?,?)');
                    $up->execute([$categoryId,$photo?:null,$name,$min,$max,$count]);
                }
                $count++;
            }
            $pdo->commit();
            foreach($tempFiles as $tf) @unlink($tf);
            auditLog($pdo,'Importação rápida de precificação','Precificação',$categoryId,$category['name'],'Importados '.$count.' produtos via '.strtoupper($uploadExt).'. Imagens novas: '.$imagesAdded.'.');
            $msg="Importação concluída: {$count} produto(s).".($imagesAdded?" {$imagesAdded} imagem(ns) convertida(s) para WebP 500x500.":'');
        }
    }catch(Throwable $e){ if($pdo->inTransaction()) $pdo->rollBack(); if(!empty($tempFiles)) foreach($tempFiles as $tf) @unlink($tf); $error=$e->getMessage(); }
}

if(isset($_GET['edit_product'])){
    $q=$pdo->prepare('SELECT * FROM pricing_products WHERE id=? AND category_id=?');
    $q->execute([(int)$_GET['edit_product'],$categoryId]);
    $editingProduct=$q->fetch()?:null;
}

$q=$pdo->prepare('SELECT * FROM pricing_products WHERE category_id=? AND active=1 ORDER BY position,item_name');
$q->execute([$categoryId]); $items=$q->fetchAll();
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=htmlspecialchars($category['name'])?> • Precificação</title><link rel="stylesheet" href="../assets/css/style.css"><style>
.price-admin{max-width:1180px;margin:auto}.box{background:#111722;border:1px solid #29344a;border-radius:14px;padding:20px;margin-bottom:16px}.page-head{display:flex;justify-content:space-between;gap:20px;align-items:flex-start}.sub{color:#8795ad;font-size:11px;margin:0 0 16px}.eyebrow{letter-spacing:4px;color:#9b74ff;font-size:12px}.head-actions,.actions-inline{display:flex;gap:7px;flex-wrap:wrap}.btnx{border:1px solid #34415a;background:#192235;color:#fff;border-radius:8px;padding:9px 13px;font-weight:700;font-size:11px;cursor:pointer;text-decoration:none;display:inline-block}.btnx.gold{background:#3b3113;border-color:#8f7620;color:#f1d76c}.btnx.primary{background:#8f2525;border-color:#a33131}.btnx.danger{background:#38171b;border-color:#6e2830;color:#ff9a9a}.btnx.edit{background:#182b3c;border-color:#315b78;color:#a9dcff}.notice{padding:10px 13px;border-radius:8px;margin-bottom:13px;font-size:11px}.ok{background:#18351f;border:1px solid #2d7740;color:#7de095}.err{background:#42191d;border:1px solid #8c3039;color:#ff9ba2}.item-row{display:grid;grid-template-columns:64px 1fr 125px 125px 70px 190px;gap:12px;align-items:center;padding:12px;border:1px solid #29344a;border-radius:10px;background:#0e151f;margin-top:8px}.thumb{width:52px;height:52px;object-fit:cover;border-radius:8px;border:1px solid #35435a;background:#0b1118}.item-name{font-size:12px;font-weight:800;color:#fff}.muted{font-size:9px;color:#748198}.price{font-size:10px;color:#d4af37;font-weight:700}.fields{display:grid;grid-template-columns:1fr 1fr;gap:11px}.field{display:grid;gap:6px}.full{grid-column:1/-1}.field label{font-size:10px;color:#9aa8bf;font-weight:700}.field input{width:100%;height:40px;background:#0b111b;color:#fff;border:1px solid #33405a;border-radius:8px;padding:0 11px;box-sizing:border-box}.format{background:#0b1118;border:1px dashed #39475e;border-radius:8px;padding:11px;color:#aeb9ca;font-family:monospace;font-size:11px;line-height:1.7}.tabs{display:flex;gap:7px;margin-bottom:12px;flex-wrap:wrap}.tab{padding:8px 11px;border:1px solid #34415a;border-radius:8px;color:#aab5c7;background:#121a27;cursor:pointer;font-size:10px;font-weight:700}.tab.active{border-color:#8f7620;background:#2b250f;color:#f1d76c}.tab-pane{display:none}.tab-pane.active{display:block}.photo-help{font-size:9px;color:#748198}@media(max-width:900px){.item-row{grid-template-columns:58px 1fr 110px}.item-row .hide-md{display:none}.item-row .actions-inline{grid-column:2/-1}}@media(max-width:700px){.page-head{flex-direction:column}.fields{grid-template-columns:1fr}.full{grid-column:auto}.item-row{grid-template-columns:52px 1fr}.item-row .price-col{display:none}.item-row .actions-inline{grid-column:2}}
</style></head><body><header class="topbar"><div><b>CARMESIM</b> <span>CREATORS</span></div><div class="top-user"><?=htmlspecialchars($_SESSION['staff_name']??'')?> · <a href="../logout.php">Sair</a></div></header>
<main class="container price-admin"><div class="page-head"><div><p class="eyebrow">ÁREA MASTER</p><h1><?=htmlspecialchars($category['name'])?></h1><p class="sub">Gerenciamento exclusivo desta categoria · <?=count($items)?> item(ns).</p></div><div class="head-actions"><a class="btnx" href="precificacao.php">← Categorias</a><a class="btnx gold" href="precificacao_imagens.php">🖼️ Enviar imagens ZIP</a><a class="btnx gold" href="../pages/precificacao.html" target="_blank">👁 Ver página pública</a></div></div>
<?php if($msg):?><div class="notice ok"><?=htmlspecialchars($msg)?></div><?php endif;?><?php if($error):?><div class="notice err"><?=htmlspecialchars($error)?></div><?php endif;?>
<div class="tabs"><button class="tab active" data-tab="items">📦 Itens (<?=count($items)?>)</button><button class="tab" data-tab="quick">⚡ Cadastro rápido TXT</button><button class="tab" data-tab="manual"><?=($editingProduct?'✏️ Editar item':'➕ Cadastrar item')?></button></div>
<section class="box tab-pane active" id="items"><div style="display:flex;justify-content:space-between;gap:10px;align-items:center;flex-wrap:wrap"><div><h2>Itens da categoria</h2><p class="sub">Somente os produtos de <strong><?=htmlspecialchars($category['name'])?></strong> aparecem aqui.</p></div><div class="actions-inline">
<button type="button" class="btnx gold" id="autoFindAll">🔎 Auto buscar imagens</button>
<a class="btnx" href="precificacao_export_txt.php?id=<?=$categoryId?>" title="Baixar todos os itens desta categoria no formato de importação">📦 Baixar TXT + Imagens</a>
<form method="post" style="display:inline" onsubmit="return confirm('ATENÇÃO! Isso excluirá TODOS os itens desta categoria. Esta ação não pode ser desfeita. Continuar?')">
<input type="hidden" name="action" value="product_delete_all">
<input type="hidden" name="category_id" value="<?=$categoryId?>">
<button type="submit" class="btnx danger">🗑️ Excluir todos os itens</button>
</form>
</div></div><div class="notice" style="background:#101a25;border:1px solid #293b54;color:#91a6c2">Procura <strong>Nome_Do_Item.webp</strong>. Se não encontrar, usa <strong>default.webp</strong> ou <strong>defull.webp</strong>.</div>
<?php if(!$items):?><div class="muted" style="padding:16px 0">Nenhum item cadastrado nesta categoria.</div><?php else:foreach($items as $p):?><div class="item-row"><div><?php if($p['photo']):?><img class="thumb" src="../assets/images/precificacao/<?=htmlspecialchars(rawurlencode($p['photo']))?>" alt="<?=htmlspecialchars($p['item_name'])?>" onerror="this.onerror=null;this.src='../assets/images/precificacao/default.webp'"><?php else:?><div class="thumb"></div><?php endif;?></div><div><div class="item-name"><?=htmlspecialchars($p['item_name'])?></div><div class="muted">Foto: <span id="photo-<?=$p['id']?>"><?=htmlspecialchars($p['photo']?:'—')?></span> · Posição: <?=intval($p['position'])?></div></div><div class="price price-col">Mínimo<br>R$ <?=number_format((float)$p['price_min'],2,',','.')?></div><div class="price price-col">Máximo<br>R$ <?=number_format((float)$p['price_max'],2,',','.')?></div><div class="muted price-col">Posição<br><strong><?=intval($p['position'])?></strong></div><div class="actions-inline"><button type="button" class="btnx gold auto-one" data-id="<?=$p['id']?>">🔎 Auto</button><a class="btnx edit" href="?id=<?=$categoryId?>&edit_product=<?=$p['id']?>#manual">✏️ Editar</a><form method="post" style="display:inline" onsubmit="return confirm('Excluir este produto?')"><input type="hidden" name="action" value="product_delete"><input type="hidden" name="category_id" value="<?=$categoryId?>"><input type="hidden" name="id" value="<?=$p['id']?>"><button class="btnx danger" type="submit">🗑 Excluir</button></form></div></div><?php endforeach;endif;?></section>
<section class="box tab-pane" id="quick"><h2>Cadastro rápido TXT + Imagens</h2><p class="sub">Envie <strong>um ZIP com o TXT e as imagens</strong> para corrigir vários itens de uma vez. O TXT continua no padrão <strong>NomeFoto / NomeItem / PrecoMin / PrecoMax</strong>. Se o item já existir, ele será atualizado pelo nome; se não existir, será criado.</p><form method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="import_txt"><input type="hidden" name="category_id" value="<?=$categoryId?>"><div class="fields"><div class="field full"><label>Arquivo TXT ou ZIP</label><input type="file" name="import_package" accept=".txt,.zip,text/plain,application/zip" required><span class="photo-help">Recomendado: ZIP contendo o TXT + as imagens. As imagens serão convertidas automaticamente para WebP 500x500.</span></div><div class="field full"><label>Formato do TXT</label><div class="format"><b>NomeFoto / NomeItem / PrecoMin / PrecoMax</b><br>whisky.webp / Whiskey / 4,87 / 5,57<br>gin_cerveja.webp / Gin Cerveja / 4,52 / 5,10<br><br><b>ZIP:</b> precificacao_salao.zip → precificacao_salao.txt + imagens/Whiskey.webp + imagens/Gin_Cerveja.webp</div></div></div><div class="actions-inline" style="margin-top:13px"><button class="btnx primary" type="submit">⚡ Importar TXT + Imagens</button></div></form></section>
<section class="box tab-pane" id="manual"><h2><?=($editingProduct?'Editar item':'Cadastrar item')?></h2><p class="sub"><?=($editingProduct?'Altere qualquer informação deste item.':'Cadastre um único item nesta categoria.')?></p><form method="post" enctype="multipart/form-data"><input type="hidden" name="action" value="product_save"><input type="hidden" name="id" value="<?=intval($editingProduct['id']??0)?>"><input type="hidden" name="category_id" value="<?=$categoryId?>"><div class="fields"><div class="field"><label>Nome do item</label><input name="item_name" value="<?=htmlspecialchars($editingProduct['item_name']??'')?>" required></div><div class="field"><label>Posição</label><input type="number" name="position" value="<?=intval($editingProduct['position']??0)?>" min="0"></div><div class="field"><label>Nome da foto</label><input name="photo" value="<?=htmlspecialchars($editingProduct['photo']??'')?>" placeholder="produto.webp"><span class="photo-help">Nome do arquivo WebP já enviado.</span></div><div class="field"><label>Enviar nova foto</label><input type="file" name="photo_file" accept="image/jpeg,image/png,image/webp"><span class="photo-help">JPG, PNG ou WebP → convertido automaticamente para WebP 500x500.</span></div><div class="field"><label>Preço mínimo</label><input name="price_min" value="<?=isset($editingProduct['price_min'])?number_format((float)$editingProduct['price_min'],2,',',''):''?>" placeholder="0,00" required></div><div class="field"><label>Preço máximo</label><input name="price_max" value="<?=isset($editingProduct['price_max'])?number_format((float)$editingProduct['price_max'],2,',',''):''?>" placeholder="0,00" required></div></div><div class="actions-inline" style="margin-top:13px"><button class="btnx primary" type="submit"><?=($editingProduct?'💾 Salvar alterações':'➕ Cadastrar item')?></button><?php if($editingProduct):?><a class="btnx" href="?id=<?=$categoryId?>">Cancelar</a><?php endif;?></div></form></section>
</main><script>
const tabs=[...document.querySelectorAll('.tab')];tabs.forEach(b=>b.addEventListener('click',()=>{tabs.forEach(x=>x.classList.remove('active'));document.querySelectorAll('.tab-pane').forEach(x=>x.classList.remove('active'));b.classList.add('active');document.getElementById(b.dataset.tab).classList.add('active');}));
async function autoFind(productId,button){const original=button.innerHTML;button.disabled=true;button.innerHTML='⏳ Buscando...';try{const fd=new FormData();fd.append('product_id',productId);const r=await fetch('precificacao_auto_imagem.php',{method:'POST',body:fd,credentials:'same-origin'});const raw=await r.text();let d;try{d=JSON.parse(raw);}catch(e){throw new Error('O servidor retornou HTML em vez de JSON. Verifique o erro do PHP em precificacao_auto_imagem.php.');}if(!r.ok||!d.success)throw new Error(d.error||('Falha ao buscar imagem (HTTP '+r.status+').'));const el=document.getElementById('photo-'+productId);if(el)el.textContent=d.photo||'default.webp';const row=button.closest('.item-row');const img=row?.querySelector('img.thumb');if(img&&d.photo){img.src='../assets/images/precificacao/'+encodeURIComponent(d.photo)+'?v='+Date.now();img.style.display='block';}else if(row&&d.photo){const holder=row.querySelector('.thumb');if(holder){holder.outerHTML='<img class="thumb" src="../assets/images/precificacao/'+encodeURIComponent(d.photo)+'?v='+Date.now()+'" alt="" onerror="this.onerror=null;this.src=\'../assets/images/precificacao/default.webp\'">';}}button.innerHTML=d.preserved?'✓ Mantida':(d.found?'✓ Encontrada':'✓ Fallback');setTimeout(()=>button.innerHTML=original,1400);}catch(e){alert(e.message);button.innerHTML=original;}finally{button.disabled=false;}}
document.querySelectorAll('.auto-one').forEach(b=>b.addEventListener('click',()=>autoFind(b.dataset.id,b)));
document.getElementById('autoFindAll')?.addEventListener('click',async()=>{const buttons=[...document.querySelectorAll('.auto-one')];if(!buttons.length){alert('Não há itens nesta categoria.');return;}const master=document.getElementById('autoFindAll');master.disabled=true;master.innerHTML='⏳ Buscando...';for(const b of buttons)await autoFind(b.dataset.id,b);master.disabled=false;master.innerHTML='✓ Busca concluída';setTimeout(()=>master.innerHTML='🔎 Auto buscar imagens',1800);});
<?php if($editingProduct):?>document.querySelector('[data-tab="manual"]').click();document.getElementById('manual').scrollIntoView({behavior:'smooth',block:'start'});<?php endif;?>
</script></body></html>
