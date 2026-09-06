<?php
require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS pricing_categories (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,name VARCHAR(120) NOT NULL UNIQUE,position INT NOT NULL DEFAULT 0,active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_pricing_categories_position(active,position,name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS pricing_products (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,category_id INT UNSIGNED NOT NULL,photo VARCHAR(500) NULL,item_name VARCHAR(180) NOT NULL,price_min DECIMAL(12,2) NOT NULL DEFAULT 0.00,price_max DECIMAL(12,2) NOT NULL DEFAULT 0.00,position INT NOT NULL DEFAULT 0,active TINYINT(1) NOT NULL DEFAULT 1,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,INDEX idx_pricing_products_category(category_id,active,position,item_name),FOREIGN KEY(category_id) REFERENCES pricing_categories(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $cats=$pdo->query("SELECT id,name FROM pricing_categories WHERE active=1 ORDER BY position ASC,name ASC")->fetchAll();
    $products=$pdo->query("SELECT p.id,p.category_id,p.photo,p.item_name,p.price_min,p.price_max FROM pricing_products p INNER JOIN pricing_categories c ON c.id=p.category_id WHERE p.active=1 AND c.active=1 ORDER BY c.position ASC,c.name ASC,p.position ASC,p.item_name ASC")->fetchAll();
    echo json_encode(['ok'=>true,'categories'=>$cats,'products'=>$products],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
} catch(Throwable $e){ http_response_code(500); echo json_encode(['ok'=>false,'error'=>'Não foi possível carregar a precificação.']); }
