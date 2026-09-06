CREATE TABLE IF NOT EXISTS live_monitor_settings(
`key` VARCHAR(100) PRIMARY KEY,
`value` TEXT NOT NULL);

CREATE TABLE IF NOT EXISTS live_monitor_titles(
id INT AUTO_INCREMENT PRIMARY KEY,
title VARCHAR(255) NOT NULL,
active TINYINT(1) DEFAULT 1,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);

INSERT INTO live_monitor_titles(title)
SELECT '[ Carmesim RP ]'
WHERE NOT EXISTS(SELECT 1 FROM live_monitor_titles WHERE title='[ Carmesim RP ]');

CREATE TABLE IF NOT EXISTS live_monitor_logs(
id BIGINT AUTO_INCREMENT PRIMARY KEY,
streamer_id INT NULL,
platform VARCHAR(20),
searched_at DATETIME,
live_found TINYINT(1),
live_title VARCHAR(255),
result VARCHAR(100),
details TEXT);
