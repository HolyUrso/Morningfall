CARMESIM CREATORS — RANKING DISCORD

IMPORTANTE — INFINITYFREE
A criação do ranking usa o mesmo Cloudflare Relay já usado pelas outras webhooks do projeto, porque o InfinityFree pode bloquear chamadas diretas para a API do Discord.

1) No painel Master > Ranking Discord, informe o Webhook do canal.
2) Clique em "Atualizar agora no Discord".
3) O site envia pelo Cloudflare Relay configurado em Discord / Relay.
4) O endpoint de 1 em 1 hora continua disponível.

EDIÇÃO DA MESMA MENSAGEM
Para editar a mesma mensagem depois da primeira criação, o site precisa ter o ID da mensagem retornado pelo Relay. Se o Worker atual responder apenas {"success":true}, sem o ID da mensagem, o site não consegue saber qual mensagem editar.

Nesse caso, atualize o Worker /send para repassar o ID retornado pelo Discord no JSON, por exemplo:
{"success":true,"message_id":"123456789012345678"}

O PHP aceita message_id, id ou message.id na resposta do Relay.

Se o Worker não puder ser alterado agora, a primeira publicação ainda pode funcionar pelo Relay, mas a edição automática da mesma mensagem ficará sem vínculo.


RANKINGS PUBLICADOS
- 🏆 Ranking de Streamers: ranking geral por desempenho/pontos, sem exibir a pontuação.
- 🔥 Histórico de Lives: quantidade total de Lives/VODs válidas com mais de 2 horas.
- ⏱️ Ranking de Horas: total acumulado de horas e minutos em Lives/VODs.
- 🎬 Ranking de Conteúdo: quantidade de conteúdos extras aprovados, sem contar VODs/Lives.

Todos os rankings são Top 10 e o nome do streamer permanece clicável para a VOD/LIVE mais recente ou canal como fallback.
