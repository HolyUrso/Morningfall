<?php
require_once '../config/auth.php';
require_once '../config/database.php';

if (($_SESSION['staff_role'] ?? '') !== 'master') {
    http_response_code(403);
    exit('Acesso restrito ao Staff Master.');
}

$categoryId = (int)($_GET['id'] ?? 0);
if ($categoryId <= 0) {
    http_response_code(400);
    exit('Categoria inválida.');
}

$cat = $pdo->prepare('SELECT name FROM pricing_categories WHERE id=? AND active=1 LIMIT 1');
$cat->execute([$categoryId]);
$category = $cat->fetchColumn();
if ($category === false) {
    http_response_code(404);
    exit('Categoria não encontrada.');
}

$q = $pdo->prepare('SELECT photo,item_name,price_min,price_max FROM pricing_products WHERE category_id=? AND active=1 ORDER BY position,item_name');
$q->execute([$categoryId]);
$items = $q->fetchAll(PDO::FETCH_ASSOC);

// UTF-8 com BOM para abrir corretamente no Bloco de Notas/Excel sem perder acentos.
$filename = 'precificacao_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $category) . '.txt';
$filename = trim($filename, '_') ?: 'precificacao_categoria.txt';

// Gera um pacote ZIP contendo o TXT e todas as imagens utilizadas pelos itens da categoria.
if (!class_exists('ZipArchive')) {
    http_response_code(500);
    exit('A extensão ZIP do PHP não está disponível no servidor.');
}

$baseDir = realpath(__DIR__ . '/../assets/images/precificacao');
if ($baseDir === false || !is_dir($baseDir)) {
    http_response_code(500);
    exit('Pasta de imagens da precificação não encontrada.');
}

$txt = "\xEF\xBB\xBF";
$images = [];
foreach ($items as $item) {
    $photo = trim((string)($item['photo'] ?? ''));
    $photo = $photo !== '' ? basename($photo) : 'default.webp';
    $name  = trim((string)$item['item_name']);
    $min   = number_format((float)$item['price_min'], 2, ',', '');
    $max   = number_format((float)$item['price_max'], 2, ',', '');
    $txt .= $photo . ' / ' . $name . ' / ' . $min . ' / ' . $max . "\r\n";
    $candidate = $baseDir . DIRECTORY_SEPARATOR . $photo;
    if (is_file($candidate)) {
        $images[$photo] = $candidate;
    }
}

// Sempre inclui os modelos protegidos se existirem, para o pacote ficar autocontido.
foreach (['default.webp', 'defull.webp'] as $protected) {
    $candidate = $baseDir . DIRECTORY_SEPARATOR . $protected;
    if (is_file($candidate)) {
        $images[$protected] = $candidate;
    }
}

set_time_limit(0);
ignore_user_abort(true);
if (session_status() === PHP_SESSION_ACTIVE) { @session_write_close(); }
$tmp = tempnam(sys_get_temp_dir(), 'carmesim_precificacao_');
$zip = new ZipArchive();
if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
    @unlink($tmp);
    http_response_code(500);
    exit('Não foi possível criar o arquivo ZIP.');
}

$zip->addFromString($filename, $txt);
foreach ($images as $imageName => $imagePath) {
    $zip->addFile($imagePath, 'imagens/' . $imageName);
}
$zip->close();

if (!is_file($tmp)) {
    http_response_code(500);
    exit('Arquivo ZIP temporário não foi criado.');
}

$zipName = 'precificacao_' . preg_replace('/[^A-Za-z0-9_-]+/', '_', $category) . '_TXT_E_IMAGENS.zip';
$zipName = trim($zipName, '_') ?: 'precificacao_categoria_TXT_E_IMAGENS.zip';

header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $zipName . '"');
header('Content-Length: ' . filesize($tmp));
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Connection: close');

while (ob_get_level() > 0) { @ob_end_clean(); }

$fp = @fopen($tmp, 'rb');
if ($fp === false) {
    @unlink($tmp);
    http_response_code(500);
    exit('Não foi possível abrir o ZIP para download.');
}
while (!feof($fp)) {
    $buffer = fread($fp, 1024 * 1024);
    if ($buffer === false) break;
    echo $buffer;
    flush();
}
fclose($fp);
@unlink($tmp);
exit;
exit;
