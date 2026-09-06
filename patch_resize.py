from pathlib import Path

root=Path('/mnt/data/resize_work')
helper='''\nfunction resizeTo500Square($im) {\n    $srcW = imagesx($im);\n    $srcH = imagesy($im);\n    if ($srcW <= 0 || $srcH <= 0) return $im;\n\n    $dst = imagecreatetruecolor(500, 500);\n    imagealphablending($dst, false);\n    imagesavealpha($dst, true);\n    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);\n    imagefilledrectangle($dst, 0, 0, 499, 499, $transparent);\n\n    // Mantém a proporção e corta apenas o excesso para chegar exatamente a 500x500.\n    $scale = max(500 / $srcW, 500 / $srcH);\n    $newW = max(1, (int)round($srcW * $scale));\n    $newH = max(1, (int)round($srcH * $scale));\n    $tmp = imagecreatetruecolor($newW, $newH);\n    imagealphablending($tmp, false);\n    imagesavealpha($tmp, true);\n    imagefilledrectangle($tmp, 0, 0, $newW - 1, $newH - 1, $transparent);\n    imagecopyresampled($tmp, $im, 0, 0, 0, 0, $newW, $newH, $srcW, $srcH);\n\n    $srcX = max(0, (int)floor(($newW - 500) / 2));\n    $srcY = max(0, (int)floor(($newH - 500) / 2));\n    imagecopy($dst, $tmp, 0, 0, $srcX, $srcY, 500, 500);\n    imagedestroy($tmp);\n    imagedestroy($im);\n    return $dst;\n}\n'''

# category manual upload
p=root/'admin/precificacao_categoria.php'; s=p.read_text()
needle="function saveWebpUpload(string $field,string $dir,string $itemName):?string{"
# Insert helper before function
s=s.replace(needle, helper+'\n'+needle,1)
s=s.replace("if(function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($im);\n    imagealphablending($im,false); imagesavealpha($im,true);\n    $stem=imageStem($itemName);", "if(function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($im);\n    imagealphablending($im,false); imagesavealpha($im,true);\n    $im=resizeTo500Square($im);\n    $stem=imageStem($itemName);",1)
s=s.replace('JPG, PNG ou WebP → convertido automaticamente para WebP.','JPG, PNG ou WebP → convertido automaticamente para WebP 500x500.',1)
p.write_text(s)

# ZIP image import
p=root/'admin/precificacao_imagens.php'; s=p.read_text()
needle='function convertImageFileToWebp(string $source, string $mime, string $target): bool {'
s=s.replace(needle, helper+'\n'+needle,1)
s=s.replace("if (function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($im);\n    imagealphablending($im, false);\n    imagesavealpha($im, true);\n    $ok = @imagewebp($im, $target, 88);", "if (function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($im);\n    imagealphablending($im, false);\n    imagesavealpha($im, true);\n    $im = resizeTo500Square($im);\n    $ok = @imagewebp($im, $target, 88);",1)
s=s.replace('converte JPG, JPEG e PNG para WebP e salva tudo', 'converte JPG, JPEG, PNG e WebP para WebP 500x500 e salva tudo',1)
p.write_text(s)

# auto image conversion. Also normalize already-webp images into a new unique 500x500 file if needed.
p=root/'admin/precificacao_auto_imagem.php'; s=p.read_text()
needle="function convert_webp(string $source,string $mime,string $target): bool {"
s=s.replace(needle, helper+'\n'+needle,1)
s=s.replace("if(function_exists('imagepalettetotruecolor'))@imagepalettetotruecolor($im);\n        imagealphablending($im,false); imagesavealpha($im,true);\n        $ok=@imagewebp($im,$target,88); imagedestroy($im); return $ok;", "if(function_exists('imagepalettetotruecolor'))@imagepalettetotruecolor($im);\n        imagealphablending($im,false); imagesavealpha($im,true);\n        $im=resizeTo500Square($im);\n        $ok=@imagewebp($im,$target,88); imagedestroy($im); return $ok;",1)
old="""        if(is_file($target)){\n            // A imagem com o nome padrão já existe: reutiliza o arquivo, sem sobrescrevê-lo.\n            $targetName=$targetName;\n        }elseif($m['ext']==='webp'){\n            if(realpath($m['path'])!==realpath($target) && !@copy($m['path'],$target)) $targetName=$m['name'];\n        }else{\n            $info=@getimagesize($m['path']); $mime=$info['mime']??'';\n            if(!convert_webp($m['path'],$mime,$target)) jsonFail('Imagem encontrada, mas o servidor não conseguiu convertê-la para WebP.',500);\n        }"""
new="""        $targetExists=is_file($target);\n        $targetIs500=false;\n        if($targetExists){\n            $ti=@getimagesize($target);\n            $targetIs500=($ti && (int)($ti[0]??0)===500 && (int)($ti[1]??0)===500 && strtolower($ti['mime']??'')==='image/webp');\n        }\n        if($targetExists && $targetIs500){\n            // Já existe uma versão correta: reutiliza sem sobrescrever.\n        }else{\n            // Biblioteca permanente: nunca sobrescrevemos um arquivo existente.\n            // Se o nome padrão existir, cria uma nova versão 500x500.\n            if($targetExists){\n                $suffix=2;\n                do { $targetName=$wanted.'_'.$suffix.'.webp'; $target=$uploadDir.'/'.$targetName; $suffix++; } while(is_file($target));\n            }\n            $info=@getimagesize($m['path']); $mime=$info['mime']??'';\n            if(!convert_webp($m['path'],$mime,$target)) jsonFail('Imagem encontrada, mas o servidor não conseguiu convertê-la para WebP 500x500.',500);\n        }"""
if old not in s: raise SystemExit('auto block not found')
s=s.replace(old,new,1)
p.write_text(s)
