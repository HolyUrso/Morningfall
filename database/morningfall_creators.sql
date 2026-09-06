CREATE DATABASE IF NOT EXISTS morningfall_creators CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE morningfall_creators;

CREATE TABLE staff_users (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 username VARCHAR(80) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 role ENUM('master','staff') NOT NULL DEFAULT 'staff',
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE streamers (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(120) NOT NULL,
 discord VARCHAR(120),
 platform VARCHAR(50),
 channel_url VARCHAR(500),
 category ENUM('novato','oficial','afiliado') NOT NULL DEFAULT 'novato',
 access_code VARCHAR(100) NOT NULL UNIQUE,
 points INT NOT NULL DEFAULT 0,
 active TINYINT(1) NOT NULL DEFAULT 1,
 joined_at DATE,
 notes TEXT,
 webhook_url VARCHAR(500) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_category(category),
 INDEX idx_active(active)
);

CREATE TABLE vods (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 streamer_id INT UNSIGNED NOT NULL,
 url VARCHAR(700) NOT NULL,
 vod_date DATE NOT NULL,
 duration_minutes INT UNSIGNED NOT NULL DEFAULT 0,
 points_awarded INT NOT NULL DEFAULT 0,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(streamer_id) REFERENCES streamers(id) ON DELETE CASCADE,
 INDEX idx_vod_streamer(streamer_id,vod_date)
);

CREATE TABLE point_transactions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 streamer_id INT UNSIGNED NOT NULL,
 type ENUM('earn','penalty','adjustment','redemption_refund') NOT NULL,
 description VARCHAR(255) NOT NULL,
 points INT NOT NULL,
 reference_type VARCHAR(50),
 reference_id BIGINT UNSIGNED,
 created_by INT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(streamer_id) REFERENCES streamers(id) ON DELETE CASCADE,
 FOREIGN KEY(created_by) REFERENCES staff_users(id) ON DELETE SET NULL
);

CREATE TABLE activities (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 streamer_id INT UNSIGNED NOT NULL,
 type ENUM('live_bonus','collab','raid','event','video','viral_video','server_promo','referral','monthly_bonus','other') NOT NULL,
 description VARCHAR(255) NOT NULL,
 points INT NOT NULL DEFAULT 0,
 activity_date DATE NOT NULL,
 evidence_url VARCHAR(700),
 created_by INT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(streamer_id) REFERENCES streamers(id) ON DELETE CASCADE,
 FOREIGN KEY(created_by) REFERENCES staff_users(id) ON DELETE SET NULL
);

CREATE TABLE penalties (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 streamer_id INT UNSIGNED NOT NULL,
 type VARCHAR(100) NOT NULL,
 description VARCHAR(255) NOT NULL,
 points INT NOT NULL DEFAULT 0,
 penalty_date DATE NOT NULL,
 created_by INT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY(streamer_id) REFERENCES streamers(id) ON DELETE CASCADE,
 FOREIGN KEY(created_by) REFERENCES staff_users(id) ON DELETE SET NULL
);

CREATE TABLE prizes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 name VARCHAR(180) NOT NULL,
 description TEXT,
 points_novato INT UNSIGNED NOT NULL DEFAULT 0,
 points_oficial INT UNSIGNED NOT NULL DEFAULT 0,
 points_afiliado INT UNSIGNED NOT NULL DEFAULT 0,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE redemptions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 streamer_id INT UNSIGNED NOT NULL,
 prize_id INT UNSIGNED NOT NULL,
 streamer_category ENUM('novato','oficial','afiliado') NOT NULL,
 points_spent INT UNSIGNED NOT NULL,
 status ENUM('pending','approved','delivered','cancelled') NOT NULL DEFAULT 'pending',
 approved_by INT UNSIGNED NULL,
 delivered_by INT UNSIGNED NULL,
 notes TEXT,
 webhook_url VARCHAR(500) NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY(streamer_id) REFERENCES streamers(id) ON DELETE RESTRICT,
 FOREIGN KEY(prize_id) REFERENCES prizes(id) ON DELETE RESTRICT,
 FOREIGN KEY(approved_by) REFERENCES staff_users(id) ON DELETE SET NULL,
 FOREIGN KEY(delivered_by) REFERENCES staff_users(id) ON DELETE SET NULL
);


CREATE TABLE IF NOT EXISTS content_submissions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 streamer_id INT UNSIGNED NOT NULL,
 content_type VARCHAR(40) NOT NULL,
 url VARCHAR(700) NOT NULL,
 submission_date DATE NOT NULL,
 collab TINYINT(1) NOT NULL DEFAULT 0,
 collab_streamer_id INT UNSIGNED NULL,
 status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
 vod_id BIGINT UNSIGNED NULL,
 duration_minutes INT UNSIGNED NULL,
 points_awarded INT NOT NULL DEFAULT 0,
 staff_notes TEXT NULL,
 reviewed_by INT UNSIGNED NULL,
 reviewed_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_submission_streamer_status(streamer_id,status),
 INDEX idx_submission_date(streamer_id,submission_date),
 FOREIGN KEY(streamer_id) REFERENCES streamers(id) ON DELETE CASCADE,
 FOREIGN KEY(collab_streamer_id) REFERENCES streamers(id) ON DELETE SET NULL,
 FOREIGN KEY(reviewed_by) REFERENCES staff_users(id) ON DELETE SET NULL,
 FOREIGN KEY(vod_id) REFERENCES vods(id) ON DELETE SET NULL
);

CREATE TABLE settings (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 setting_key VARCHAR(100) NOT NULL UNIQUE,
 setting_value TEXT NOT NULL,
 description VARCHAR(255),
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

INSERT INTO settings(setting_key,setting_value,description) VALUES
('streamer_logo','','Logo da Área do Streamer'),
('streamer_background','assets/img/streamer-background-default.png','Background da Área do Streamer'),
('live_points_per_hour','1','Pontos por hora completa'),
('live_max_points','10','Máximo de pontos por dia para VODs/Lives'),
('official_weekly_lives','3','Lives mínimas Oficial'),
('official_min_live_hours','2','Horas mínimas por live Oficial'),
('official_weekly_short_content','1','Conteúdos curtos Oficial'),
('affiliate_weekly_lives','3','Lives mínimas Afiliado'),
('affiliate_min_live_hours','3','Horas mínimas por live Afiliado'),
('affiliate_weekly_short_content','2','Conteúdos curtos Afiliado'),
('collab_raid_points','3','Pontos por Collab/Raid com outro streamer do Condado'),
('social_common_points','3','Pontos por Link Comum'),
('social_humor_points','20','Pontos por Vídeo Humorístico'),
('social_news_points','10','Pontos por Vídeo de Novidades'),
('weekly_social_points_limit','30','Limite semanal de pontos por conteúdos de redes sociais'),
('weekly_live_days_target','3','Dias distintos com live aprovados na semana para atingir a meta'),
('weekly_live_days_bonus','20','Bônus por atingir a meta semanal de dias com live'),
('monthly_live_days_target','20','Dias distintos com live aprovados no mês para atingir a meta'),
('monthly_live_days_bonus','50','Bônus por atingir a meta mensal de dias com live'),
('proof_retention_days','45','Quantidade de dias para manter os comprovantes de VOD antes da exclusão automática'),
('redemption_cooldown_days','7','Quantidade de dias entre resgates aprovados do mesmo streamer'),
('weekly_video_points_limit','30','Limite semanal de vídeos'),
('weekly_viral_video_points_limit','60','Limite semanal de viralizados');
-- Marcador de migração: em bancos existentes, o sistema cria este registro automaticamente após recalcular as VODs.

INSERT INTO prizes(name,points_novato,points_oficial,points_afiliado) VALUES
('Caixa de Armas Mediana',30,27,25),
('Caixa de Armas Top',90,80,70),
('Cirurgia',45,40,35),
('Altura Personalizada',50,45,40),
('2º Emprego ou Slot Extra',230,200,170),
('PED - Até 6mb',250,230,200),
('Cavalo - Até $5.000',145,125,100),
('Cavalo - Até $15.000',195,170,150),
('Personalização de Arma',140,120,100),
('Carroça - 500 KG',100,80,70),
('Carroça - 3.000 KG',150,130,100),
('Renovação VIP Platina',175,150,130),
('Renovação VIP Diamante',300,275,250),
('Vitrola',130,110,90),
('Resgate - Cupom R$ 250',300,275,250),
('Barco a escolha',200,175,150),
('Balão',300,275,250),
('R$2.000',120,110,100),
('R$5.000',260,250,240);

-- Comandos administrativos da Staff
CREATE TABLE IF NOT EXISTS admin_commands (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 command VARCHAR(100) NOT NULL UNIQUE,
 description VARCHAR(500) NOT NULL,
 position INT NOT NULL DEFAULT 0,
 active TINYINT(1) NOT NULL DEFAULT 1,
 created_by INT UNSIGNED NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_admin_commands_created_by FOREIGN KEY(created_by) REFERENCES staff_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Coordenadas da Staff / locais de CDS
CREATE TABLE IF NOT EXISTS staff_coordinates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category ENUM('Empresas','Fazendas','Casas','Grupos','Sobrenatural') NOT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
