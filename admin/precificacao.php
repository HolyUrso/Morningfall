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

$uploadDir = dirname(__DIR__) . '/assets/images/precificacao';
if (!is_dir($uploadDir)) @mkdir($uploadDir, 0755, true);

function priceVal($v) {
    $v = trim((string)$v);
    $v = str_replace(['R$', 'r$', ' '], '', $v);
    if (str_contains($v, ',')) {
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
    }
    return is_numeric($v) ? (float)$v : null;
}

function saveWebpUpload(string $field, string $uploadDir): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Não foi possível enviar a imagem.');
    if ($_FILES[$field]['size'] > 8 * 1024 * 1024) throw new RuntimeException('A imagem deve ter no máximo 8 MB.');
    $info = @getimagesize($_FILES[$field]['tmp_name']);
    if (!$info) throw new RuntimeException('O arquivo enviado não é uma imagem válida.');
    $mime = $info['mime'] ?? '';
    if (!in_array($mime, ['image/jpeg','image/png','image/webp'], true)) throw new RuntimeException('Use uma imagem JPG, PNG ou WebP.');
    if (!function_exists('imagewebp')) throw new RuntimeException('O servidor não possui suporte para conversão WebP.');
    if ($mime === 'image/jpeg') $im = @imagecreatefromjpeg($_FILES[$field]['tmp_name']);
    elseif ($mime === 'image/png') $im = @imagecreatefrompng($_FILES[$field]['tmp_name']);
    else $im = @imagecreatefromwebp($_FILES[$field]['tmp_name']);
    if (!$im) throw new RuntimeException('Não foi possível processar a imagem.');
    if (function_exists('imagepalettetotruecolor')) @imagepalettetotruecolor($im);
    imagealphablending($im, false);
    imagesavealpha($im, true);
    $name = 'item_' . bin2hex(random_bytes(8)) . '.webp';
    $target = $uploadDir . '/' . $name;
    $ok = @imagewebp($im, $target, 88);
    imagedestroy($im);
    if (!$ok) throw new RuntimeException('Não foi possível converter a imagem para WebP.');
    return $name;
}

$msg = '';
$error = '';
$editingCategory = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $action = $_POST['action'] ?? '';

        if ($action === 'category_save') {
            $name = trim($_POST['name'] ?? '');
            $pos = (int)($_POST['position'] ?? 0);
            $id = (int)($_POST['id'] ?? 0);
            if ($name === '') throw new RuntimeException('Informe o nome da categoria.');
            if ($id) {
                $st = $pdo->prepare('UPDATE pricing_categories SET name=?,position=? WHERE id=?');
                $st->execute([$name, $pos, $id]);
                auditLog($pdo, 'Categoria de precificação alterada', 'Precificação', $id, $name, 'Categoria alterada.');
                $msg = 'Categoria atualizada.';
            } else {
                $st = $pdo->prepare('INSERT INTO pricing_categories(name,position) VALUES(?,?)');
                $st->execute([$name, $pos]);
                $new = (int)$pdo->lastInsertId();
                auditLog($pdo, 'Categoria de precificação criada', 'Precificação', $new, $name, 'Categoria criada.');
                $msg = 'Categoria criada.';
            }
        } elseif ($action === 'category_delete') {
            $id = (int)($_POST['id'] ?? 0);
            $q = $pdo->prepare('SELECT name FROM pricing_categories WHERE id=?');
            $q->execute([$id]);
            $r = $q->fetch();
            if ($r) {
                /*
                 * Excluir categoria remove apenas os registros do banco.
                 * As imagens são uma biblioteca compartilhada da precificação
                 * e nunca devem ser apagadas ao excluir produtos/categorias.
                 */
                $pdo->prepare('DELETE FROM pricing_categories WHERE id=?')->execute([$id]);
                auditLog($pdo, 'Categoria de precificação excluída', 'Precificação', $id, $r['name'], 'Categoria e produtos excluídos.');
            }
            $msg = 'Categoria excluída.';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

if (isset($_GET['edit_category'])) {
    $st = $pdo->prepare('SELECT * FROM pricing_categories WHERE id=?');
    $st->execute([(int)$_GET['edit_category']]);
    $editingCategory = $st->fetch() ?: null;
}

$cats = $pdo->query('SELECT * FROM pricing_categories ORDER BY position,name')->fetchAll();
$counts = [];
$q = $pdo->query('SELECT category_id,COUNT(*) total FROM pricing_products WHERE active=1 GROUP BY category_id');
foreach ($q->fetchAll() as $r) $counts[(int)$r['category_id']] = (int)$r['total'];
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Precificação • Master</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.price-admin{max-width:1100px;margin:auto}.box{background:#111722;border:1px solid #29344a;border-radius:14px;padding:20px;margin-bottom:16px}.box h2{margin:0 0 5px;color:#fff;font-size:18px}.sub{color:#8795ad;font-size:11px;margin:0 0 16px}.fields{display:grid;grid-template-columns:1fr 150px;gap:11px}.field{display:grid;gap:6px}.field label{font-size:10px;color:#9aa8bf;font-weight:700}.field input{width:100%;height:40px;background:#0b111b;color:#fff;border:1px solid #33405a;border-radius:8px;padding:0 11px;outline:none;font:inherit;font-size:11px;box-sizing:border-box}.field input:focus{border-color:#d4af37}.btnx{border:1px solid #34415a;background:#192235;color:#fff;border-radius:8px;padding:9px 13px;font-weight:700;font-size:11px;cursor:pointer;text-decoration:none;display:inline-block}.btnx.gold{background:#3b3113;border-color:#8f7620;color:#f1d76c}.btnx.primary{background:#8f2525;border-color:#a33131}.btnx.danger{background:#38171b;border-color:#6e2830;color:#ff9a9a}.btnx.edit{background:#182b3c;border-color:#315b78;color:#a9dcff}.actions{display:flex;gap:7px;margin-top:13px;flex-wrap:wrap}.notice{padding:10px 13px;border-radius:8px;margin-bottom:13px;font-size:11px}.ok{background:#18351f;border:1px solid #2d7740;color:#7de095}.err{background:#42191d;border:1px solid #8c3039;color:#ff9ba2}.category-card{background:#0e151f;border:1px solid #29344a;border-radius:12px;padding:18px;display:flex;align-items:center;justify-content:space-between;gap:18px;margin-top:11px;transition:.15s;color:inherit;text-decoration:none}.category-card:hover{border-color:#6d5a1e;background:#111a26;transform:translateY(-1px)}.category-main{display:flex;align-items:center;gap:13px;min-width:0}.category-icon{width:42px;height:42px;border-radius:10px;border:1px solid #6d5a1e;background:#211c0d;color:#e2c64e;display:grid;place-items:center;font-size:19px}.category-name{font-size:15px;font-weight:800;color:#fff}.category-meta{font-size:9px;color:#748198;margin-top:5px}.category-actions{display:flex;align-items:center;gap:7px;flex-wrap:wrap}.category-count{padding:5px 8px;border-radius:999px;background:#221d0d;border:1px solid #6d5a1e;color:#e2c64e;font-weight:700;font-size:8px}.upload-box{border:1px dashed #3b4962;border-radius:10px;padding:15px;background:#0b1118}.upload-box input[type=file]{color:#aeb9ca;font-size:11px;width:100%}.upload-help{font-size:9px;color:#748198;margin-top:7px;line-height:1.6}.current-edit{padding:10px 13px;border:1px solid #6d5a1e;border-radius:9px;background:#211c0d;color:#e9d474;font-size:11px;margin-bottom:12px;display:flex;justify-content:space-between;gap:12px;align-items:center}@media(max-width:700px){.category-card{align-items:flex-start;flex-direction:column}.category-actions{width:100%}.category-actions .btnx{flex:1;text-align:center}.fields{grid-template-columns:1fr}.category-main{width:100%}}
</style>
</head>
<body>
<header class="topbar"><div><b>CARMESIM</b> <span>CREATORS</span></div><div class="top-user"><?=htmlspecialchars($_SESSION['staff_name']??'')?> · <a href="../logout.php">Sair</a></div></header>
<main class="container price-admin">
<div class="page-head"><div><p class="eyebrow">ÁREA MASTER</p><h1>Precificação</h1><p class="sub">Gerencie as categorias e entre em cada uma para editar somente os seus produtos.</p></div><div class="head-actions"><a class="btnx gold" href="../pages/precificacao.html" target="_blank">👁 Ver página pública</a><a class="btnx" href="index.php">← Dashboard</a></div></div>
<?php if($msg):?><div class="notice ok"><?=htmlspecialchars($msg)?></div><?php endif;?>
<?php if($error):?><div class="notice err"><?=htmlspecialchars($error)?></div><?php endif;?>
<section class="box"><h2>Categorias</h2><p class="sub">Clique na categoria para abrir uma página exclusiva com os itens dela.</p>
<?php if(!$cats): ?><div style="color:#748198;font-size:11px;padding:15px 0">Nenhuma categoria cadastrada.</div><?php else: foreach($cats as $c): $id=(int)$c['id']; $count=(int)($counts[$id]??0); ?>
<a class="category-card" href="precificacao_categoria.php?id=<?=$id?>">
  <div class="category-main"><div class="category-icon">🏷️</div><div><div class="category-name"><?=htmlspecialchars($c['name'])?></div><div class="category-meta">Posição <?=intval($c['position'])?></div></div></div>
  <div class="category-actions"><span class="category-count"><?=$count?> item<?=($count===1?'':'s')?></span><span class="btnx primary">📂 Gerenciar</span><span class="btnx edit" onclick="event.preventDefault();event.stopPropagation();location.href='?edit_category=<?=$id?>'">✏️ Editar</span><form style="display:inline" method="post" onsubmit="event.stopPropagation();return confirm('Excluir categoria e todos os produtos dela?')"><input type="hidden" name="action" value="category_delete"><input type="hidden" name="id" value="<?=$id?>"><button class="btnx danger" type="submit" onclick="event.preventDefault();event.stopPropagation();this.form.submit();">Excluir</button></form></div>
</a>
<?php endforeach; endif; ?></section>
<?php if($editingCategory): ?><section class="box"><div class="current-edit"><span>✏️ Editando categoria: <strong><?=htmlspecialchars($editingCategory['name'])?></strong></span><a class="btnx" href="precificacao.php">Cancelar</a></div><form method="post"><input type="hidden" name="action" value="category_save"><input type="hidden" name="id" value="<?=intval($editingCategory['id'])?>"><div class="fields"><div class="field"><label>Nome da categoria</label><input name="name" value="<?=htmlspecialchars($editingCategory['name'])?>" required></div><div class="field"><label>Posição</label><input type="number" name="position" value="<?=intval($editingCategory['position'])?>" min="0"></div></div><div class="actions"><button class="btnx primary" type="submit">💾 Salvar categoria</button><a class="btnx" href="precificacao.php">Cancelar</a></div></form></section><?php endif; ?>
<section class="box"><h2>📦 Enviar pacote de imagens</h2><p class="sub">Envie um único ZIP com as imagens dos produtos. O sistema converte JPG, JPEG e PNG para WebP e salva tudo em <strong>assets/images/precificacao</strong>.</p><form method="post" action="precificacao_imagens.php" enctype="multipart/form-data"><div class="upload-box"><input type="file" name="image_zip" accept=".zip,application/zip" required><div class="upload-help">Use nomes que correspondam ao campo <strong>NomeFoto</strong> dos seus produtos. Ex.: <strong>whiskey.jpg</strong> vira <strong>whiskey.webp</strong>. Imagens WebP já existentes também podem ser enviadas.</div></div><div class="actions"><button class="btnx gold" type="submit">📤 Enviar ZIP e converter para WebP</button></div></form></section>
<section class="box"><h2>Nova categoria</h2><p class="sub">Crie uma categoria e depois clique nela para cadastrar os produtos.</p><form method="post"><input type="hidden" name="action" value="category_save"><input type="hidden" name="id" value="0"><div class="fields"><div class="field"><label>Nome da categoria</label><input name="name" placeholder="Ex.: Saloon" required></div><div class="field"><label>Posição</label><input type="number" name="position" value="0" min="0"></div></div><div class="actions"><button class="btnx primary" type="submit">➕ Criar categoria</button></div></form></section>
</main>
</body></html>
