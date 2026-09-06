<?php
// Página pública de consulta dos comandos.
// Mantemos esta URL antiga funcionando para quem já possui o link.
header('Location: ../comandos.php', true, 302);
exit;
