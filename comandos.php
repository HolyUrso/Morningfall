<?php
require_once __DIR__ . '/config/database.php';

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
try { $pdo->exec("ALTER TABLE staff_coordinates MODIFY category VARCHAR(100) NOT NULL"); } catch (Throwable $e) {}
try { $pdo->exec("ALTER TABLE staff_coordinates ADD COLUMN subcategory VARCHAR(100) NOT NULL DEFAULT 'Geral' AFTER category"); } catch (Throwable $e) {}
try { $pdo->exec("CREATE INDEX idx_staff_coordinates_subcategory ON staff_coordinates(subcategory)"); } catch (Throwable $e) {}

$pdo->exec("CREATE TABLE IF NOT EXISTS staff_coordinate_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE, icon VARCHAR(20) NOT NULL DEFAULT '📍',
    active TINYINT(1) NOT NULL DEFAULT 1, created_by INT UNSIGNED NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_staff_coord_categories_created_by FOREIGN KEY(created_by) REFERENCES staff_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("CREATE TABLE IF NOT EXISTS staff_coordinate_subcategories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, category_id INT UNSIGNED NOT NULL, name VARCHAR(100) NOT NULL, active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_coord_subcategory(category_id,name),
    CONSTRAINT fk_staff_coord_subcategories_category FOREIGN KEY(category_id) REFERENCES staff_coordinate_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$seedCategories = [
 ['Empresas','🏢',['Saloons','Ferrarias','Tabacarias','Artesanatos','Armarias','Ateliês','Perfumarias','Jornais','Geral']],
 ['Fazendas','🌾',['Geral']],['Casas','🏠',['Geral']],['Grupos','👥',['Geral']],['Sobrenatural','👻',['Geral']],['Xerifado','⭐',['Cavalaria','Geral']]
];
$hasAnyCategories = (int)$pdo->query('SELECT COUNT(*) FROM staff_coordinate_categories')->fetchColumn() > 0;
if (!$hasAnyCategories) {
    $ci=$pdo->prepare('INSERT IGNORE INTO staff_coordinate_categories(name,icon,active) VALUES(?,?,1)');
    $si=$pdo->prepare('INSERT IGNORE INTO staff_coordinate_subcategories(category_id,name,active) VALUES(?,?,1)');
    foreach($seedCategories as $sc){
        $ci->execute([$sc[0],$sc[1]]);
        $q=$pdo->prepare('SELECT id FROM staff_coordinate_categories WHERE name=?');
        $q->execute([$sc[0]]);
        $cid=(int)$q->fetchColumn();
        foreach($sc[2] as $sn)$si->execute([$cid,$sn]);
    }
}
$pdo->exec("UPDATE staff_coordinates SET subcategory = CASE WHEN LOWER(name) LIKE '%saloon%' THEN 'Saloons' WHEN LOWER(name) LIKE '%ferraria%' THEN 'Ferrarias' WHEN LOWER(name) LIKE '%tabacaria%' THEN 'Tabacarias' WHEN LOWER(name) LIKE '%artesanato%' THEN 'Artesanatos' WHEN LOWER(name) LIKE '%armaria%' THEN 'Armarias' WHEN LOWER(name) LIKE '%ateliê%' OR LOWER(name) LIKE '%atelie%' THEN 'Ateliês' WHEN LOWER(name) LIKE '%perfumaria%' THEN 'Perfumarias' WHEN LOWER(name) LIKE '%jornal%' THEN 'Jornais' ELSE subcategory END WHERE subcategory='Geral'");
$pdo->exec("CREATE TABLE IF NOT EXISTS staff_commands_settings (id TINYINT UNSIGNED PRIMARY KEY, top_name VARCHAR(100) NOT NULL DEFAULT 'CARMESIM STAFF', eyebrow_name VARCHAR(150) NOT NULL DEFAULT 'CARMESIM ROLEPLAY · STAFF', updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$pdo->exec("INSERT IGNORE INTO staff_commands_settings(id,top_name,eyebrow_name) VALUES(1,'CARMESIM STAFF','CARMESIM ROLEPLAY · STAFF')");

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
    $ins = $pdo->prepare('INSERT IGNORE INTO admin_commands(command,description,position,active,created_by) VALUES(?,?,?,?,NULL)');
    foreach ($seed as $item) $ins->execute([$item[0],$item[1],$item[2],1]);
}

$rows = $pdo->query("SELECT command,description FROM admin_commands WHERE active=1 ORDER BY position ASC, command ASC")->fetchAll(PDO::FETCH_ASSOC);
$coordinateRows = $pdo->query("SELECT id,category,subcategory,icon,name,cds FROM staff_coordinates WHERE active=1 ORDER BY category ASC, position ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
$publicCategories = $pdo->query("SELECT id,name,icon FROM staff_coordinate_categories WHERE active=1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
$brandSettings = $pdo->query('SELECT top_name, eyebrow_name FROM staff_commands_settings WHERE id=1')->fetch(PDO::FETCH_ASSOC) ?: ['top_name'=>'CARMESIM STAFF','eyebrow_name'=>'CARMESIM ROLEPLAY · STAFF'];
function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Comandos Staff · <?=e($brandSettings['top_name'])?></title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
:root{--steam-bg:#171d25;--steam-panel:#1b2838;--steam-border:#2a475e;--steam-blue:#66c0f4;--steam-blue2:#1a9fff;--text:#d6d7d8;--muted:#8f98a5}
body{background:#101820!important;color:var(--text);font-family:Poppins,Arial,sans-serif}.background{opacity:.22}.topbar{background:linear-gradient(180deg,#171d25 0%,#121a23 100%)!important;border-bottom:1px solid #2a475e!important;box-shadow:0 2px 12px rgba(0,0,0,.35)}.topbar>div:first-child{color:#fff!important}.topbar>div:first-child span{color:#66c0f4!important}.top-user a{color:#66c0f4!important}
.commands-public{max-width:1120px;margin:0 auto;padding:42px 24px 70px}.commands-head{padding:28px 32px 24px;background:linear-gradient(135deg,rgba(27,40,56,.98),rgba(20,30,42,.94));border:1px solid var(--steam-border);box-shadow:0 12px 35px rgba(0,0,0,.3);margin-bottom:18px}.commands-head .eyebrow{color:#66c0f4;letter-spacing:2px;font-size:11px;margin-bottom:8px}.commands-head h1{font-size:32px;margin:0 0 7px;color:#fff}.commands-head>p:last-child{color:#a7b3c2;margin:0;line-height:1.6}.staff-warning{display:flex;gap:13px;align-items:flex-start;background:linear-gradient(90deg,rgba(227,174,55,.12),rgba(27,40,56,.95));border:1px solid rgba(227,174,55,.42);border-left:4px solid #e7b64a;padding:15px 18px;margin-bottom:18px;color:#c9d1da;line-height:1.55;font-size:13px}.staff-warning strong{color:#f0c75e}.staff-warning .warn-icon{font-size:20px;line-height:1}.hub-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.hub-card{background:linear-gradient(135deg,#1b2838,#172333);border:1px solid #2a475e;border-radius:3px;padding:24px;box-shadow:0 14px 40px rgba(0,0,0,.28);transition:.16s}.hub-card:hover{border-color:#66c0f4;transform:translateY(-2px)}.hub-icon{font-size:30px}.hub-card h2{color:#fff;margin:8px 0 7px;font-size:21px}.hub-card p{color:#aeb8c4;line-height:1.55;font-size:13px;min-height:42px}.hub-meta{color:#7d8b99;font-size:11px;margin:12px 0 17px}.hub-button{display:inline-flex;align-items:center;justify-content:center;padding:10px 16px;background:linear-gradient(90deg,#1a9fff,#66c0f4);color:#06263a;text-decoration:none;border-radius:2px;font-weight:800;font-size:12px}.hub-button:hover{filter:brightness(1.08)}.site-footer{background:#101820!important;border-top:1px solid #2a475e!important;color:#7d8b99!important;text-align:center;padding:18px 20px!important;font-size:12px}.site-footer a{color:#66c0f4;text-decoration:none;font-weight:700}.site-footer a:hover{color:#fff}.site-footer small{display:block;margin-top:6px;color:#657383;font-size:10px}@media(max-width:700px){.hub-grid{grid-template-columns:1fr}.commands-public{padding:20px 12px 40px}.commands-head{padding:22px 20px}.commands-head h1{font-size:26px}}
</style></head><body><div class="background"></div><header class="topbar"><div><b><?=e(strtok($brandSettings['top_name'], ' '))?></b> <span><?=e(trim(substr($brandSettings['top_name'], strlen(strtok($brandSettings['top_name'], ' ')))))?></span></div><div class="top-user"><a href="index.php">Início</a></div></header>
<main class="commands-public"><div class="commands-head"><p class="eyebrow"><?=e($brandSettings['eyebrow_name'])?></p><h1>⚡ Comandos Staff</h1><p>Acesse separadamente os assuntos administrativos da Staff, sem deixar a página poluída.</p></div>
<div class="staff-warning"><span class="warn-icon">⚠️</span><div><strong>Atenção:</strong> alguns comandos possuem acesso restrito e somente podem ser utilizados por determinados níveis de permissão da Staff. Em caso de dúvida, entre em contato com um <strong>Moderador ou superior</strong>.</div></div>
<div class="hub-grid"><article class="hub-card"><div class="hub-icon">⚡</div><h2>Comandos da Staff</h2><p>Consulte os comandos administrativos, suas descrições e copie o comando com um clique.</p><div class="hub-meta">Lista pública de comandos</div><a class="hub-button" href="comandos_lista.php">📋 Mostrar lista</a></article><article class="hub-card"><div class="hub-icon">👥</div><h2>Comandos Básicos de Players</h2><p>Consulte comandos utilizados pelos jogadores. A descrição informa para qual profissão ou função cada comando serve.</p><div class="hub-meta">Cavalaria, Médicos e outros</div><a class="hub-button" href="comandos_players.php">👥 Mostrar lista</a></article><article class="hub-card"><div class="hub-icon">📍</div><h2>Coordenadas da Staff</h2><p>Pesquise locais por categoria e subcategoria e copie o CDS rapidamente.</p><div class="hub-meta">Empresas, Xerifado, Fazendas e outros</div><a class="hub-button" href="coordenadas.php">📍 Mostrar lista</a></article></div></main>
<footer class="site-footer"><div>Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/618837942877552670" target="_blank" rel="noopener noreferrer">💬 luciferms666</a></div><small>Clique para entrar em contato no Discord</small></footer></body></html>