<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';

if (($_SESSION['staff_role'] ?? '') !== 'master') {
    http_response_code(403);
    exit('Acesso restrito ao Staff Master.');
}

$uploadDir = dirname(__DIR__) . '/assets/images/precificacao';
if (!is_dir($uploadDir) && !@mkdir($uploadDir, 0755, true)) {
    exit('Não foi possível criar a pasta de imagens.');
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

function convertImageFileToWebp(string $source, string $mime, string $target): bool {
    if (!function_exists('imagewebp')) return false;
    if ($mime === 'image/jpeg') $im = @imagecreatefromjpeg($source);
    elseif ($mime === 'image/png') $im = @imagecreatefrompng($source);
    elseif ($mime === 'image/webp') $im = @imagecreatefromwebp($source);
    else return false;
    if (!$im) return false;
    if (function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($im);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    $im = resizeTo500Square($im);
    $ok = @imagewebp($im, $target, 88);
    imagedestroy($im);
    return $ok;
}

$msg = '';
$error = '';
$converted = 0;
$skipped = 0;
$errors = [];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') throw new RuntimeException('Envie um arquivo ZIP para continuar.');
    if (empty($_FILES['image_zip']) || $_FILES['image_zip']['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Selecione um arquivo ZIP válido.');
    if ($_FILES['image_zip']['size'] > 50 * 1024 * 1024) throw new RuntimeException('O pacote ZIP deve ter no máximo 50 MB.');
    if (!class_exists('ZipArchive')) throw new RuntimeException('O servidor não possui suporte ao ZIP (ZipArchive).');
    if (!function_exists('imagewebp')) throw new RuntimeException('O servidor não possui suporte à conversão WebP.');

    $zip = new ZipArchive();
    if ($zip->open($_FILES['image_zip']['tmp_name']) !== true) throw new RuntimeException('Não foi possível abrir o ZIP.');

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->statIndex($i);
        if (!$entry || empty($entry['name'])) { $skipped++; continue; }
        $entryName = str_replace('\\', '/', $entry['name']);
        if (str_ends_with($entryName, '/')) { $skipped++; continue; }

        $base = basename($entryName);
        if ($base === '' || $base[0] === '.') { $skipped++; continue; }
        $ext = strtolower(pathinfo($base, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'], true)) { $skipped++; continue; }

        $data = $zip->getFromIndex($i);
        if ($data === false) { $errors[] = $base . ' (não foi possível ler)'; continue; }

        $tmp = tempnam(sys_get_temp_dir(), 'carmesim_img_');
        if ($tmp === false || @file_put_contents($tmp, $data) === false) { $errors[] = $base . ' (temporário)'; if ($tmp) @unlink($tmp); continue; }

        $info = @getimagesize($tmp);
        $mime = $info['mime'] ?? '';
        if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
            $errors[] = $base . ' (imagem inválida)'; @unlink($tmp); continue;
        }

        $stem = pathinfo($base, PATHINFO_FILENAME);
        $stem = preg_replace('/[^A-Za-z0-9._-]/', '_', $stem);
        $stem = trim($stem, '._-');
        if ($stem === '') { $errors[] = $base . ' (nome inválido)'; @unlink($tmp); continue; }
        // Nunca sobrescrever imagens existentes. Em colisão, cria nome único.
        $targetName = $stem . '.webp';
        $target = $uploadDir . '/' . $targetName;
        $suffix = 2;
        while (is_file($target)) {
            $targetName = $stem . '_' . $suffix . '.webp';
            $target = $uploadDir . '/' . $targetName;
            $suffix++;
        }

        if (convertImageFileToWebp($tmp, $mime, $target)) $converted++;
        else $errors[] = $base . ' (falha na conversão)';
        @unlink($tmp);
    }
    $zip->close();

    auditLog($pdo, 'Pacote de imagens de precificação enviado', 'Precificação', 0, 'Imagens', 'Convertidas '.$converted.' imagem(ns) para WebP.');
    $msg = "Concluído: {$converted} imagem(ns) convertida(s) para WebP.";
    if ($skipped) $msg .= " {$skipped} arquivo(s) ignorado(s).";
    if ($errors) $msg .= ' '.count($errors).' arquivo(s) apresentaram erro.';
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Imagens • Precificação</title><link rel="stylesheet" href="../assets/css/style.css"><style>body{min-height:100vh}.wrap{max-width:850px;margin:auto}.box{background:#111722;border:1px solid #29344a;border-radius:14px;padding:22px}.ok{background:#18351f;border:1px solid #2d7740;color:#7de095;padding:14px;border-radius:9px;font-size:11px}.err{background:#42191d;border:1px solid #8c3039;color:#ff9ba2;padding:14px;border-radius:9px;font-size:11px}.btn{display:inline-block;margin-top:14px;border:1px solid #34415a;background:#192235;color:#fff;border-radius:8px;padding:10px 14px;text-decoration:none;font-weight:700;font-size:11px}.errors{margin-top:15px;padding:12px;border:1px solid #51383c;border-radius:9px;color:#ffb2b7;font-size:10px;line-height:1.8}</style></head><body><header class="topbar"><div><b>CARMESIM</b> <span>CREATORS</span></div><div class="top-user"><?=htmlspecialchars($_SESSION['staff_name']??'')?> · <a href="../logout.php">Sair</a></div></header><main class="container wrap"><div class="page-head"><div><p class="eyebrow">ÁREA MASTER</p><h1>Imagens de Precificação</h1></div></div><section class="box"><?php if($msg):?><div class="ok"><?=htmlspecialchars($msg)?></div><?php endif;?><?php if($error):?><div class="err"><?=htmlspecialchars($error)?></div><?php endif;?><?php if($errors):?><div class="errors"><strong>Arquivos com erro:</strong><br><?=htmlspecialchars(implode(' | ', $errors))?></div><?php endif;?><a class="btn" href="precificacao.php">← Voltar para Precificação</a></section></main></body></html>
