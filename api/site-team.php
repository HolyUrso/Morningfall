<?php
require_once '../config/database.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_team_members (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(120) NOT NULL,
        role_title VARCHAR(120) NOT NULL,
        description VARCHAR(500) NULL,
        discord_id VARCHAR(32) NULL,
        discord_avatar VARCHAR(700) NULL,
        member_type ENUM('leadership','collaborator') NOT NULL DEFAULT 'collaborator',
        position INT NOT NULL DEFAULT 0,
        active TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_site_team_type_position(member_type, position, active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Garante os três cargos principais mesmo em uma instalação nova.
    $count=(int)$pdo->query("SELECT COUNT(*) FROM site_team_members WHERE member_type='leadership'")->fetchColumn();
    if($count===0){
        $seed=$pdo->prepare("INSERT INTO site_team_members(name,role_title,description,member_type,position,active) VALUES(?,?,?,?,?,1)");
        $seed->execute(['Jessy','CEO','Idealizadora do Condado Carmesim','leadership',1]);
        $seed->execute(['Luci','CEO','Idealizador do Condado Carmesim','leadership',2]);
        $seed->execute(['Matheus','COO','Parte da liderança e construção do projeto','leadership',3]);
    }

    $lead=$pdo->query("SELECT id,name,role_title,description,discord_id,discord_avatar,position FROM site_team_members WHERE member_type='leadership' AND active=1 ORDER BY position ASC,id ASC LIMIT 3")->fetchAll();
    $collab=$pdo->query("SELECT id,name,role_title,description,discord_avatar,position FROM site_team_members WHERE member_type='collaborator' AND active=1 ORDER BY position ASC,id ASC")->fetchAll();
    echo json_encode(['success'=>true,'leadership'=>$lead,'collaborators'=>$collab],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
}catch(Throwable $e){
    http_response_code(500);
    echo json_encode(['success'=>false,'error'=>'Não foi possível carregar a equipe.']);
}
