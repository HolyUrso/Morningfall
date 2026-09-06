MORNINGFALL CREATORS - V2
===========================

INSTALAÇÃO NO INFINITYFREE

1. Crie um banco MySQL no painel do InfinityFree.
2. Abra phpMyAdmin.
3. Importe database/morningfall_creators.sql.
4. Edite config/database.php com:
   - host
   - nome do banco
   - usuário
   - senha
5. Envie todos os arquivos para htdocs.
6. Abra /install.php UMA VEZ para criar o primeiro usuário da Staff.
7. Depois de criar o usuário, APAGUE install.php.
8. Acesse /login.php.

LOGIN DO STREAMER
- O streamer poderá consultar seus dados através de streamer-login.php.
- A Staff cria/define o código de consulta no cadastro do streamer.
- O streamer não poderá alterar pontuação, VODs ou regras.

REGRAS INICIAIS
- Live: 1 ponto por hora completa.
- Máximo: 10 pontos por VOD.
- Os demais pontos ficarão configuráveis no painel.

IMPORTANTE
- Esta versão não usa Node.js, Python ou processos em segundo plano.
- Foi feita para hospedagem PHP/MySQL compartilhada.


ATUALIZAÇÃO: Gestão de Streamers
- Cadastro, edição e ativação/inativação.
- Busca por nome, Discord e plataforma.
- Filtros por categoria e status.
- Perfil com VODs, tempo total em HH:MM e histórico de pontos.


APARÊNCIA DO STREAMER
- Painel Staff > Aparência do Streamer (ou Configurações > Setup).
- Permite enviar logo e background em PNG/JPG/WEBP.
- O background padrão está em assets/img/streamer-background-default.png.
- Rodapé: Morningfal Creatos V1 — Desenvolvido por luciferms666. O nome aponta para o perfil Discord 207712204890439680.
