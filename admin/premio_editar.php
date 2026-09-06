<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../config/audit.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: premios.php');
    exit;
}

$st = $pdo->prepare("SELECT * FROM prizes WHERE id = ? LIMIT 1");
$st->execute([$id]);
$prize = $st->fetch();
$beforePrize=$prize ? ['name'=>$prize['name'],'points_novato'=>$prize['points_novato'],'points_oficial'=>$prize['points_oficial'],'points_afiliado'=>$prize['points_afiliado'],'active'=>$prize['active']] : null;

if (!$prize) {
    header('Location: premios.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $novato = max(0, (int)($_POST['novato'] ?? 0));
    $oficial = max(0, (int)($_POST['oficial'] ?? 0));
    $afiliado = max(0, (int)($_POST['afiliado'] ?? 0));
    $active = isset($_POST['active']) ? 1 : 0;

    if ($name === '') {
        $error = 'Informe o nome da recompensa.';
    } else {
        $up = $pdo->prepare("UPDATE prizes SET name = ?, description = ?, points_novato = ?, points_oficial = ?, points_afiliado = ?, active = ? WHERE id = ?");
        $up->execute([$name, $description, $novato, $oficial, $afiliado, $active, $id]);
        auditLog($pdo,'Prêmio alterado','Prêmios',null,'','Recompensa alterada: '.$name,$beforePrize,['name'=>$name,'points_novato'=>$novato,'points_oficial'=>$oficial,'points_afiliado'=>$afiliado,'active'=>$active]);
        header('Location: premios.php');
        exit;
    }

    $prize['name'] = $name;
    $prize['description'] = $description;
    $prize['points_novato'] = $novato;
    $prize['points_oficial'] = $oficial;
    $prize['points_afiliado'] = $afiliado;
    $prize['active'] = $active;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Editar Troca</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="container">
  <a href="premios.php">← Prêmios</a>
  <h1>Editar Troca</h1>

  <?php if ($error): ?>
    <div class="alert error"><?=htmlspecialchars($error)?></div>
  <?php endif; ?>

  <form method="post" class="form">
    <label>Nome da recompensa</label>
    <input name="name" value="<?=htmlspecialchars($prize['name'])?>" required>

    <label>Descrição</label>
    <textarea name="description"><?=htmlspecialchars($prize['description'] ?? '')?></textarea>

    <label>Pontos Novato</label>
    <input type="number" name="novato" min="0" value="<?=$prize['points_novato']?>" required>

    <label>Pontos Oficial</label>
    <input type="number" name="oficial" min="0" value="<?=$prize['points_oficial']?>" required>

    <label>Pontos Afiliado</label>
    <input type="number" name="afiliado" min="0" value="<?=$prize['points_afiliado']?>" required>

    <label style="display:flex;align-items:center;gap:8px;">
      <input type="checkbox" name="active" value="1" <?=$prize['active'] ? 'checked' : ''?>>
      Recompensa ativa
    </label>

    <div style="display:flex;gap:10px;align-items:center;">
      <button class="btn primary" type="submit">💾 Salvar alterações</button>
      <a class="btn" href="premios.php">Cancelar</a>
    </div>
  </form>
</main>
<footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
</body>
</html>
