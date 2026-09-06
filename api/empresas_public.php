<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
try{
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
    $colLinkUrl=$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='site_companies' AND COLUMN_NAME='link_url'")->fetchColumn();
    if(!(int)$colLinkUrl){ $pdo->exec("ALTER TABLE site_companies ADD COLUMN link_url VARCHAR(1000) NULL AFTER description"); }
    $colLinkText=$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='site_companies' AND COLUMN_NAME='link_text'")->fetchColumn();
    if(!(int)$colLinkText){ $pdo->exec("ALTER TABLE site_companies ADD COLUMN link_text VARCHAR(160) NULL AFTER link_url"); }
    $rows=$pdo->query("SELECT id,photo,category,name,description,link_url,link_text,available,position FROM site_companies WHERE active=1 ORDER BY position ASC,name ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as &$r){
        $r['id']=(int)$r['id'];
        $r['available']=(int)$r['available'];
        $r['photo']=$r['photo'] ? '/assets/images/empresas/'.ltrim($r['photo'],'/') : null;
    }
    echo json_encode(['ok'=>true,'companies'=>$rows],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'Não foi possível carregar as empresas.'],JSON_UNESCAPED_UNICODE);
}
