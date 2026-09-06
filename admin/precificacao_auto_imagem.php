<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';

header('Content-Type: application/json; charset=utf-8');

function jsonFail(string $message, int $code=400): void {
    http_response_code($code);
    echo json_encode(['success'=>false,'error'=>$message], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    if (($_SESSION['staff_role'] ?? '') !== 'master') jsonFail('Acesso restrito ao Staff Master.',403);
    $productId=(int)($_POST['product_id']??0);
    if($productId<=0) jsonFail('Produto inválido.');

    $uploadDir=dirname(__DIR__).'/assets/images/precificacao';
    if(!is_dir($uploadDir) && !@mkdir($uploadDir,0755,true) && !is_dir($uploadDir)) jsonFail('Não foi possível acessar a pasta de imagens.');

    $st=$pdo->prepare('SELECT id,item_name,photo FROM pricing_products WHERE id=? LIMIT 1');
    $st->execute([$productId]); $p=$st->fetch();
    if(!$p) jsonFail('Produto não encontrado.',404);

    function norm_img_name(string $name): string {
        $s=pathinfo($name,PATHINFO_FILENAME);
        $s=trim($s);
        $map=['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a','Á'=>'a','À'=>'a','Ã'=>'a','Â'=>'a','Ä'=>'a','é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','É'=>'e','È'=>'e','Ê'=>'e','Ë'=>'e','í'=>'i','ì'=>'i','î'=>'i','ï'=>'i','Í'=>'i','Ì'=>'i','Î'=>'i','Ï'=>'i','ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o','Ó'=>'o','Ò'=>'o','Õ'=>'o','Ô'=>'o','Ö'=>'o','ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u','Ú'=>'u','Ù'=>'u','Û'=>'u','Ü'=>'u','ç'=>'c','Ç'=>'c','ñ'=>'n','Ñ'=>'n'];
        $s=strtr($s,$map);
        $s=preg_replace('/[^A-Za-z0-9]+/','_',$s)??$s;
        $s=preg_replace('/_+/','_',$s)??$s;
        return strtolower(trim($s,'_'));
    }

    function walk_images(string $dir): array {
        $out=[]; $items=@scandir($dir); if($items===false)return $out;
        foreach($items as $name){
            if($name==='.'||$name==='..')continue;
            $path=$dir.DIRECTORY_SEPARATOR.$name;
            if(is_dir($path)){ $out=array_merge($out,walk_images($path)); continue; }
            if(!is_file($path))continue;
            $ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
            if(in_array($ext,['webp','jpg','jpeg','png'],true))$out[]=['path'=>$path,'name'=>$name,'ext'=>$ext];
        }
        return $out;
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

function convert_webp(string $source,string $mime,string $target): bool {
        if(!function_exists('imagewebp'))return false;
        if($mime==='image/jpeg' && function_exists('imagecreatefromjpeg'))$im=@imagecreatefromjpeg($source);
        elseif($mime==='image/png' && function_exists('imagecreatefrompng'))$im=@imagecreatefrompng($source);
        elseif($mime==='image/webp' && function_exists('imagecreatefromwebp'))$im=@imagecreatefromwebp($source);
        else return false;
        if(!$im)return false;
        if(function_exists('imagepalettetotruecolor'))@imagepalettetotruecolor($im);
        imagealphablending($im,false); imagesavealpha($im,true);
        $im=resizeTo500Square($im);
        $ok=@imagewebp($im,$target,88); imagedestroy($im); return $ok;
    }

    $wanted=norm_img_name((string)$p['item_name']);
    $matches=[];
    foreach(walk_images($uploadDir) as $file){
        if(norm_img_name($file['name'])===$wanted)$matches[]=$file;
    }
    usort($matches,function($a,$b){return ($a['ext']==='webp'?0:1)<=>($b['ext']==='webp'?0:1);});

    $old=basename((string)($p['photo']??''));
    if($matches){
        $m=$matches[0]; $targetName=$wanted.'.webp'; $target=$uploadDir.'/'.$targetName;
        $targetExists=is_file($target);
        $targetIs500=false;
        if($targetExists){
            $ti=@getimagesize($target);
            $targetIs500=($ti && (int)($ti[0]??0)===500 && (int)($ti[1]??0)===500 && strtolower($ti['mime']??'')==='image/webp');
        }
        if($targetExists && $targetIs500){
            // Já existe uma versão correta: reutiliza sem sobrescrever.
        }else{
            // Biblioteca permanente: nunca sobrescrevemos um arquivo existente.
            // Se o nome padrão existir, cria uma nova versão 500x500.
            if($targetExists){
                $suffix=2;
                do { $targetName=$wanted.'_'.$suffix.'.webp'; $target=$uploadDir.'/'.$targetName; $suffix++; } while(is_file($target));
            }
            $info=@getimagesize($m['path']); $mime=$info['mime']??'';
            if(!convert_webp($m['path'],$mime,$target)) jsonFail('Imagem encontrada, mas o servidor não conseguiu convertê-la para WebP 500x500.',500);
        }
        $st=$pdo->prepare('UPDATE pricing_products SET photo=? WHERE id=?'); $st->execute([$targetName,$productId]);
        // NUNCA apagar a foto anterior. A pasta de imagens é uma biblioteca permanente.
        auditLog($pdo,'Imagem automática de precificação','Precificação',$productId,$p['item_name'],'Imagem encontrada: '.$targetName);
        echo json_encode(['success'=>true,'photo'=>$targetName,'found'=>true],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
    }

    $oldPath=$uploadDir.'/'.$old;
    if($old && !in_array(strtolower($old),['default.webp','defull.webp'],true) && is_file($oldPath)){
        echo json_encode(['success'=>true,'photo'=>$old,'found'=>false,'preserved'=>true,'message'=>'Nenhuma imagem correspondente encontrada. A foto atual foi preservada.'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit;
    }

    foreach(['default.webp','defull.webp'] as $fallback){
        if(is_file($uploadDir.'/'.$fallback)){
            $st=$pdo->prepare('UPDATE pricing_products SET photo=? WHERE id=?');$st->execute([$fallback,$productId]);
            echo json_encode(['success'=>true,'photo'=>$fallback,'found'=>false,'fallback'=>true],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
        }
    }
    jsonFail('Nenhuma imagem correspondente encontrada e não existe default.webp ou defull.webp.');
} catch (Throwable $e) {
    jsonFail('Erro ao buscar imagem: '.$e->getMessage(),500);
}
?>
