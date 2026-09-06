<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';

if (($_SESSION['staff_role'] ?? '') !== 'master') {
    http_response_code(403);
    exit('Acesso restrito ao Staff Master.');
}

$pdo->exec("CREATE TABLE IF NOT EXISTS admin_commands (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    command VARCHAR(100) NOT NULL UNIQUE,
    description VARCHAR(500) NOT NULL,
    position INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_admin_commands_created_by FOREIGN KEY(created_by) REFERENCES staff_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

$pdo->exec("CREATE TABLE IF NOT EXISTS staff_coordinates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(100) NOT NULL,
    subcategory VARCHAR(100) NOT NULL DEFAULT 'Geral',
    icon VARCHAR(20) NOT NULL DEFAULT '📍',
    name VARCHAR(180) NOT NULL,
    cds VARCHAR(255) NOT NULL,
    position INT NOT NULL DEFAULT 0,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_staff_coordinates_created_by FOREIGN KEY(created_by) REFERENCES staff_users(id) ON DELETE SET NULL,
    INDEX idx_staff_coordinates_category(category),
    INDEX idx_staff_coordinates_subcategory(subcategory),
    INDEX idx_staff_coordinates_active(active),
    INDEX idx_staff_coordinates_name(name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

// Permite categorias criadas pelo Master sem apagar nem recriar os locais existentes.
try { $pdo->exec("ALTER TABLE staff_coordinates MODIFY category VARCHAR(100) NOT NULL"); } catch (Throwable $e) { /* já está em VARCHAR */ }
try { $pdo->exec("ALTER TABLE staff_coordinates ADD COLUMN subcategory VARCHAR(100) NOT NULL DEFAULT 'Geral' AFTER category"); } catch (Throwable $e) { /* coluna já existe */ }
try { $pdo->exec("CREATE INDEX idx_staff_coordinates_subcategory ON staff_coordinates(subcategory)"); } catch (Throwable $e) { /* índice já existe */ }

// Cadastro dinâmico de categorias e subcategorias.
$pdo->exec("CREATE TABLE IF NOT EXISTS staff_coordinate_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    icon VARCHAR(20) NOT NULL DEFAULT '📍',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT UNSIGNED NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_staff_coord_categories_created_by FOREIGN KEY(created_by) REFERENCES staff_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS staff_coordinate_subcategories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(20) NOT NULL DEFAULT '📍',
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_coord_subcategory(category_id,name),
    CONSTRAINT fk_staff_coord_subcategories_category FOREIGN KEY(category_id) REFERENCES staff_coordinate_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

try { $pdo->exec("ALTER TABLE staff_coordinate_subcategories ADD COLUMN icon VARCHAR(20) NOT NULL DEFAULT '📍' AFTER name"); } catch (Throwable $e) { /* coluna já existe */ }

$categorySeed = [
    ['Empresas','🏢',[['Saloons','🍺'],['Ferrarias','🔨'],['Tabacarias','🚬'],['Artesanatos','🎨'],['Armarias','🔫'],['Ateliês','✂️'],['Perfumarias','🌹'],['Jornais','📰'],['Geral','📍']]],
    ['Fazendas','🌾',[['Geral','📍']]],
    ['Casas','🏠',[['Geral','📍']]],
    ['Grupos','👥',[['Geral','📍']]],
    ['Sobrenatural','👻',[['Geral','📍']]],
    ['Xerifado','⭐',[['Cavalaria','🐎'],['Geral','📍']]],
];
$hasAnyCategories = (int)$pdo->query('SELECT COUNT(*) FROM staff_coordinate_categories')->fetchColumn() > 0;
if (!$hasAnyCategories) {
    $catIns = $pdo->prepare('INSERT IGNORE INTO staff_coordinate_categories(name,icon,active,created_by) VALUES(?,?,1,?)');
    $subIns = $pdo->prepare('INSERT IGNORE INTO staff_coordinate_subcategories(category_id,name,icon,active) VALUES(?,?,?,1)');
    foreach ($categorySeed as $seed) {
        $catIns->execute([$seed[0],$seed[1],$_SESSION['staff_id'] ?? null]);
        $catIdSt = $pdo->prepare('SELECT id FROM staff_coordinate_categories WHERE name=?');
        $catIdSt->execute([$seed[0]]);
        $catId = (int)$catIdSt->fetchColumn();
        foreach ($seed[2] as $subSeed) $subIns->execute([$catId,$subSeed[0],$subSeed[1]]);
    }
}

$pdo->exec("CREATE TABLE IF NOT EXISTS staff_commands_settings (
    id TINYINT UNSIGNED PRIMARY KEY,
    top_name VARCHAR(100) NOT NULL DEFAULT 'CARMESIM STAFF',
    eyebrow_name VARCHAR(150) NOT NULL DEFAULT 'CARMESIM ROLEPLAY · STAFF',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("INSERT IGNORE INTO staff_commands_settings (id, top_name, eyebrow_name) VALUES (1, 'CARMESIM STAFF', 'CARMESIM ROLEPLAY · STAFF')");
$brandSettings = $pdo->query('SELECT top_name, eyebrow_name FROM staff_commands_settings WHERE id=1')->fetch(PDO::FETCH_ASSOC) ?: ['top_name'=>'CARMESIM STAFF','eyebrow_name'=>'CARMESIM ROLEPLAY · STAFF'];

// Preenche automaticamente a subcategoria dos locais antigos pelas categorias que já usamos.
$pdo->exec("UPDATE staff_coordinates SET subcategory = CASE
    WHEN LOWER(name) LIKE '%saloon%' THEN 'Saloons'
    WHEN LOWER(name) LIKE '%ferraria%' THEN 'Ferrarias'
    WHEN LOWER(name) LIKE '%tabacaria%' THEN 'Tabacarias'
    WHEN LOWER(name) LIKE '%artesanato%' THEN 'Artesanatos'
    WHEN LOWER(name) LIKE '%armaria%' THEN 'Armarias'
    WHEN LOWER(name) LIKE '%ateliê%' OR LOWER(name) LIKE '%atelie%' THEN 'Ateliês'
    WHEN LOWER(name) LIKE '%perfumaria%' THEN 'Perfumarias'
    WHEN LOWER(name) LIKE '%jornal%' THEN 'Jornais'
    ELSE subcategory END
WHERE subcategory = 'Geral'");

$commandCount = (int)$pdo->query('SELECT COUNT(*) FROM admin_commands')->fetchColumn();
if ($commandCount === 0) {
    $seed = [
        ['/painel','Abre o Painel da Staff.',1],
        ['/tpm','Teleporta até a marcação do mapa.',2],
        ['/cds','Mostra a CDS da localização atual.',3],
        ['/nc','Ativa ou desativa o Noclip e Invisibilidade.',4],
        ['/pedpainel','Painel de Peds (Acesso Restrito).',5],
        ['/dv','Guarda a carroça ou veículo utilizado.',6],
        ['/revive','Revive você mesmo.',7],
        ['/revive iddbabota','Revive outro jogador utilizando o ID.',8],
        ['/god','Enche fome, sede, vida e remove o stress.',9],
        ['/god iddbabota','Enche fome, sede, vida e remove o stress de outro jogador.',10],
        ['/tpto iddbabota','Teleporta você até o jogador informado.',11],
        ['/tptome iddbabota','Teleporta o jogador informado até você.',12]
    ];
    $ins = $pdo->prepare('INSERT IGNORE INTO admin_commands(command,description,position,active,created_by) VALUES(?,?,?,?,?)');
    foreach ($seed as $item) $ins->execute([$item[0],$item[1],$item[2],1,$_SESSION['staff_id'] ?? null]);
}

$msg = '';
$error = '';

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function inferCoordinateSubcategory(string $name): string {
    $n = mb_strtolower($name, 'UTF-8');
    if (str_contains($n, 'saloon')) return 'Saloons';
    if (str_contains($n, 'ferraria')) return 'Ferrarias';
    if (str_contains($n, 'tabacaria')) return 'Tabacarias';
    if (str_contains($n, 'artesanato')) return 'Artesanatos';
    if (str_contains($n, 'armaria')) return 'Armarias';
    if (str_contains($n, 'ateliê') || str_contains($n, 'atelie')) return 'Ateliês';
    if (str_contains($n, 'perfumaria')) return 'Perfumarias';
    if (str_contains($n, 'jornal')) return 'Jornais';
    return 'Geral';
}

function normalizeCommand($value){
    $value = trim((string)$value);
    if ($value === '') return '';
    return '/' . ltrim($value, '/');
}

/**
 * Mantém a ordem dos comandos sempre contínua (1, 2, 3, ...).
 * Quando $movingId é informado, o comando é inserido na posição desejada.
 */
function reorderCommands(PDO $pdo, int $movingId, int $requestedPosition): void {
    $st = $pdo->query('SELECT id FROM admin_commands ORDER BY position ASC, id ASC');
    $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));

    $ids = array_values(array_filter($ids, static fn($id) => $id !== $movingId));
    $countAfterRemove = count($ids);

    // 0 ou menor = automático: coloca no final.
    if ($requestedPosition <= 0) {
        $index = $countAfterRemove;
    } else {
        $index = min(max($requestedPosition - 1, 0), $countAfterRemove);
    }

    array_splice($ids, $index, 0, [$movingId]);

    $up = $pdo->prepare('UPDATE admin_commands SET position=? WHERE id=?');
    foreach ($ids as $position => $id) {
        $up->execute([$position + 1, $id]);
    }
}

function compactCommandOrder(PDO $pdo): void {
    $ids = array_map('intval', $pdo->query('SELECT id FROM admin_commands ORDER BY position ASC, id ASC')->fetchAll(PDO::FETCH_COLUMN));
    $up = $pdo->prepare('UPDATE admin_commands SET position=? WHERE id=?');
    foreach ($ids as $position => $id) {
        $up->execute([$position + 1, $id]);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'save_branding') {
            $topName = trim((string)($_POST['top_name'] ?? ''));
            $eyebrowName = trim((string)($_POST['eyebrow_name'] ?? ''));
            if ($topName === '' || mb_strlen($topName) > 100) throw new RuntimeException('Informe um nome de topo válido.');
            if ($eyebrowName === '' || mb_strlen($eyebrowName) > 150) throw new RuntimeException('Informe um texto de identificação válido.');
            $before = $pdo->query('SELECT * FROM staff_commands_settings WHERE id=1')->fetch(PDO::FETCH_ASSOC);
            $st = $pdo->prepare('UPDATE staff_commands_settings SET top_name=?, eyebrow_name=? WHERE id=1');
            $st->execute([$topName, $eyebrowName]);
            $brandSettings = ['top_name'=>$topName,'eyebrow_name'=>$eyebrowName];
            auditLog($pdo,'Alterou identidade da página de comandos','Comandos Admin',null,'Identidade Staff','Cabeçalho da página atualizado',$before,$brandSettings);
            $msg = 'Nomes da página de comandos atualizados com sucesso.';
        }

        if ($action === 'create') {
            $command = normalizeCommand($_POST['command'] ?? '');
            $description = trim((string)($_POST['description'] ?? ''));
            $position = max(0, (int)($_POST['position'] ?? 0));

            if ($command === '' || !preg_match('/^\/[a-zA-Z0-9_\-]+(?:\s+[a-zA-Z0-9_\-]+)*$/', $command)) {
                throw new RuntimeException('Informe um comando válido, por exemplo: /god ou /god iddbabota.');
            }
            if ($description === '') throw new RuntimeException('Informe a descrição do comando.');

            // A ordem 0 significa automática: próximo número disponível.
            if ($position <= 0) {
                $position = (int)$pdo->query('SELECT COALESCE(MAX(position),0)+1 FROM admin_commands')->fetchColumn();
            }

            $pdo->beginTransaction();
            try {
                $st = $pdo->prepare('INSERT INTO admin_commands(command,description,position,active,created_by) VALUES(?,?,?,?,?)');
                $st->execute([$command,$description,$position,1,(int)$_SESSION['staff_id']]);
                $newId = (int)$pdo->lastInsertId();
                reorderCommands($pdo, $newId, $position);
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }

            auditLog($pdo,'Criou comando administrativo','Comandos Admin',null,$command,'Comando criado',['command'=>null],['command'=>$command,'description'=>$description,'position'=>$position]);
            $msg = 'Comando criado com sucesso. A ordem foi organizada automaticamente.';
        }

        if ($action === 'edit') {
            $id = (int)($_POST['id'] ?? 0);
            $command = normalizeCommand($_POST['command'] ?? '');
            $description = trim((string)($_POST['description'] ?? ''));
            $position = max(0, (int)($_POST['position'] ?? 0));
            if ($id <= 0) throw new RuntimeException('Comando inválido.');
            if ($command === '' || !preg_match('/^\/[a-zA-Z0-9_\-]+(?:\s+[a-zA-Z0-9_\-]+)*$/', $command)) throw new RuntimeException('Informe um comando válido.');
            if ($description === '') throw new RuntimeException('Informe a descrição do comando.');

            $oldSt = $pdo->prepare('SELECT * FROM admin_commands WHERE id=?');
            $oldSt->execute([$id]);
            $before = $oldSt->fetch(PDO::FETCH_ASSOC);
            if (!$before) throw new RuntimeException('Comando não encontrado.');

            // Na edição, a ordem atual é mantida se o número não foi alterado.
            // Se o Master escolher outra ordem, os demais comandos são deslocados e
            // a sequência volta automaticamente para 1, 2, 3, 4...
            $requestedPosition = $position > 0 ? $position : (int)$before['position'];

            $pdo->beginTransaction();
            try {
                $st = $pdo->prepare('UPDATE admin_commands SET command=?,description=? WHERE id=?');
                $st->execute([$command,$description,$id]);
                reorderCommands($pdo, $id, $requestedPosition);
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }

            auditLog($pdo,'Editou comando administrativo','Comandos Admin',null,$command,'Comando alterado',$before,['command'=>$command,'description'=>$description,'position'=>$requestedPosition,'active'=>(int)$before['active']]);
            $msg = $requestedPosition !== (int)$before['position']
                ? 'Comando atualizado e ordem reorganizada.'
                : 'Comando atualizado com sucesso.';
        }

        if ($action === 'toggle') {
            $id = (int)($_POST['id'] ?? 0);
            $st = $pdo->prepare('SELECT * FROM admin_commands WHERE id=?');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new RuntimeException('Comando não encontrado.');
            $new = (int)!((int)$row['active']);
            $up = $pdo->prepare('UPDATE admin_commands SET active=? WHERE id=?');
            $up->execute([$new,$id]);
            auditLog($pdo,$new?'Ativou comando administrativo':'Desativou comando administrativo','Comandos Admin',null,$row['command'],'Status alterado',['active'=>(int)$row['active']],['active'=>$new]);
            $msg = $new ? 'Comando ativado.' : 'Comando desativado.';
        }

        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            $st = $pdo->prepare('SELECT * FROM admin_commands WHERE id=?');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new RuntimeException('Comando não encontrado.');
            $pdo->beginTransaction();
            try {
                $del = $pdo->prepare('DELETE FROM admin_commands WHERE id=?');
                $del->execute([$id]);
                compactCommandOrder($pdo);
                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
            auditLog($pdo,'Excluiu comando administrativo','Comandos Admin',null,$row['command'],'Comando excluído',$row,null);
            $msg = 'Comando excluído permanentemente. A ordem foi reorganizada.';
        }

        if ($action === 'categories_bulk_save') {
            $raw = (string)($_POST['bulk_payload'] ?? '');
            $payload = json_decode($raw, true);
            if (!is_array($payload) || !isset($payload['categories']) || !is_array($payload['categories'])) {
                throw new RuntimeException('Não foi possível ler as alterações em massa.');
            }

            $pdo->beginTransaction();
            try {
                $categories = $payload['categories'];
                $categoryRows = [];
                $categoryNames = [];
                $getCat = $pdo->prepare('SELECT * FROM staff_coordinate_categories WHERE id=?');
                $updateCat = $pdo->prepare('UPDATE staff_coordinate_categories SET name=?, icon=? WHERE id=?');
                $updateCoordCategory = $pdo->prepare('UPDATE staff_coordinates SET category=? WHERE category=?');

                foreach ($categories as $item) {
                    $id = (int)($item['id'] ?? 0);
                    $name = trim((string)($item['name'] ?? ''));
                    $icon = trim((string)($item['icon'] ?? '📍'));
                    if ($id <= 0 || $name === '' || mb_strlen($name) > 100) throw new RuntimeException('Existe uma categoria com nome inválido.');
                    if ($icon === '') $icon = '📍';
                    if (mb_strlen($icon) > 20) throw new RuntimeException('Existe um ícone de categoria muito grande.');
                    if (isset($categoryNames[mb_strtolower($name)])) throw new RuntimeException("A categoria '{$name}' está repetida nas alterações.");
                    $categoryNames[mb_strtolower($name)] = true;
                    $getCat->execute([$id]);
                    $before = $getCat->fetch(PDO::FETCH_ASSOC);
                    if (!$before) throw new RuntimeException('Uma das categorias não foi encontrada.');
                    $categoryRows[] = [$before, $name, $icon, $item['subs'] ?? []];
                }

                // Confere nomes contra categorias que não estão no lote.
                $existing = $pdo->query('SELECT id,name FROM staff_coordinate_categories')->fetchAll(PDO::FETCH_KEY_PAIR);
                foreach ($categoryRows as [$before,$name]) {
                    foreach ($existing as $eid => $ename) {
                        if ((int)$eid !== (int)$before['id'] && mb_strtolower((string)$ename) === mb_strtolower($name)) {
                            throw new RuntimeException("A categoria '{$name}' já existe.");
                        }
                    }
                }

                // Primeiro atualiza categorias para que as coordenadas acompanhem a nova categoria.
                foreach ($categoryRows as [$before,$name,$icon]) {
                    $updateCat->execute([$name,$icon,(int)$before['id']]);
                    if ($before['name'] !== $name) $updateCoordCategory->execute([$name,$before['name']]);
                    if ($before['name'] !== $name || $before['icon'] !== $icon) {
                        auditLog($pdo,'Editou categoria de coordenadas','Comandos Admin',null,$name,'Categoria alterada em lote',$before,['name'=>$name,'icon'=>$icon]);
                    }
                }

                $getSub = $pdo->prepare('SELECT s.*, c.name category_name FROM staff_coordinate_subcategories s JOIN staff_coordinate_categories c ON c.id=s.category_id WHERE s.id=?');
                $updateSub = $pdo->prepare('UPDATE staff_coordinate_subcategories SET name=?, icon=? WHERE id=?');
                $updateCoordSub = $pdo->prepare('UPDATE staff_coordinates SET subcategory=? WHERE category=? AND subcategory=?');

                foreach ($categoryRows as [$beforeCat,$newCatName,$catIcon,$subs]) {
                    if (!is_array($subs)) continue;
                    $subNames = [];
                    foreach ($subs as $itemSub) {
                        $sid = (int)($itemSub['id'] ?? 0);
                        $sname = trim((string)($itemSub['name'] ?? ''));
                        $sicon = trim((string)($itemSub['icon'] ?? '📍'));
                        if ($sid <= 0 || $sname === '' || mb_strlen($sname) > 100) throw new RuntimeException("Existe uma subcategoria inválida em '{$newCatName}'.");
                        if ($sicon === '') $sicon = '📍';
                        if (mb_strlen($sicon) > 20) throw new RuntimeException("O ícone da subcategoria '{$sname}' é muito grande.");
                        $key = mb_strtolower($sname);
                        if (isset($subNames[$key])) throw new RuntimeException("A subcategoria '{$sname}' está repetida em '{$newCatName}'.");
                        $subNames[$key] = true;

                        $getSub->execute([$sid]);
                        $beforeSub = $getSub->fetch(PDO::FETCH_ASSOC);
                        if (!$beforeSub) throw new RuntimeException('Uma das subcategorias não foi encontrada.');
                        if ((int)$beforeSub['category_id'] !== (int)$beforeCat['id']) throw new RuntimeException('Uma subcategoria não pertence à categoria selecionada.');

                        $dup = $pdo->prepare('SELECT COUNT(*) FROM staff_coordinate_subcategories WHERE category_id=? AND name=? AND id<>?');
                        $dup->execute([(int)$beforeCat['id'],$sname,$sid]);
                        if ((int)$dup->fetchColumn() > 0) throw new RuntimeException("A subcategoria '{$sname}' já existe em '{$newCatName}'.");

                        $updateSub->execute([$sname,$sicon,$sid]);
                        if ($beforeSub['name'] !== $sname || $beforeSub['icon'] !== $sicon) {
                            $updateCoordSub->execute([$sname,$newCatName,$beforeSub['name']]);
                            auditLog($pdo,'Editou subcategoria de coordenadas','Comandos Admin',null,$sname,'Subcategoria alterada em lote',$beforeSub,['category'=>$newCatName,'subcategory'=>$sname,'icon'=>$sicon]);
                        }
                    }
                }

                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }
            $msg = 'Todas as alterações de categorias e subcategorias foram salvas de uma vez.';
        }

        if ($action === 'category_create') {
            $name = trim((string)($_POST['category_name'] ?? ''));
            $icon = trim((string)($_POST['category_icon'] ?? '📍'));
            if ($name === '' || mb_strlen($name) > 100) throw new RuntimeException('Informe um nome de categoria válido.');
            if ($icon === '') $icon = '📍';
            if (mb_strlen($icon) > 20) throw new RuntimeException('O ícone da categoria é muito grande.');
            $st = $pdo->prepare('INSERT INTO staff_coordinate_categories(name,icon,active,created_by) VALUES(?,?,1,?)');
            $st->execute([$name,$icon,(int)$_SESSION['staff_id']]);
            $msg = 'Categoria criada com sucesso. Agora você pode adicionar subcategorias.';
            auditLog($pdo,'Criou categoria de coordenadas','Comandos Admin',null,$name,'Categoria criada',null,['name'=>$name,'icon'=>$icon]);
        }

        if ($action === 'category_edit') {
            $id = (int)($_POST['category_id'] ?? 0);
            $name = trim((string)($_POST['category_name'] ?? ''));
            $icon = trim((string)($_POST['category_icon'] ?? '📍'));
            if ($id <= 0 || $name === '' || mb_strlen($name) > 100) throw new RuntimeException('Categoria inválida.');
            if ($icon === '') $icon = '📍';
            $st = $pdo->prepare('SELECT * FROM staff_coordinate_categories WHERE id=?');
            $st->execute([$id]);
            $before = $st->fetch(PDO::FETCH_ASSOC);
            if (!$before) throw new RuntimeException('Categoria não encontrada.');
            $pdo->beginTransaction();
            try {
                $up = $pdo->prepare('UPDATE staff_coordinate_categories SET name=?,icon=? WHERE id=?');
                $up->execute([$name,$icon,$id]);
                $coordUp = $pdo->prepare('UPDATE staff_coordinates SET category=? WHERE category=?');
                $coordUp->execute([$name,$before['name']]);
                $pdo->commit();
            } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
            auditLog($pdo,'Editou categoria de coordenadas','Comandos Admin',null,$name,'Categoria alterada',$before,['name'=>$name,'icon'=>$icon]);
            $msg = 'Categoria atualizada. Os locais vinculados também foram atualizados.';
        }

        if ($action === 'category_toggle') {
            $id = (int)($_POST['category_id'] ?? 0);
            $st = $pdo->prepare('SELECT * FROM staff_coordinate_categories WHERE id=?'); $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new RuntimeException('Categoria não encontrada.');
            $new = (int)!((int)$row['active']);
            $pdo->prepare('UPDATE staff_coordinate_categories SET active=? WHERE id=?')->execute([$new,$id]);
            auditLog($pdo,$new?'Ativou categoria de coordenadas':'Desativou categoria de coordenadas','Comandos Admin',null,$row['name'],'Status alterado',['active'=>(int)$row['active']],['active'=>$new]);
            $msg = $new ? 'Categoria ativada.' : 'Categoria desativada.';
        }

        if ($action === 'category_delete') {
            $id = (int)($_POST['category_id'] ?? 0);
            $st = $pdo->prepare('SELECT * FROM staff_coordinate_categories WHERE id=?'); $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new RuntimeException('Categoria não encontrada.');
            $used = $pdo->prepare('SELECT COUNT(*) FROM staff_coordinates WHERE category=?'); $used->execute([$row['name']]);
            if ((int)$used->fetchColumn() > 0) throw new RuntimeException('Não é possível excluir esta categoria enquanto houver coordenadas vinculadas. Edite ou exclua os locais primeiro.');
            $pdo->prepare('DELETE FROM staff_coordinate_categories WHERE id=?')->execute([$id]);
            auditLog($pdo,'Excluiu categoria de coordenadas','Comandos Admin',null,$row['name'],'Categoria excluída',$row,null);
            $msg = 'Categoria excluída.';
        }

        if ($action === 'subcategory_create') {
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $name = trim((string)($_POST['subcategory_name'] ?? ''));
            $icon = trim((string)($_POST['subcategory_icon'] ?? '📍'));
            if ($icon === '') $icon = '📍';
            if (mb_strlen($icon) > 20) throw new RuntimeException('O ícone da subcategoria é muito grande.');
            if ($categoryId <= 0 || $name === '' || mb_strlen($name) > 100) throw new RuntimeException('Informe uma subcategoria válida.');
            $cat = $pdo->prepare('SELECT * FROM staff_coordinate_categories WHERE id=?'); $cat->execute([$categoryId]);
            $catRow = $cat->fetch(PDO::FETCH_ASSOC);
            if (!$catRow) throw new RuntimeException('Categoria não encontrada.');
            $st = $pdo->prepare('INSERT INTO staff_coordinate_subcategories(category_id,name,icon,active) VALUES(?,?,?,1)');
            $st->execute([$categoryId,$name,$icon]);
            auditLog($pdo,'Criou subcategoria de coordenadas','Comandos Admin',null,$name,'Subcategoria criada',null,['category'=>$catRow['name'],'subcategory'=>$name]);
            $msg = 'Subcategoria adicionada em '.$catRow['name'].'.';
        }

        if ($action === 'subcategory_edit') {
            $id = (int)($_POST['subcategory_id'] ?? 0);
            $name = trim((string)($_POST['subcategory_name'] ?? ''));
            $icon = trim((string)($_POST['subcategory_icon'] ?? '📍'));
            if ($icon === '') $icon = '📍';
            if (mb_strlen($icon) > 20) throw new RuntimeException('O ícone da subcategoria é muito grande.');
            if ($id <= 0 || $name === '' || mb_strlen($name) > 100) throw new RuntimeException('Subcategoria inválida.');
            $st = $pdo->prepare('SELECT s.*,c.name category_name FROM staff_coordinate_subcategories s JOIN staff_coordinate_categories c ON c.id=s.category_id WHERE s.id=?'); $st->execute([$id]);
            $before = $st->fetch(PDO::FETCH_ASSOC);
            if (!$before) throw new RuntimeException('Subcategoria não encontrada.');
            $pdo->beginTransaction();
            try {
                $pdo->prepare('UPDATE staff_coordinate_subcategories SET name=?, icon=? WHERE id=?')->execute([$name,$icon,$id]);
                $pdo->prepare('UPDATE staff_coordinates SET subcategory=? WHERE category=? AND subcategory=?')->execute([$name,$before['category_name'],$before['name']]);
                $pdo->commit();
            } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
            auditLog($pdo,'Editou subcategoria de coordenadas','Comandos Admin',null,$name,'Subcategoria alterada',$before,['category'=>$before['category_name'],'subcategory'=>$name]);
            $msg = 'Subcategoria atualizada. Os locais vinculados também foram atualizados.';
        }

        if ($action === 'subcategory_delete') {
            $id = (int)($_POST['subcategory_id'] ?? 0);
            $st = $pdo->prepare('SELECT s.*,c.name category_name FROM staff_coordinate_subcategories s JOIN staff_coordinate_categories c ON c.id=s.category_id WHERE s.id=?'); $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new RuntimeException('Subcategoria não encontrada.');
            $used = $pdo->prepare('SELECT COUNT(*) FROM staff_coordinates WHERE category=? AND subcategory=?'); $used->execute([$row['category_name'],$row['name']]);
            if ((int)$used->fetchColumn() > 0) throw new RuntimeException('Não é possível excluir esta subcategoria enquanto houver coordenadas vinculadas.');
            $pdo->prepare('DELETE FROM staff_coordinate_subcategories WHERE id=?')->execute([$id]);
            auditLog($pdo,'Excluiu subcategoria de coordenadas','Comandos Admin',null,$row['name'],'Subcategoria excluída',$row,null);
            $msg = 'Subcategoria excluída.';
        }

        if ($action === 'coord_create' || $action === 'coord_edit') {
            $id = (int)($_POST['coord_id'] ?? 0);
            $category = trim((string)($_POST['category'] ?? ''));
            $subcategory = trim((string)($_POST['subcategory'] ?? 'Geral'));
            $icon = trim((string)($_POST['icon'] ?? '📍'));
            $name = trim((string)($_POST['coord_name'] ?? ''));
            $cds = trim((string)($_POST['cds'] ?? ''));
            $position = max(0, (int)($_POST['coord_position'] ?? 0));
            $allowedCategories = $pdo->query('SELECT name FROM staff_coordinate_categories WHERE active=1 ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array($category, $allowedCategories, true)) throw new RuntimeException('Selecione uma categoria cadastrada e ativa.');
            $subCheck = $pdo->prepare('SELECT COUNT(*) FROM staff_coordinate_subcategories s JOIN staff_coordinate_categories c ON c.id=s.category_id WHERE c.name=? AND s.name=? AND s.active=1');
            $subCheck->execute([$category, $subcategory]);
            if ((int)$subCheck->fetchColumn() === 0) throw new RuntimeException('Selecione uma subcategoria cadastrada para esta categoria.');
            if ($subcategory === '') $subcategory = 'Geral';
            if (mb_strlen($subcategory) > 100) throw new RuntimeException('A subcategoria é muito grande.');
            if ($subcategory === '') $subcategory = 'Geral';
            if ($icon === '') $icon = '📍';
            if (mb_strlen($icon) > 20) throw new RuntimeException('O emoticon/ícone é muito grande.');
            if ($name === '') throw new RuntimeException('Informe o nome do local.');
            if ($cds === '') throw new RuntimeException('Informe o CDS.');
            if ($action === 'coord_create') {
                $st = $pdo->prepare('INSERT INTO staff_coordinates(category,subcategory,icon,name,cds,position,active,created_by) VALUES(?,?,?,?,?,?,?,?)');
                $st->execute([$category,$subcategory,$icon,$name,$cds,$position,1,(int)$_SESSION['staff_id']]);
                auditLog($pdo,'Cadastrou coordenada','Comandos Admin',null,$name,'Coordenada cadastrada',null,['category'=>$category,'subcategory'=>$subcategory,'icon'=>$icon,'name'=>$name,'cds'=>$cds,'position'=>$position]);
                $msg = 'Local cadastrado com sucesso.';
            } else {
                if ($id <= 0) throw new RuntimeException('Local inválido.');
                $oldSt = $pdo->prepare('SELECT * FROM staff_coordinates WHERE id=?');
                $oldSt->execute([$id]);
                $before = $oldSt->fetch(PDO::FETCH_ASSOC);
                if (!$before) throw new RuntimeException('Local não encontrado.');
                $st = $pdo->prepare('UPDATE staff_coordinates SET category=?,subcategory=?,icon=?,name=?,cds=?,position=? WHERE id=?');
                $st->execute([$category,$subcategory,$icon,$name,$cds,$position,$id]);
                auditLog($pdo,'Editou coordenada','Comandos Admin',null,$name,'Coordenada alterada',$before,['category'=>$category,'subcategory'=>$subcategory,'icon'=>$icon,'name'=>$name,'cds'=>$cds,'position'=>$position,'active'=>(int)$before['active']]);
                $msg = 'Local atualizado com sucesso.';
            }
        }

        if ($action === 'coord_bulk_import') {
            if (!isset($_FILES['coord_file']) || ($_FILES['coord_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Selecione um arquivo TXT válido para importar.');
            }

            $file = $_FILES['coord_file'];
            if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
                throw new RuntimeException('O arquivo TXT não pode ultrapassar 2 MB.');
            }

            $originalName = (string)($file['name'] ?? '');
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            if ($extension !== 'txt') {
                throw new RuntimeException('O arquivo precisa ter extensão .txt.');
            }

            $content = file_get_contents($file['tmp_name']);
            if ($content === false) {
                throw new RuntimeException('Não foi possível ler o arquivo enviado.');
            }

            // Remove BOM UTF-8, quando presente.
            $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
            $lines = preg_split('/\R/u', $content);
            $allowedCategories = $pdo->query('SELECT name FROM staff_coordinate_categories WHERE active=1 ORDER BY name')->fetchAll(PDO::FETCH_COLUMN);
            $nextPosition = (int)$pdo->query('SELECT COALESCE(MAX(position),0)+1 FROM staff_coordinates')->fetchColumn();
            $insert = $pdo->prepare('INSERT INTO staff_coordinates(category,subcategory,icon,name,cds,position,active,created_by) VALUES(?,?,?,?,?,?,?,?)');

            $imported = 0;
            $ignored = 0;
            $errors = [];

            $pdo->beginTransaction();
            try {
                foreach ($lines as $lineNumber => $line) {
                    $lineNumber++;
                    $line = trim((string)$line);
                    if ($line === '' || str_starts_with($line, '#')) {
                        continue;
                    }

                    $parts = str_getcsv($line, '|', '"', '\\');
                    $parts = array_map(static fn($v) => trim((string)$v), $parts);
                    if (count($parts) === 4) {
                        [$category, $icon, $name, $cds] = $parts;
                        $subcategory = inferCoordinateSubcategory($name);
                    } elseif (count($parts) === 5) {
                        [$category, $subcategory, $icon, $name, $cds] = $parts;
                    } else {
                        $errors[] = "Linha {$lineNumber}: use Categoria | Subcategoria | Ícone | Nome | CDS.";
                        continue;
                    }
                    if (!in_array($category, $allowedCategories, true)) {
                        $errors[] = "Linha {$lineNumber}: categoria '{$category}' inválida ou não cadastrada.";
                        continue;
                    }
                    $subCheck = $pdo->prepare('SELECT COUNT(*) FROM staff_coordinate_subcategories s JOIN staff_coordinate_categories c ON c.id=s.category_id WHERE c.name=? AND s.name=? AND s.active=1');
                    $subCheck->execute([$category,$subcategory]);
                    if ((int)$subCheck->fetchColumn() === 0) { $errors[] = "Linha {$lineNumber}: subcategoria '{$subcategory}' não cadastrada para a categoria '{$category}'."; continue; }
                    if ($subcategory === '') $subcategory = inferCoordinateSubcategory($name);
                    if ($subcategory === '') $subcategory = 'Geral';
                    if (mb_strlen($subcategory) > 100) {
                        $errors[] = "Linha {$lineNumber}: subcategoria muito grande.";
                        continue;
                    }
                    if ($icon === '') $icon = '📍';
                    if (mb_strlen($icon) > 20) {
                        $errors[] = "Linha {$lineNumber}: ícone/emoticon muito grande.";
                        continue;
                    }
                    if ($name === '') {
                        $errors[] = "Linha {$lineNumber}: nome do local vazio.";
                        continue;
                    }
                    if ($cds === '') {
                        $errors[] = "Linha {$lineNumber}: CDS vazio.";
                        continue;
                    }

                    // Evita duplicar exatamente o mesmo local na mesma categoria.
                    $dup = $pdo->prepare('SELECT id FROM staff_coordinates WHERE category=? AND subcategory=? AND name=? AND cds=? LIMIT 1');
                    $dup->execute([$category, $subcategory, $name, $cds]);
                    if ($dup->fetchColumn()) {
                        $ignored++;
                        continue;
                    }

                    $insert->execute([$category, $subcategory, $icon, $name, $cds, $nextPosition++, 1, (int)$_SESSION['staff_id']]);
                    $imported++;
                }

                $pdo->commit();
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                throw $e;
            }

            auditLog(
                $pdo,
                'Importou coordenadas em lote',
                'Comandos Admin',
                null,
                $originalName,
                'Importação de coordenadas TXT concluída',
                null,
                ['arquivo'=>$originalName,'importados'=>$imported,'ignorados'=>$ignored,'erros'=>count($errors)]
            );

            $msg = "Importação concluída: {$imported} local(is) cadastrado(s), {$ignored} duplicado(s) ignorado(s).";
            if ($errors) {
                $msg .= ' ' . count($errors) . ' linha(s) com erro.';
                $error = implode(' | ', $errors);
            }
        }

        if ($action === 'coord_toggle') {
            $id = (int)($_POST['coord_id'] ?? 0);
            $st = $pdo->prepare('SELECT * FROM staff_coordinates WHERE id=?');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new RuntimeException('Local não encontrado.');
            $new = (int)!((int)$row['active']);
            $up = $pdo->prepare('UPDATE staff_coordinates SET active=? WHERE id=?');
            $up->execute([$new,$id]);
            auditLog($pdo,$new?'Ativou coordenada':'Desativou coordenada','Comandos Admin',null,$row['name'],'Status da coordenada alterado',['active'=>(int)$row['active']],['active'=>$new]);
            $msg = $new ? 'Local ativado.' : 'Local desativado.';
        }

        if ($action === 'coord_delete') {
            $id = (int)($_POST['coord_id'] ?? 0);
            $st = $pdo->prepare('SELECT * FROM staff_coordinates WHERE id=?');
            $st->execute([$id]);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            if (!$row) throw new RuntimeException('Local não encontrado.');
            $del = $pdo->prepare('DELETE FROM staff_coordinates WHERE id=?');
            $del->execute([$id]);
            auditLog($pdo,'Excluiu coordenada','Comandos Admin',null,$row['name'],'Coordenada excluída',$row,null);
            $msg = 'Local excluído permanentemente.';
        }
    } catch (Throwable $e) {
        $error = $e instanceof PDOException && (int)$e->errorInfo[1] === 1062
            ? 'Esse comando já está cadastrado.'
            : $e->getMessage();
    }
}

$rows = $pdo->query('SELECT id,command,description,position,active,created_at,updated_at FROM admin_commands ORDER BY position ASC, command ASC')->fetchAll(PDO::FETCH_ASSOC);
$coordinateRows = $pdo->query("SELECT id,category,subcategory,icon,name,cds,position,active,created_at,updated_at FROM staff_coordinates ORDER BY category ASC, position ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
$coordinateCategories = $pdo->query("SELECT id,name,icon,active FROM staff_coordinate_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$coordinateSubcategories = $pdo->query("SELECT id,category_id,name,icon,active FROM staff_coordinate_subcategories ORDER BY category_id ASC,name ASC")->fetchAll(PDO::FETCH_ASSOC);
$subsByCategory = []; foreach ($coordinateSubcategories as $sub) { $subsByCategory[(int)$sub['category_id']][] = $sub; }
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Comandos Administrativos · Carmesim Creators</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.commands-hero{background:linear-gradient(135deg,#111722,#171321);border:1px solid #3b315c;border-radius:16px;padding:24px;margin-bottom:18px}.commands-hero h2{margin:0 0 8px;font-size:24px}.commands-hero p{margin:0;color:#9aa5b8;line-height:1.6}.command-admin-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.command-form{max-width:none}.command-form textarea{min-height:120px;resize:vertical}.command-list{display:grid;gap:12px}.command-row{background:#111722;border:1px solid #252e3e;border-radius:12px;padding:16px}.command-row.inactive{opacity:.55}.command-row-head{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}.command-name{font-size:20px;font-weight:800;color:#d4af37;font-family:monospace}.command-desc{color:#a4adbd;margin-top:8px;line-height:1.5}.command-actions{display:flex;flex-wrap:wrap;gap:7px;margin-top:13px}.command-actions form{display:inline}.command-actions .btn{padding:8px 11px;font-size:12px}.command-actions .delete{background:#3a1d24;color:#ffb0b8;border:1px solid #5a2a35}.command-actions .toggle{background:#263044}.command-position{font-size:11px;color:#718099;margin-top:8px}.command-form small{display:block;margin:-4px 0 14px;color:#718099;line-height:1.45}.command-form small strong{color:#d4af37}.badge-status{font-size:10px;font-weight:800;padding:4px 8px;border-radius:999px;background:#173b2c;color:#8ce8b9}.badge-status.off{background:#3a1d24;color:#ffb0b8}@media(max-width:800px){.command-admin-grid{grid-template-columns:1fr}}
.modal-backdrop{position:fixed;inset:0;background:rgba(0,0,0,.76);display:flex;align-items:center;justify-content:center;z-index:9999;padding:20px}.modal-card{width:min(650px,100%);background:#111722;border:1px solid #3b315c;border-radius:16px;padding:22px;box-shadow:0 25px 90px #000b}.modal-close{border:0;background:transparent;color:#fff;font-size:28px;cursor:pointer}

.coord-section{margin-top:18px}.coord-grid{display:grid;grid-template-columns:1fr 1.4fr;gap:18px}.coord-form{max-width:none}.coord-form select,.coord-form input{width:100%}.coord-list{display:grid;gap:10px}.coord-row{background:#111722;border:1px solid #252e3e;border-radius:12px;padding:14px}.coord-row.inactive{opacity:.5}.coord-head{display:flex;justify-content:space-between;gap:12px}.coord-name{font-weight:800;color:#fff}.coord-meta{color:#8d96a6;font-size:12px;margin-top:4px}.coord-cds{margin-top:10px;padding:10px 12px;background:#0b0e14;border-radius:8px;color:#35e58b;font:700 13px/1.4 Consolas,monospace;word-break:break-word}.coord-actions{display:flex;flex-wrap:wrap;gap:7px;margin-top:10px}.coord-actions form{display:inline}.coord-icon{font-size:22px;margin-right:6px}.coord-categories{display:flex;flex-wrap:wrap;gap:7px;margin-bottom:14px}.coord-cat{font-size:11px;padding:5px 9px;border-radius:999px;background:#20252e;color:#b7c0cf;border:1px solid #303746}.coord-cat.active{background:#3b3012;color:#d4af37;border-color:#d4af37}@media(max-width:900px){.coord-grid{grid-template-columns:1fr}}

.coord-import-box{margin:0 0 20px;padding:18px;background:#0f1520;border:1px solid #303746;border-radius:14px}.coord-import-box h3{margin:2px 0 6px;color:#fff;font-size:18px}.coord-import-form{display:flex;align-items:center;gap:10px;margin:14px 0;flex-wrap:wrap}.coord-import-form input[type=file]{max-width:100%;padding:10px;border:1px dashed #39465b;border-radius:10px;background:#111722;color:#9da7b8}.coord-format{margin-top:10px;color:#9da7b8;font-size:13px;line-height:1.6}.coord-format pre{margin:10px 0;padding:14px;overflow:auto;background:#080c12;border:1px solid #252e3e;border-radius:10px;color:#d4af37;font:12px/1.7 Consolas,monospace;white-space:pre}.coord-format code{color:#d4af37}.coord-format .btn{display:inline-block;text-decoration:none;margin-top:4px}
.category-create-grid{display:grid;grid-template-columns:1fr 1fr;gap:18px}.category-help{background:#101923;border:1px solid #303746;border-radius:12px;padding:18px;color:#9da7b8;line-height:1.6;font-size:13px}.category-help strong{color:#d4af37;font-size:15px}.category-manager-list{display:grid;gap:12px;margin-top:18px}.category-manager-card{background:#111722;border:1px solid #252e3e;border-radius:12px;padding:16px}.category-manager-head{display:flex;justify-content:space-between;gap:12px;align-items:flex-start}.category-manager-title{font-size:17px;font-weight:800;color:#fff}.category-manager-title .badge-status{margin-left:7px}.category-edit-row{margin-top:12px}.category-inline-form,.subcategory-inline-form,.subcategory-add-form,.inline-form{display:flex;gap:7px;align-items:center}.category-inline-form input,.subcategory-inline-form input,.subcategory-add-form input{background:#0b0e14;border:1px solid #303746;color:#d6d7d8;border-radius:8px;padding:9px 10px;outline:none}.category-inline-form input:first-of-type,.subcategory-inline-form input,.subcategory-add-form input{flex:1}.subcategory-list{margin-top:13px;padding-top:13px;border-top:1px solid #252e3e;display:grid;gap:8px}.subcategory-item{display:flex;gap:7px;align-items:center}.subcategory-item:before{content:'↳';color:#d4af37;font-weight:700}.subcategory-add-form{margin-top:8px}.category-manager-card .btn{white-space:nowrap}.category-manager-card .btn.secondary{background:#202a3b}.category-manager-card .btn.delete{background:#3a1d24;color:#ffb0b8;border:1px solid #5a2a35}.category-manager-card .btn.primary{background:#7657e8;color:#fff;border:0}.bulk-save-bar{display:flex;justify-content:space-between;align-items:center;gap:16px;margin:18px 0 12px;padding:14px 16px;background:#111722;border:1px solid #303746;border-radius:12px}.bulk-save-bar>div{display:flex;flex-direction:column;gap:4px}.bulk-save-bar strong{color:#fff}.emoji-input{width:58px!important;flex:0 0 58px!important;text-align:center;font-size:20px;line-height:1;background:#0b0e14!important;color:#fff!important;border:1px solid #303746!important;border-radius:8px!important;padding:7px!important}.emoji-input:focus{border-color:#7657e8!important;box-shadow:0 0 0 2px rgba(118,87,232,.16);outline:none}.emoji-input::placeholder{opacity:.7}
@media(max-width:800px){.category-create-grid{grid-template-columns:1fr}.category-manager-head{flex-direction:column}.category-inline-form{flex-wrap:wrap}}

</style>
</head>
<body>
<header class="topbar"><div><b>MORNINGFALL</b> <span>CREATORS</span></div><div class="top-user"><?=e($_SESSION['staff_name']??'Staff Master')?> · <a href="../logout.php">Sair</a></div></header>
<main class="container">
<a href="index.php">← Dashboard</a>
<div class="page-head"><div><p class="eyebrow">ÁREA MASTER</p><h1>⚡ Comandos Administrativos</h1><p class="muted">Cadastre os comandos usados pela Staff e controle o que aparece na página administrativa de comandos.</p><div style="margin-top:12px"><a class="btn secondary" href="comandos_players.php">👥 Gerenciar Comandos de Players</a></div></div></div>
<?php if($msg):?><div class="alert success"><?=e($msg)?></div><?php endif;?>
<?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?>

<div class="commands-hero">
<h2>🔐 Área administrativa</h2>
<p>Esta página é independente da Área do Streamer. Somente o Master pode criar, editar, ativar, desativar ou excluir comandos. Staff com acesso administrativo pode consultar a página de comandos.</p>
</div>

<section class="panel" style="margin-bottom:18px">
<div class="section-title"><div><p class="eyebrow">IDENTIDADE DA PÁGINA</p><h2>✏️ Nomes exibidos</h2><p class="muted">Altere os dois textos que aparecem no topo da página pública de Comandos Staff.</p></div><span class="setup-badge">MASTER</span></div>
<form class="form" method="post">
<input type="hidden" name="action" value="save_branding">
<label>Nome do topo</label>
<input name="top_name" maxlength="100" value="<?=e($brandSettings['top_name'])?>" placeholder="CARMESIM STAFF" required>
<small class="muted">Aparece no canto superior esquerdo.</small>
<label>Identificação da página</label>
<input name="eyebrow_name" maxlength="150" value="<?=e($brandSettings['eyebrow_name'])?>" placeholder="CARMESIM ROLEPLAY · STAFF" required>
<small class="muted">Aparece acima do título "Comandos Básicos da Staff".</small>
<div class="actions"><button class="btn primary">💾 Salvar nomes</button></div>
</form>
</section>

<div class="command-admin-grid">
<section class="panel">
<div class="section-title"><div><p class="eyebrow">NOVO COMANDO</p><h2>Adicionar comando</h2></div><span class="setup-badge">MASTER</span></div>
<form class="form command-form" method="post">
<input type="hidden" name="action" value="create">
<label>Comando</label><input name="command" required maxlength="100" placeholder="/god">
<label>Descrição</label><textarea name="description" required maxlength="500" placeholder="Enche fome, sede, vida e remove o stress."></textarea>
<label>Ordem <span class="muted">(automática)</span></label>
<input type="number" name="position" min="0" value="0" placeholder="0 = próximo da ordem">
<small class="muted">Deixe <strong>0</strong> para o sistema colocar automaticamente no próximo número. Altere somente se quiser inserir em outra posição.</small>
<div class="actions"><button class="btn primary">+ Criar comando</button></div>
</form>
</section>

<section class="panel">
<div class="section-title"><div><p class="eyebrow">VISUALIZAÇÃO</p><h2>Como ficará</h2></div></div>
<div class="command-row">
<div class="command-row-head"><div><div class="command-name">/god</div><div class="command-desc">Enche fome, sede, vida e remove o stress.</div></div><span class="badge-status">ATIVO</span></div>
</div>
<p class="muted" style="margin-top:12px">Os comandos cadastrados aqui aparecerão na página administrativa em cards, seguindo o visual do modelo enviado.</p>
<p style="margin-top:14px"><a class="btn secondary" href="comandos_lista.php">⚡ Abrir página de comandos</a></p>
</section>
</div>

<section class="panel" style="margin-top:18px">
<div class="section-title"><div><p class="eyebrow">CADASTRADOS</p><h2>Comandos da Staff</h2></div><span class="muted"><?=count($rows)?> comando(s)</span></div>
<?php if(!$rows): ?><div class="empty">Nenhum comando cadastrado ainda.</div><?php else: ?>
<div class="command-list">
<?php foreach($rows as $r): ?>
<div class="command-row <?=$r['active']?'':'inactive'?>">
<div class="command-row-head"><div><div class="command-name"><?=e($r['command'])?></div><div class="command-desc"><?=e($r['description'])?></div><div class="command-position">Ordem: <?=e($r['position'])?></div></div><span class="badge-status <?=$r['active']?'':'off'?>"><?=$r['active']?'ATIVO':'DESATIVADO'?></span></div>
<div class="command-actions">
<button type="button" class="btn secondary" onclick='openEdit(<?=json_encode(['id'=>(int)$r['id'],'command'=>$r['command'],'description'=>$r['description'],'position'=>(int)$r['position']],JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>)'>✏️ Editar</button>
<form method="post"><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn toggle"><?=$r['active']?'⏸ Desativar':'▶ Ativar'?></button></form>
<form method="post" onsubmit="return confirm('Excluir este comando permanentemente?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=$r['id']?>"><button class="btn delete">🗑 Excluir</button></form>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>


<section class="panel coord-section">
<div class="section-title"><div><p class="eyebrow">COORDENADAS</p><h2>📍 Cadastro de Coordenadas</h2><p class="muted">Cadastre os locais de CDS que aparecerão logo abaixo dos comandos na página pública.</p></div><span class="setup-badge">MASTER</span></div>

<div class="coord-import-box">
  <div>
    <p class="eyebrow">IMPORTAÇÃO EM LOTE</p>
    <h3>📄 Subir arquivo TXT</h3>
    <p class="muted">Cadastre dezenas ou centenas de coordenadas de uma vez. A ordem será preenchida automaticamente a partir do próximo número.</p>
  </div>
  <form method="post" enctype="multipart/form-data" class="coord-import-form">
    <input type="hidden" name="action" value="coord_bulk_import">
    <input type="file" name="coord_file" accept=".txt,text/plain" required>
    <button class="btn primary">📥 Importar TXT</button>
  </form>
  <div class="coord-format">
    <strong>📌 Padrão do arquivo TXT:</strong> uma coordenada por linha, usando <code>|</code> entre os campos:
    <pre># Categoria | Subcategoria | Ícone | Nome | CDS
Empresas | Tabacarias | 🚬 | Tabacaria Rhodes | 1325.8912 -1200.2056 82.538246
Fazendas | Fazendas | 🌾 | Fazenda Exemplo | 123.4567 -456.7890 78.123456
Casas | Casas | 🏠 | Casa Valentine | -123.4567 456.7890 80.123456
Grupos | Grupos | 👥 | Acampamento do Grupo | 100.0000 -200.0000 75.000000
Sobrenatural | Sobrenatural | 👻 | Castelo das Bruxas | 200.0000 -300.0000 90.000000</pre>
    <p class="muted">Linhas vazias e linhas começando com <code>#</code> são ignoradas. Se a mesma categoria + nome + CDS já existir, o sistema não duplica.</p>
    <a class="btn secondary" href="../exemplo_coordenadas.txt" download>📄 Baixar exemplo TXT</a>
  </div>
</div>
<section class="panel coord-category-manager" style="margin:18px 0">
<div class="section-title"><div><p class="eyebrow">ESTRUTURA</p><h2>🗂️ Categorias e Subcategorias</h2><p class="muted">Crie categorias e mantenha as subcategorias amarradas a cada uma. Novas subcategorias podem ser adicionadas ou editadas quando precisar.</p></div><span class="setup-badge">MASTER</span></div>
<div class="category-create-grid">
<form class="form" method="post">
<input type="hidden" name="action" value="category_create">
<label>Nova categoria</label><input name="category_name" maxlength="100" required placeholder="Ex.: Xerifado">
<label>Ícone</label><input name="category_icon" maxlength="20" value="📍" placeholder="🐎">
<div class="actions"><button class="btn primary">+ Criar categoria</button></div>
</form>
<div class="category-help"><strong>Como funciona</strong><p>1. Crie a categoria.<br>2. Adicione as subcategorias dentro dela.<br>3. Ao cadastrar coordenadas, a subcategoria disponível será filtrada pela categoria escolhida.<br>4. Se precisar de outra subcategoria no futuro, basta adicionar aqui.</p></div>
</div>
<div class="bulk-save-bar">
<div><strong>💾 Alterações em massa</strong><span class="muted">Edite nomes e ícones de todas as categorias e subcategorias e salve tudo de uma vez.</span></div>
<button type="button" class="btn primary" onclick="saveAllCategoryChanges()">💾 Salvar todas as alterações</button>
</div>
<form method="post" id="bulkCategoryForm" style="display:none"><input type="hidden" name="action" value="categories_bulk_save"><input type="hidden" name="bulk_payload" id="bulkCategoryPayload"></form>
<div class="category-manager-list">
<?php foreach($coordinateCategories as $cat): ?>
<div class="category-manager-card">
<div class="category-manager-head">
<div><div class="category-manager-title"><?=e($cat['icon'])?> <?=e($cat['name'])?> <span class="badge-status <?=$cat['active']?'':'off'?>"><?=$cat['active']?'ATIVA':'DESATIVADA'?></span></div><div class="muted">Subcategorias vinculadas</div></div>
<div class="command-actions">
<form method="post" class="inline-form"><input type="hidden" name="action" value="category_toggle"><input type="hidden" name="category_id" value="<?=$cat['id']?>"><button class="btn toggle"><?=$cat['active']?'⏸ Desativar':'▶ Ativar'?></button></form>
<form method="post" class="inline-form" onsubmit="return confirm('Excluir esta categoria? Só será possível se não houver coordenadas vinculadas.')"><input type="hidden" name="action" value="category_delete"><input type="hidden" name="category_id" value="<?=$cat['id']?>"><button class="btn delete">🗑 Excluir</button></form>
</div></div>
<div class="category-edit-row">
<form method="post" class="category-inline-form"><input type="hidden" name="action" value="category_edit"><input type="hidden" name="category_id" value="<?=$cat['id']?>"><input data-bulk-category-name name="category_name" value="<?=e($cat['name'])?>" maxlength="100" required><input data-bulk-category-icon name="category_icon" value="<?=e($cat['icon'])?>" maxlength="20"><button class="btn secondary">✏️ Salvar categoria</button></form>
</div>
<div class="subcategory-list">
<?php foreach(($subsByCategory[(int)$cat['id']] ?? []) as $sub): ?>
<div class="subcategory-item">
<form method="post" class="subcategory-inline-form"><input type="hidden" name="action" value="subcategory_edit"><input type="hidden" name="subcategory_id" value="<?=$sub['id']?>"><input data-bulk-subcategory-name name="subcategory_name" value="<?=e($sub['name'])?>" maxlength="100" required><input data-bulk-subcategory-icon class="emoji-input" name="subcategory_icon" value="<?=e($sub['icon'] ?? '📍')?>" maxlength="20" title="Digite um emoji. No Windows, use Win + . para abrir o painel de emojis." aria-label="Ícone da subcategoria" placeholder="📍"><button class="btn secondary">💾 Salvar</button></form>
<form method="post" class="inline-form" onsubmit="return confirm('Excluir esta subcategoria? Só será possível se não houver coordenadas vinculadas.')"><input type="hidden" name="action" value="subcategory_delete"><input type="hidden" name="subcategory_id" value="<?=$sub['id']?>"><button class="btn delete">🗑</button></form>
</div>
<?php endforeach; ?>
<form method="post" class="subcategory-add-form"><input type="hidden" name="action" value="subcategory_create"><input type="hidden" name="category_id" value="<?=$cat['id']?>"><input name="subcategory_name" maxlength="100" required placeholder="Nova subcategoria"><input class="emoji-input" name="subcategory_icon" value="📍" maxlength="20" title="Digite um emoji. No Windows, use Win + . para abrir o painel de emojis." aria-label="Ícone da nova subcategoria" placeholder="📍"><button class="btn primary">+ Subcategoria</button></form>
</div>
</div>
<?php endforeach; ?>
</div>
</section>

<div class="coord-grid">
<section>
<form class="form coord-form" method="post">
<input type="hidden" name="action" value="coord_create">
<label>Categoria</label>
<select name="category" id="coord_create_category" required onchange="syncCoordinateSubcategories('coord_create_category','coord_create_subcategory')"><option value="">Selecione</option><?php foreach($coordinateCategories as $cat): if(!$cat['active']) continue; ?><option value="<?=e($cat['name'])?>"><?=e($cat['icon'])?> <?=e($cat['name'])?></option><?php endforeach; ?></select>
<label>Subcategoria</label><select name="subcategory" id="coord_create_subcategory" required><option value="">Selecione uma categoria primeiro</option></select>
<label>Emoticon / Ícone</label><input name="icon" maxlength="20" value="📍" placeholder="🏢">
<label>Nome do local</label><input name="coord_name" maxlength="180" required placeholder="Tabacaria Rhodes">
<label>CDS</label><input name="cds" maxlength="255" required placeholder="1325.8912 -1200.2056 82.538246">
<label>Ordem</label><input type="number" name="coord_position" min="0" value="0">
<div class="actions"><button class="btn primary">+ Cadastrar local</button></div>
</form>
</section>
<section>
<div class="coord-list">
<?php if(!$coordinateRows): ?><div class="empty">Nenhum local cadastrado.</div><?php else: ?>
<?php foreach($coordinateRows as $c): ?>
<div class="coord-row <?=$c['active']?'':'inactive'?>">
<div class="coord-head"><div><div class="coord-name"><span class="coord-icon"><?=e($c['icon'])?></span><?=e($c['name'])?></div><div class="coord-meta"><?=e($c['category'])?> · <?=e($c['subcategory'])?> · Ordem <?=e($c['position'])?> · <?=$c['active']?'Ativo':'Desativado'?></div></div></div>
<div class="coord-cds"><?=e($c['cds'])?></div>
<div class="coord-actions">
<button type="button" class="btn secondary" onclick='openCoordEdit(<?=json_encode(['id'=>(int)$c['id'],'category'=>$c['category'],'subcategory'=>$c['subcategory'],'icon'=>$c['icon'],'name'=>$c['name'],'cds'=>$c['cds'],'position'=>(int)$c['position']],JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP)?>'>✏️ Editar</button>
<form method="post"><input type="hidden" name="action" value="coord_toggle"><input type="hidden" name="coord_id" value="<?=$c['id']?>"><button class="btn toggle"><?=$c['active']?'⏸ Desativar':'▶ Ativar'?></button></form>
<form method="post" onsubmit="return confirm('Excluir este local permanentemente?')"><input type="hidden" name="action" value="coord_delete"><input type="hidden" name="coord_id" value="<?=$c['id']?>"><button class="btn delete">🗑 Excluir</button></form>
</div>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>
</section>
</div>
</section>

<div id="coordEditModal" class="modal-backdrop" style="display:none">
<div class="modal-card">
<div class="section-title"><div><p class="eyebrow">EDITAR</p><h2>Editar coordenada</h2></div><button type="button" class="modal-close" onclick="closeCoordEdit()">×</button></div>
<form class="form" method="post"><input type="hidden" name="action" value="coord_edit"><input type="hidden" name="coord_id" id="coord_id">
<label>Categoria</label><select name="category" id="coord_category" required onchange="syncCoordinateSubcategories('coord_category','coord_subcategory')"><?php foreach($coordinateCategories as $cat): if(!$cat['active']) continue; ?><option value="<?=e($cat['name'])?>"><?=e($cat['icon'])?> <?=e($cat['name'])?></option><?php endforeach; ?></select>
<label>Subcategoria</label><select name="subcategory" id="coord_subcategory" required></select>
<label>Emoticon / Ícone</label><input name="icon" id="coord_icon" maxlength="20">
<label>Nome do local</label><input name="coord_name" id="coord_name" maxlength="180" required>
<label>CDS</label><input name="cds" id="coord_cds" maxlength="255" required>
<label>Ordem</label><input type="number" name="coord_position" id="coord_position" min="0">
<div class="actions"><button class="btn primary">💾 Salvar alterações</button><button type="button" class="btn secondary" onclick="closeCoordEdit()">Cancelar</button></div>
</form></div></div>

<div id="editModal" class="modal-backdrop" style="display:none">
<div class="modal-card">
<div class="section-title"><div><p class="eyebrow">EDITAR</p><h2>Editar comando</h2></div><button type="button" class="modal-close" onclick="closeEdit()">×</button></div>
<form class="form command-form" method="post"><input type="hidden" name="action" value="edit"><input type="hidden" name="id" id="edit_id">
<label>Comando</label><input name="command" id="edit_command" required maxlength="100">
<label>Descrição</label><textarea name="description" id="edit_description" required maxlength="500"></textarea>
<label>Ordem</label><input type="number" name="position" id="edit_position" min="1">
<small class="muted">A ordem atual é mantida. Troque o número somente se quiser mover este comando.</small>
<div class="actions"><button class="btn primary">💾 Salvar alterações</button><button type="button" class="btn secondary" onclick="closeEdit()">Cancelar</button></div>
</form>
</div></div>
</main>
<footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
<script>
function saveAllCategoryChanges(){
    const cards = Array.from(document.querySelectorAll('.category-manager-card'));
    const categories = [];
    for (const card of cards) {
        const categoryForm = card.querySelector('.category-edit-row form');
        if (!categoryForm) continue;
        const id = Number(categoryForm.querySelector('input[name="category_id"]')?.value || 0);
        const name = categoryForm.querySelector('[data-bulk-category-name]')?.value.trim() || '';
        const icon = categoryForm.querySelector('[data-bulk-category-icon]')?.value.trim() || '📍';
        const subs = [];
        card.querySelectorAll('.subcategory-item').forEach(item => {
            const form = item.querySelector('.subcategory-inline-form');
            if (!form) return;
            subs.push({
                id: Number(form.querySelector('input[name="subcategory_id"]')?.value || 0),
                name: form.querySelector('[data-bulk-subcategory-name]')?.value.trim() || '',
                icon: form.querySelector('[data-bulk-subcategory-icon]')?.value.trim() || '📍'
            });
        });
        categories.push({id, name, icon, subs});
    }
    if (!categories.length) { alert('Nenhuma categoria encontrada para salvar.'); return; }
    if (!confirm('Salvar todas as alterações de categorias e subcategorias de uma vez?')) return;
    document.getElementById('bulkCategoryPayload').value = JSON.stringify({categories});
    document.getElementById('bulkCategoryForm').submit();
}

function openEdit(d){document.getElementById('edit_id').value=d.id;document.getElementById('edit_command').value=d.command;document.getElementById('edit_description').value=d.description;document.getElementById('edit_position').value=d.position||0;document.getElementById('editModal').style.display='flex';}
function closeEdit(){document.getElementById('editModal').style.display='none';}
const coordinateSubcategoriesByCategory = <?=json_encode(array_reduce($coordinateCategories, function($carry,$cat) use ($subsByCategory){$carry[$cat['name']] = array_map(function($s){return $s['name'];}, $subsByCategory[(int)$cat['id']] ?? []); return $carry;}, []), JSON_UNESCAPED_UNICODE|JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT)?>;
function syncCoordinateSubcategories(categoryId, subId, selected=''){const cat=document.getElementById(categoryId)?.value;const sel=document.getElementById(subId);if(!sel)return;const values=coordinateSubcategoriesByCategory[cat]||[];sel.innerHTML='<option value="">Selecione</option>'+values.map(v=>'<option value="'+String(v).replace(/"/g,'&quot;')+'">'+String(v).replace(/</g,'&lt;')+'</option>').join('');if(selected&&values.includes(selected))sel.value=selected;}
function openCoordEdit(d){document.getElementById('coord_id').value=d.id;document.getElementById('coord_category').value=d.category;syncCoordinateSubcategories('coord_category','coord_subcategory',d.subcategory||'Geral');document.getElementById('coord_icon').value=d.icon;document.getElementById('coord_name').value=d.name;document.getElementById('coord_cds').value=d.cds;document.getElementById('coord_position').value=d.position||0;document.getElementById('coordEditModal').style.display='flex';}
function closeCoordEdit(){document.getElementById('coordEditModal').style.display='none';}
syncCoordinateSubcategories('coord_create_category','coord_create_subcategory');
</script>
</body></html>
