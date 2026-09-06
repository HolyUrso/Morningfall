WEBHOOK INDIVIDUAL POR STREAMER

Cada streamer possui sua própria URL de webhook do Discord.
A Staff configura a URL em:
Painel Staff > Streamers > Editar Streamer

O botão "Testar webhook" agora mostra o diagnóstico técnico quando o envio falhar:
- HTTP 0 + erro cURL: o servidor não conseguiu conectar ao Discord.
- HTTP 401/404: webhook inválida, apagada ou expirada.
- HTTP 403: acesso negado pelo Discord.
- HTTP 429: limite de requisições.
- HTTP 2xx: envio realizado com sucesso.

A URL da webhook nunca é exibida na tela de diagnóstico.

O banco existente recebe a coluna automaticamente pelo config/database.php.
Não é necessário importar o SQL novamente.
