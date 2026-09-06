<?php
require_once '../config/auth.php';
require_once '../config/database.php';

$p = $pdo->query("SELECT * FROM prizes ORDER BY points_novato ASC, name ASC")->fetchAll();
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Prêmios</title>
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="container">
  <a href="index.php">← Dashboard</a>

  <div class="page-head">
    <h1>Tabela de Troca de Pontos</h1>
    <a class="btn primary" href="premio_novo.php">+ Cadastrar troca</a>
  </div>

  <table>
    <thead>
      <tr>
        <th>Recompensa</th>
        <th>Novato</th>
        <th>Oficial</th>
        <th>Afiliado</th>
        <th>Status</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($p as $x): ?>
      <tr>
        <td><?=htmlspecialchars($x['name'])?></td>
        <td><?=$x['points_novato']?></td>
        <td><?=$x['points_oficial']?></td>
        <td><?=$x['points_afiliado']?></td>
        <td><?=$x['active'] ? 'Ativo' : 'Inativo'?></td>
        <td>
          <a class="btn small-btn" href="premio_editar.php?id=<?=$x['id']?>">✏️ Editar</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</main>
<footer class="site-footer">Morningfall Creators V1 <span>•</span> Desenvolvido por <a href="https://discord.com/users/207712204890439680" target="_blank" rel="noopener noreferrer">luciferms666</a></footer>
</body>
</html>
