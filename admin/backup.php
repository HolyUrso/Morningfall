<?php
require_once '../config/auth.php';
require_once '../config/database.php';

// Backup: acesso exclusivo ao Master.
if (($_SESSION['staff_role'] ?? '') !== 'master') {
    try {
        $roleQ = $pdo->prepare("SELECT role FROM staff_users WHERE id=? AND active=1 LIMIT 1");
        $roleQ->execute([(int)($_SESSION['staff_id'] ?? 0)]);
        if ($roleQ->fetchColumn() === 'master') {
            $_SESSION['staff_role'] = 'master';
        }
    } catch (Throwable $e) {}
}

if (($_SESSION['staff_role'] ?? '') !== 'master') {
    http_response_code(403);
    exit('Acesso restrito ao Staff Master.');
}

$backupDir = dirname(__DIR__) . '/storage/backups';
if (!is_dir($backupDir)) {
    @mkdir($backupDir, 0755, true);
}
$backupDirReady = is_dir($backupDir) && is_writable($backupDir);

function e($v) {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function backupFiles($dir, $zip, $basePath = '') {
    if (!is_dir($dir)) return 0;
    $count = 0;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..' || $item === '.htaccess') continue;
        $full = $dir . DIRECTORY_SEPARATOR . $item;
        $relative = ltrim($basePath . '/' . $item, '/');
        if (is_dir($full)) {
            $zip->addEmptyDir($relative);
            $count += backupFiles($full, $zip, $relative);
        } elseif (is_file($full)) {
            if ($zip->addFile($full, $relative)) $count++;
        }
    }
    return $count;
}

function sqlValue(PDO $pdo, $value, $type = '') {
    if ($value === null) return 'NULL';
    $binaryTypes = ['blob','tinyblob','mediumblob','longblob','binary','varbinary'];
    if (in_array(strtolower($type), $binaryTypes, true)) {
        return "X'" . bin2hex($value) . "'";
    }
    return $pdo->quote((string)$value);
}

function generateSqlDump(PDO $pdo, $file) {
    $fh = @fopen($file, 'wb');
    if (!$fh) {
        throw new RuntimeException('Não foi possível criar o arquivo SQL temporário na pasta de backups. Verifique a permissão de escrita do servidor.');
    }

    fwrite($fh, "-- Morningfall Creators - Backup do banco\n");
    fwrite($fh, "-- Gerado em: " . date('Y-m-d H:i:s') . "\n\n");
    fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");
    fwrite($fh, "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

    $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as $row) {
        $table = $row[0];
        $safeTable = str_replace('`', '``', $table);
        $create = $pdo->query("SHOW CREATE TABLE `{$safeTable}`")->fetch(PDO::FETCH_NUM);
        if (!$create) continue;

        fwrite($fh, "DROP TABLE IF EXISTS `{$safeTable}`;\n");
        fwrite($fh, $create[1] . ";\n\n");

        $columns = $pdo->query("SHOW COLUMNS FROM `{$safeTable}`")->fetchAll();
        $types = [];
        $columnNames = [];
        foreach ($columns as $col) {
            $columnNames[] = '`' . str_replace('`', '``', $col['Field']) . '`';
            $types[$col['Field']] = strtolower((string)$col['Type']);
        }

        $select = $pdo->query("SELECT * FROM `{$safeTable}`");
        $batch = [];
        while ($record = $select->fetch(PDO::FETCH_ASSOC)) {
            $vals = [];
            foreach ($columns as $col) {
                $field = $col['Field'];
                $vals[] = sqlValue($pdo, $record[$field], $types[$field] ?? '');
            }
            $batch[] = '(' . implode(',', $vals) . ')';
            if (count($batch) >= 100) {
                fwrite($fh, "INSERT INTO `{$safeTable}` (" . implode(',', $columnNames) . ") VALUES\n" . implode(",\n", $batch) . ";\n");
                $batch = [];
            }
        }
        if ($batch) {
            fwrite($fh, "INSERT INTO `{$safeTable}` (" . implode(',', $columnNames) . ") VALUES\n" . implode(",\n", $batch) . ";\n");
        }
        fwrite($fh, "\n");
    }

    fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);
}


function splitSqlStatements($sql) {
    $statements = [];
    $len = strlen($sql);
    $buf = '';
    $inSingle = false;
    $inDouble = false;
    $inBacktick = false;
    $inLineComment = false;
    $inBlockComment = false;
    $escape = false;

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];
        $next = ($i + 1 < $len) ? $sql[$i + 1] : '';

        if ($inLineComment) {
            $buf .= $ch;
            if ($ch === "\n") $inLineComment = false;
            continue;
        }
        if ($inBlockComment) {
            $buf .= $ch;
            if ($ch === '*' && $next === '/') {
                $buf .= $next;
                $i++;
                $inBlockComment = false;
            }
            continue;
        }
        if (!$inSingle && !$inDouble && !$inBacktick) {
            if ($ch === '-' && $next === '-' && ($i + 2 >= $len || preg_match('/\s/', $sql[$i + 2]))) {
                $buf .= $ch . $next;
                $i++;
                $inLineComment = true;
                continue;
            }
            if ($ch === '#') {
                $buf .= $ch;
                $inLineComment = true;
                continue;
            }
            if ($ch === '/' && $next === '*') {
                $buf .= $ch . $next;
                $i++;
                $inBlockComment = true;
                continue;
            }
        }

        if ($inSingle || $inDouble) {
            $buf .= $ch;
            if ($escape) {
                $escape = false;
            } elseif ($ch === '\\') {
                $escape = true;
            } elseif (($inSingle && $ch === "'") || ($inDouble && $ch === '"')) {
                if ($next === $ch) {
                    $buf .= $next;
                    $i++;
                } else {
                    $inSingle = false;
                    $inDouble = false;
                }
            }
            continue;
        }

        if ($ch === '`') {
            $inBacktick = !$inBacktick;
            $buf .= $ch;
            continue;
        }
        if (!$inDouble && $ch === "'") {
            $inSingle = true;
            $buf .= $ch;
            continue;
        }
        if (!$inSingle && $ch === '"') {
            $inDouble = true;
            $buf .= $ch;
            continue;
        }
        if ($ch === ';') {
            $statement = trim($buf);
            if ($statement !== '') $statements[] = $statement;
            $buf = '';
            continue;
        }
        $buf .= $ch;
    }

    $statement = trim($buf);
    if ($statement !== '') $statements[] = $statement;
    return $statements;
}

function importSqlDump(PDO $pdo, $file) {
    $sql = @file_get_contents($file);
    if ($sql === false || trim($sql) === '') {
        throw new RuntimeException('O arquivo database.sql está vazio ou não pôde ser lido.');
    }

    $statements = splitSqlStatements($sql);
    if (!$statements) throw new RuntimeException('Nenhuma instrução SQL válida foi encontrada no backup.');

    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    $pdo->exec("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO'");
    try {
        foreach ($statements as $statement) {
            $trimmed = ltrim($statement);
            if ($trimmed === '' || preg_match('/^(--|#|\/\*)/', $trimmed)) continue;
            $pdo->exec($statement);
        }
    } finally {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }
}

function safeZipRelativePath($name) {
    $name = str_replace('\\', '/', $name);
    if ($name === '' || str_starts_with($name, '/') || preg_match('/^[A-Za-z]:\//', $name)) return false;
    $parts = explode('/', $name);
    foreach ($parts as $part) {
        if ($part === '..') return false;
    }
    return ltrim($name, '/');
}

function extractUploadsToTemp(ZipArchive $zip, $targetDir) {
    $found = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->getNameIndex($i);
        $safe = safeZipRelativePath($entry);
        if ($safe === false) throw new RuntimeException('O backup contém um caminho de arquivo inválido.');
        if ($safe !== 'uploads' && !str_starts_with($safe, 'uploads/')) continue;
        $found = true;
        if (str_ends_with($safe, '/')) {
            @mkdir($targetDir . '/' . $safe, 0755, true);
            continue;
        }
        $dest = $targetDir . '/' . $safe;
        $parent = dirname($dest);
        if (!is_dir($parent) && !@mkdir($parent, 0755, true) && !is_dir($parent)) {
            throw new RuntimeException('Não foi possível preparar os arquivos do backup.');
        }
        $stream = $zip->getStream($entry);
        if (!$stream) throw new RuntimeException('Não foi possível ler um arquivo do backup.');
        $out = @fopen($dest, 'wb');
        if (!$out) { fclose($stream); throw new RuntimeException('Não foi possível gravar um arquivo restaurado.'); }
        stream_copy_to_stream($stream, $out);
        fclose($out);
        fclose($stream);
    }
    return $found;
}

function removeDirectoryContents($dir) {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) ?: [] as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path) && !is_link($path)) {
            removeDirectoryContents($path);
            @rmdir($path);
        } else {
            @unlink($path);
        }
    }
}

function copyDirectoryContents($source, $target) {
    if (!is_dir($source)) return;
    if (!is_dir($target) && !@mkdir($target, 0755, true) && !is_dir($target)) {
        throw new RuntimeException('Não foi possível criar a pasta de uploads.');
    }
    foreach (scandir($source) ?: [] as $item) {
        if ($item === '.' || $item === '..') continue;
        $src = $source . DIRECTORY_SEPARATOR . $item;
        $dst = $target . DIRECTORY_SEPARATOR . $item;
        if (is_dir($src) && !is_link($src)) {
            copyDirectoryContents($src, $dst);
        } elseif (is_file($src)) {
            if (!@copy($src, $dst)) throw new RuntimeException('Não foi possível restaurar um arquivo de upload.');
        }
    }
}

function createSafetyBackup(PDO $pdo, $backupDir) {
    if (!class_exists('ZipArchive')) throw new RuntimeException('A extensão ZIP do PHP não está disponível neste servidor.');
    $stamp = date('Y-m-d_H-i-s');
    $zipName = 'morningfall_backup_pre_restore_' . $stamp . '_' . bin2hex(random_bytes(3)) . '.zip';
    $zipPath = $backupDir . '/' . $zipName;
    $tmpSql = $backupDir . '/.morningfall_pre_restore_' . bin2hex(random_bytes(6)) . '.sql';
    try {
        generateSqlDump($pdo, $tmpSql);
        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Não foi possível criar o backup de segurança antes da restauração.');
        }
        $zip->addFile($tmpSql, 'database.sql');
        $uploadsCount = backupFiles(dirname(__DIR__) . '/assets/uploads', $zip, 'uploads');
        $zip->addFromString('backup-info.txt', "Morningfall Creators\nBackup de segurança criado antes de uma restauração em " . date('Y-m-d H:i:s') . "\nArquivos de upload incluídos: {$uploadsCount}\n");
        $zip->close();
        @unlink($tmpSql);
        return $zipPath;
    } catch (Throwable $e) {
        @unlink($tmpSql);
        @unlink($zipPath);
        throw $e;
    }
}

$msg = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'generate') {
        if (!$backupDirReady) {
            throw new RuntimeException('A pasta de backups não existe ou não possui permissão de escrita no servidor.');
        }

        $stamp = date('Y-m-d_H-i-s');
        $zipName = 'morningfall_backup_' . $stamp . '.zip';
        $zipPath = $backupDir . '/' . $zipName;

        // Alguns hosts compartilhados bloqueiam sys_get_temp_dir().
        // Criamos o SQL temporário na própria pasta de backups.
        $tmpSql = $backupDir . '/.morningfall_' . bin2hex(random_bytes(6)) . '.sql';

        try {
            if (!class_exists('ZipArchive')) {
                throw new RuntimeException('A extensão ZIP do PHP não está disponível neste servidor.');
            }

            generateSqlDump($pdo, $tmpSql);

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Não foi possível criar o arquivo de backup.');
            }

            $zip->addFile($tmpSql, 'database.sql');
            $uploadsCount = backupFiles(dirname(__DIR__) . '/assets/uploads', $zip, 'uploads');
            $info = "Morningfall Creators\n";
            $info .= "Backup gerado em: " . date('Y-m-d H:i:s') . "\n";
            $info .= "Banco: " . (defined('MFC_DB_NAME') ? MFC_DB_NAME : 'config/database.php') . "\n";
            $info .= "Inclui: banco de dados completo + arquivos de assets/uploads.\n";
            $info .= "Arquivos de upload incluídos: {$uploadsCount}\n";
            $info .= "Configurações/credenciais do site não são incluídas no ZIP.\n";
            $zip->addFromString('backup-info.txt', $info);
            $zip->close();

            @unlink($tmpSql);
            $msg = 'Backup gerado com sucesso.';
        } catch (Throwable $e) {
            @unlink($tmpSql);
            @unlink($zipPath);
            $error = $e->getMessage();
        }
    }

    if ($action === 'restore') {
        $restoreSql = null;
        $restoreUploads = null;
        $safetyBackup = null;
        try {
            if (!$backupDirReady) throw new RuntimeException('A pasta de backups não existe ou não possui permissão de escrita no servidor.');
            if (!isset($_FILES['backup']) || $_FILES['backup']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Selecione um arquivo de backup válido.');
            }
            $file = $_FILES['backup'];
            if ($file['size'] <= 0) throw new RuntimeException('O arquivo enviado está vazio.');
            if ($file['size'] > 100 * 1024 * 1024) throw new RuntimeException('O backup é maior que o limite de 100 MB.');
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($ext !== 'zip') throw new RuntimeException('O backup precisa estar no formato .zip.');
            if (!class_exists('ZipArchive')) throw new RuntimeException('A extensão ZIP do PHP não está disponível neste servidor.');

            $zip = new ZipArchive();
            if ($zip->open($file['tmp_name']) !== true) throw new RuntimeException('O arquivo ZIP não pôde ser aberto.');
            $sqlIndex = $zip->locateName('database.sql', ZipArchive::FL_NOCASE | ZipArchive::FL_NODIR);
            if ($sqlIndex === false) {
                $zip->close();
                throw new RuntimeException('Esse arquivo não parece ser um backup do Morningfall Creators: database.sql não encontrado.');
            }

            // Segurança: valide todos os caminhos do ZIP antes de extrair qualquer coisa.
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (safeZipRelativePath($entry) === false) {
                    $zip->close();
                    throw new RuntimeException('O backup contém um caminho de arquivo inválido e não pode ser restaurado.');
                }
            }

            $restoreBase = $backupDir . '/.restore_' . bin2hex(random_bytes(6));
            $restoreUploads = $restoreBase . '/';
            if (!@mkdir($restoreUploads, 0755, true) && !is_dir($restoreUploads)) {
                $zip->close();
                throw new RuntimeException('Não foi possível preparar a área temporária para a restauração.');
            }
            $restoreSql = $restoreBase . '/database.sql';
            $stream = $zip->getStream($zip->getNameIndex($sqlIndex));
            if (!$stream) { $zip->close(); throw new RuntimeException('Não foi possível ler o database.sql do backup.'); }
            $out = @fopen($restoreSql, 'wb');
            if (!$out) { fclose($stream); $zip->close(); throw new RuntimeException('Não foi possível preparar o database.sql para restauração.'); }
            stream_copy_to_stream($stream, $out);
            fclose($out);
            fclose($stream);
            $hasUploads = extractUploadsToTemp($zip, $restoreUploads);
            $zip->close();

            // Sempre crie uma cópia de segurança do estado atual antes de substituir qualquer dado.
            $safetyBackup = createSafetyBackup($pdo, $backupDir);

            // Restaura primeiro o banco. Os arquivos atuais só serão substituídos se o SQL terminar sem erro.
            importSqlDump($pdo, $restoreSql);

            if ($hasUploads) {
                $liveUploads = dirname(__DIR__) . '/assets/uploads';
                if (!is_dir($liveUploads) && !@mkdir($liveUploads, 0755, true) && !is_dir($liveUploads)) {
                    throw new RuntimeException('O banco foi restaurado, mas não foi possível preparar a pasta de uploads. O backup de segurança está disponível.');
                }
                removeDirectoryContents($liveUploads);
                copyDirectoryContents($restoreUploads . 'uploads', $liveUploads);
            }

            $msg = 'Backup restaurado com sucesso. Um backup de segurança do estado anterior foi criado automaticamente.';
            @unlink($restoreSql);
            removeDirectoryContents($restoreBase);
            @rmdir($restoreBase);
        } catch (Throwable $e) {
            // Se o banco chegou a ser alterado e existe um backup de segurança, tente voltar ao estado anterior.
            if ($safetyBackup && is_file($safetyBackup)) {
                try {
                    $rollbackBase = $backupDir . '/.rollback_' . bin2hex(random_bytes(5));
                    @mkdir($rollbackBase, 0755, true);
                    $rollbackZip = new ZipArchive();
                    if ($rollbackZip->open($safetyBackup) === true) {
                        $idx = $rollbackZip->locateName('database.sql', ZipArchive::FL_NOCASE | ZipArchive::FL_NODIR);
                        if ($idx !== false) {
                            $stream = $rollbackZip->getStream($rollbackZip->getNameIndex($idx));
                            $rollbackSql = $rollbackBase . '/database.sql';
                            $out = @fopen($rollbackSql, 'wb');
                            if ($stream && $out) {
                                stream_copy_to_stream($stream, $out);
                                fclose($out); fclose($stream);
                                importSqlDump($pdo, $rollbackSql);
                            } elseif ($stream) {
                                fclose($stream);
                            }
                        }
                        $rollbackZip->close();
                    }
                    @unlink($rollbackBase . '/database.sql');
                    @rmdir($rollbackBase);
                } catch (Throwable $rollbackError) {
                    $error = $e->getMessage() . ' O sistema não conseguiu concluir o rollback automático; use o backup de segurança criado antes da restauração.';
                }
            }
            if ($error === '') $error = $e->getMessage();
            if ($restoreSql) @unlink($restoreSql);
            if ($restoreUploads) {
                $base = dirname($restoreUploads);
                removeDirectoryContents($base);
                @rmdir($base);
            }
        }
    }

    if ($action === 'delete') {
        $name = basename($_POST['file'] ?? '');
        if ($name === '' || !preg_match('/\.zip$/i', $name)) {
            $error = 'Backup inválido.';
        } else {
            $path = $backupDir . '/' . $name;
            if (!is_file($path)) {
                $error = 'Backup não encontrado.';
            } elseif (@unlink($path)) {
                $msg = 'Backup excluído com sucesso.';
            } else {
                $error = 'Não foi possível excluir o backup.';
            }
        }
    }
}

$backups = [];
if (is_dir($backupDir)) {
    foreach (glob($backupDir . '/*.zip') ?: [] as $file) {
        if (!is_file($file)) continue;
        $backups[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'mtime' => filemtime($file),
        ];
    }
}
usort($backups, fn($a, $b) => $b['mtime'] <=> $a['mtime']);

function humanSize($bytes) {
    $units = ['B','KB','MB','GB'];
    $i = 0;
    while ($bytes >= 1024 && $i < count($units)-1) { $bytes /= 1024; $i++; }
    return number_format($bytes, $i ? 2 : 0, ',', '.') . ' ' . $units[$i];
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Backups · Morningfall Creators</title>
<link rel="stylesheet" href="../assets/css/style.css">
<style>
.backup-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px}
.backup-card{background:#111722;border:1px solid #2d394e;border-radius:16px;padding:22px}
.backup-card h2{margin:0 0 8px}.backup-card p{color:#8998b2;font-size:14px;line-height:1.5}
.backup-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:18px}.backup-file{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:15px 0;border-bottom:1px solid #273247}.backup-file:last-child{border-bottom:0}.backup-name{font-weight:700;word-break:break-all}.backup-meta{font-size:12px;color:#8190aa;margin-top:4px}.danger-btn{background:#5a1f2b!important;border-color:#79303e!important;color:#ffd1d8!important}.muted-box{padding:14px;background:#0c121d;border:1px solid #273247;border-radius:12px;color:#91a0b8;font-size:13px;line-height:1.5}.master-badge{display:inline-block;background:#3a276b;color:#d8c9ff;border:1px solid #6549a9;border-radius:999px;padding:5px 9px;font-size:11px;font-weight:800;letter-spacing:.4px}.file-input{width:100%;box-sizing:border-box}
@media(max-width:800px){.backup-grid{grid-template-columns:1fr}.backup-file{align-items:flex-start;flex-direction:column}}
</style>
</head>
<body>
<header class="topbar"><div><b>MORNINGFALL</b> <span>CREATORS</span></div><div class="top-user"><?=e($_SESSION['staff_name']??'')?> · <span class="master-badge">MASTER</span> · <a href="../logout.php">Sair</a></div></header>
<main class="container">
<a href="index.php">← Dashboard</a>
<div class="page-head"><div><p class="eyebrow">ADMINISTRAÇÃO · MASTER</p><h1>🗄️ Backup do Sistema</h1><p class="muted">Gere, baixe, restaure ou exclua backups do Morningfall Creators.</p></div></div>
<?php if($msg):?><div class="alert success"><?=e($msg)?></div><?php endif;?>
<?php if($error):?><div class="alert danger"><?=e($error)?></div><?php endif;?>
<div class="backup-grid">
<section class="backup-card">
<h2>💾 Gerar Bkp</h2>
<p>Cria um backup do banco de dados atual e dos arquivos enviados pelos streamers.</p>
<div class="muted-box">O ZIP contém <strong>database.sql</strong> e <strong>assets/uploads</strong>. Senhas, webhooks e credenciais de configuração não são incluídos.</div>
<form method="post" class="backup-actions" onsubmit="return confirm('Gerar um novo backup do sistema agora?');">
<input type="hidden" name="action" value="generate"><button class="btn primary">💾 Gerar Bkp</button>
</form>
</section>
<section class="backup-card">
<h2>🔄 Restaurar Backup</h2>
<p>Envie um backup ZIP para <strong>substituir os dados atuais</strong> do Morningfall Creators.</p>
<div class="muted-box"><strong>⚠️ Atenção:</strong> antes de restaurar, o sistema cria automaticamente um backup de segurança do estado atual. A restauração pode substituir o banco de dados e os arquivos de <strong>assets/uploads</strong>.</div>
<form method="post" enctype="multipart/form-data" class="form" onsubmit="return confirmRestore(this)">
<input type="hidden" name="action" value="restore"><label>Arquivo .ZIP do backup</label><input class="file-input" type="file" name="backup" accept=".zip,application/zip" required>
<small class="muted">Máximo definido pelo painel: 100 MB (o servidor também pode possuir limite próprio de upload).</small>
<div class="backup-actions"><button class="btn secondary">🔄 Restaurar Backup</button></div>
</form>
</section>
</div>
<section class="panel" style="margin-top:18px">
<div class="section-title"><div><p class="eyebrow">ARQUIVOS</p><h2>Backups armazenados</h2></div><span class="setup-badge">MASTER</span></div>
<?php if(!$backups):?><div class="muted-box">Nenhum backup armazenado ainda.</div><?php else: foreach($backups as $b):?>
<div class="backup-file"><div><div class="backup-name">📦 <?=e($b['name'])?></div><div class="backup-meta"><?=date('d/m/Y H:i:s',$b['mtime'])?> · <?=e(humanSize($b['size']))?></div></div><div class="backup-actions" style="margin-top:0"><a class="btn secondary" href="backup_download.php?file=<?=urlencode($b['name'])?>">⬇️ Download</a><form method="post" style="display:inline" onsubmit="return confirm('Excluir este backup permanentemente?');"><input type="hidden" name="action" value="delete"><input type="hidden" name="file" value="<?=e($b['name'])?>"><button class="btn danger-btn">🗑️ Excluir</button></form></div></div>
<?php endforeach; endif;?>
</section>
<footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
</main>
<script>
function confirmRestore(form){
    const input=form.querySelector('input[type="file"]');
    const name=input && input.files.length ? input.files[0].name : 'backup selecionado';
    return confirm('ATENÇÃO!\n\nO backup "'+name+'" irá substituir os dados atuais do sistema.\n\nUm backup de segurança será criado automaticamente antes de continuar.\n\nDeseja realmente restaurar este backup?');
}
</script>
</body></html>
