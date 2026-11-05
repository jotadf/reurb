# CRUD REURB (PHP + PDO + SB Admin 2)

Telas de listagem, inclusão, edição e exclusão para as tabelas do modelo,
no mesmo visual da "Ficha de Selagem de Lote".

| Menu | Tabela | Chave |
|---|---|---|
| Perfis | `perfil` | `id` |
| Equipes | `equipe` | `id` |
| Usuários | `usuario` | `id` (senha gravada com `password_hash`) |
| Auditoria | `auditoria` | `id` (somente consulta) |
| Selagens | `selagem_lotes_import` | `id_submissao` |
| Domicílios | `domicilios_import` | `numero_selo` |
| Sócio jurídicos | `cadastro_sociojuridico_import` | `id_submissao` |
| Caracterizações | `caracterizacao_vulnerabilidade_import` | `id_submissao` |

## Instalação

1. Copie a pasta para o servidor (PHP 8.0 ou superior, extensão `pdo_mysql`).
2. Em `config/config.php`, informe o banco (`db`), o nome da ocupação e os links do
   menu que já existem no seu sistema (`links_externos`).
3. Tema: por padrão os arquivos do SB Admin 2 vêm do CDN. Se o seu sistema já tem
   as pastas `css/`, `js/` e `vendor/` do tema, aponte `tema_base` para elas.
4. Login: se o sistema já tem login, informe em `sessao_id_usuario` e
   `sessao_nome_usuario` as chaves de `$_SESSION` que ele usa — o CRUD reaproveita
   a sessão e grava esse ID na auditoria. Senão, use o `login.php` incluído
   (e-mail ou CPF + senha; senhas antigas em MD5 são convertidas no primeiro acesso).

## Endereços

- `lista.php?t=selagem` — listagem com pesquisa, ordenação por coluna e paginação
- `form.php?t=selagem` — novo registro
- `form.php?t=selagem&id=761081201` — ficha de edição
- `lista.php?t=domicilios&id_submissao_pai=761081201` — domicílios de um lote

Chaves de `t`: `selagem`, `domicilios`, `socio`, `caracterizacao`, `perfil`, `equipe`, `usuario`, `auditoria`.

## Como funciona

- Configuração alinhada ao `script_banco_reurb.sql` (MySQL 8): todas as 72 colunas de
  `caracterizacao_vulnerabilidade_import` têm rótulo e estão agrupadas em seções.
- As colunas são lidas do banco (`INFORMATION_SCHEMA`). Coluna nova criada depois no
  banco aparece sozinha na ficha (em "Outros dados"), com tipo de campo conforme a coluna.
- A ficha de Caracterização é dividida em abas (Moradia, Construção, Infraestrutura,
  Pessoas e saúde, Riscos, Entorno, Registro). Para usar abas em outra tela, como a de
  Sócio jurídico, basta incluir uma chave `'abas'` na configuração dela, agrupando as
  seções. Ao salvar, a ficha volta na aba em que se estava; se houver erro, abre a aba
  com o erro e mostra quantos campos com erro há em cada aba.
- Campos obrigatórios vêm do banco: toda coluna `NOT NULL` sem `DEFAULT` é obrigatória
  (marcada com * e validada ao salvar). Ex.: `uuid`, `codigo_selo`, `r1_nome`,
  `id_submissao_pai`, `perfil.descricao`, `usuario.id_equipe`.
- Valores `DEFAULT` do banco já vêm preenchidos num registro novo
  (ex.: `numero_residentes` = 1, rendas = 0,00, `qtd_domicilios_total` = 1).
- O script não tem chave estrangeira entre lote, domicílio e cadastros, então o CRUD
  impede a exclusão de um lote que ainda tem domicílios e de um domicílio que ainda tem
  cadastro sociojurídico ou caracterização. Perfil e equipe em uso são protegidos pela
  chave estrangeira do próprio banco.
- Rótulos, larguras, seções, campos somente leitura, listas de opções e colunas da
  listagem ficam em `config/tabelas.php`.
- ID do Kobo e chave primária: editáveis só na inclusão. Data/Hora de Envio: sempre
  somente leitura, como na ficha original.
- Campos `foto_*` têm botão para abrir a imagem (configure `fotos_base_url` se o banco
  guarda só o nome do arquivo). Campos de vínculo (`id_submissao_pai`, `codigo_selo`)
  têm botão que abre a ficha ligada.
- A ficha do lote lista os domicílios dele; a ficha do domicílio lista o cadastro
  sociojurídico e a caracterização (ligados por `codigo_selo` = `numero_selo`).
- Auditoria: toda inclusão, alteração e exclusão grava `tabela`, `acao`
  (INSERT/UPDATE/DELETE), `usuario`, `valor_antigo`, `valor_atual` (JSON) e `atualizado`.
  Na alteração entram só os campos que mudaram; salvar sem mudar nada não gera registro.
  Senhas aparecem como `***`.
- Segurança: consultas preparadas (PDO), nomes de tabela/coluna validados contra o
  banco, token CSRF em todo POST e exclusão só por POST com confirmação.

## Pontos para conferir

- **Opções do "Tipo de Ocupação do Lote"**: usei Unifamiliar, Multifamiliar, Misto,
  Comercial, Institucional e Lote vazio. Ajuste em `config/tabelas.php`. Um valor
  importado que não esteja na lista continua aparecendo e é preservado.
- **Campos Sim/Não** gravam `sim`/`nao` (padrão do Kobo). Se o banco já tiver
  "Sim"/"SIM", o valor é reconhecido e mantido como está.
- **Permissões por perfil** não foram implementadas: qualquer usuário logado acessa
  todas as telas.
- Para a auditoria ficar rápida com muitos registros, crie um índice:
  `CREATE INDEX ix_auditoria_tabela ON auditoria (tabela, atualizado);`
