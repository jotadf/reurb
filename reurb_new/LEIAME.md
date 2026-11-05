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
   (e-mail ou CPF + senha).

## Senhas

- Configurado como `'senha_formato' => 'texto'`, igual ao sistema atual: a senha é gravada
  e conferida sem criptografia, com comparação exata (maiúsculas e espaços contam).
  Na auditoria a senha aparece sempre como `***`.
- Para mudar depois: `'auto'` reconhece sozinho o formato de cada senha gravada
  (`password_hash`, MD5, SHA1, SHA256, SHA512 ou texto) e grava no mesmo formato;
  também dá para fixar um deles, ex.: `'password_hash'`.
- Se o login falhar, um aviso amarelo de diagnóstico aparece abaixo do erro quando o
  sistema é acessado pelo próprio servidor (`localhost`), pois o padrão é
  `'login_diagnostico' => 'local'`. Ele diz se o usuário não foi encontrado, se está
  inativo, se a senha difere (sem mostrá-la) ou se houve erro de conexão com o banco.
  Para ver o aviso acessando de outro computador, use `true`; em produção, use `false`.
  O motivo também vai sempre para o log de erros do PHP.
- Senha em texto puro ou MD5/SHA sem "sal" é fácil de descobrir se o banco vazar. Quando
  o sistema atual aceitar `password_hash`, use `'senha_formato' => 'password_hash'` e
  `'converter_senha_no_login' => true` para migrar as senhas aos poucos, a cada login.

## Perfis de acesso

As regras ficam em `config/permissoes.php` e valem pelo **nome** cadastrado na tabela
`perfil`, sem diferença de maiúsculas ou acentos ("Usuário" = "usuario").

| Perfil | Gestão de Acesso (perfis, equipes, usuários, auditoria) | Selagem, Domicílios, Sócio jurídico, Caracterização |
|---|---|---|
| Administrador | ver, incluir, alterar, excluir | ver, incluir, alterar, excluir |
| Usuário | sem acesso | ver, incluir, alterar, excluir |
| Observador | sem acesso | só ver |
| qualquer outro nome | sem acesso | sem acesso |

- A permissão é verificada no servidor em cada página e em cada gravação, não só
  escondida no menu. Uma tentativa sem permissão mostra a página "403 – Acesso negado".
- O menu e os botões (Novo, Editar, Excluir, Salvar) aparecem conforme o perfil. Para o
  Observador, a ficha abre com o selo "Somente visualização" e todos os campos bloqueados.
- O perfil é lido do banco a cada página: trocar o perfil de alguém ou inativá-lo vale na
  hora. Um usuário inativado com a sessão aberta é desconectado no próximo clique.
- O sistema não deixa ficar sem Administrador: o último Administrador ativo não pode ser
  excluído, inativado nem ter o perfil trocado.
- Para tirar do perfil Usuário a permissão de excluir, troque `$TUDO` por
  `['ver', 'incluir', 'alterar']` na linha dele.
- Se renomear um perfil na tabela `perfil`, renomeie também em `config/permissoes.php`
  (ou use o ID do perfil como chave).

### Proteger telas existentes

Os itens de menu Importar Dados, Gerenciar Imagens e os relatórios apontam para telas do
sistema atual. `menu_externo` em `config/permissoes.php` só esconde esses links. Para
bloquear a tela em si, coloque no início dela (ajuste o caminho até a pasta do CRUD):

```php
require __DIR__ . '/reurb_crud/lib/bootstrap.php';
exigir_login();
if (!pode_link_externo('importar_dados')) {   // mesma chave usada em menu_externo
    http_response_code(403);
    exit('Acesso negado.');
}
```

## Menu lateral

O menu fica em `includes/menu.php`. A estrutura (grupos, itens, ordem, ícones e textos)
está no array `$MENU_LATERAL` no início do arquivo. As telas do CRUD o incluem pelo
`includes/topo.php`, e qualquer outra página do sistema também pode incluí-lo:

```php
<?php
// No início da página, antes de qualquer HTML (ajuste o caminho até a pasta do CRUD)
require_once __DIR__ . '/reurb_crud/lib/bootstrap.php';
exigir_login();
$menuAtivo = 'importar_dados';   // item que fica destacado (opcional)
?>
...
<div id="wrapper">
    <?php require RAIZ . '/includes/menu.php'; ?>
    <div id="content-wrapper" class="d-flex flex-column">
        ... conteúdo da página ...
    </div>
</div>
```

- Os itens aparecem conforme o perfil de quem está logado (`config/permissoes.php`).
- Os links saem com o caminho completo da pasta do CRUD (ex.: `/reurb_crud/lista.php?t=selagem`),
  então funcionam a partir de qualquer pasta. Esse caminho é descoberto sozinho; se os links
  ficarem errados (ex.: pasta publicada por `Alias` do Apache), informe-o em `crud_url` no
  `config/config.php`.
- Em `links_externos`, prefira caminhos a partir da raiz do site, ex.:
  `'/reurb/importar.php'`. Caminhos relativos são lidos a partir da pasta do CRUD.
- A página precisa carregar o CSS e o JS do SB Admin 2 (o botão de recolher o menu é do
  `sb-admin-2.min.js`). Se preferir o layout completo do CRUD, inclua
  `includes/topo.php` no início do conteúdo e `includes/rodape.php` no fim.
- O `lib/bootstrap.php` declara funções com nomes curtos (`e()`, `url()`, `db()`, `cfg()`...).
  Se a página do sistema atual já tiver funções com esses nomes, o PHP acusa
  "Cannot redeclare". Nesse caso, me avise para renomearmos.

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
- Fotos: o banco guarda o nome do arquivo, e o endereço é montado com `fotos_base_url`
  (`config/config.php`, padrão `/arquivos/`, que corresponde a `~/www/arquivos`) mais a
  pasta da tela (`pasta_fotos` em `config/tabelas.php`): Selagem e Domicílios → `domicilio/`,
  Sócio jurídico → `socio_juridico/`, Caracterização → `caracterizacao/`. Cada campo
  `foto_*` tem um botão para abrir a imagem e mostra uma miniatura, inclusive para o
  Observador; se o arquivo não existir ou não for imagem (ex.: PDF), aparece um aviso.
- Campos de vínculo (`id_submissao_pai`, `codigo_selo`)
  têm botão que abre a ficha ligada.
- A ficha do lote lista os domicílios dele; a ficha do domicílio lista o cadastro
  sociojurídico e a caracterização (ligados por `codigo_selo` = `numero_selo`).
- Pendências na listagem (`'indicadores'` em `config/tabelas.php`):
  - Selagens: coluna "Domicílios" com a quantidade cadastrada; vermelho "Sem domicílio"
    quando não há nenhum; amarelo "1 de 2" quando há menos que o declarado no lote.
  - Domicílios: colunas "Sócio jurídico" e "Caracterização" com "Sim", vermelho "Falta"
    ou amarelo "2 cadastros" quando o mesmo selo tem mais de um cadastro.
  - Botões no topo mostram quantos registros têm cada pendência e filtram a lista
    (funcionam junto com a pesquisa). Clicar no selo abre os registros ligados; o "+" ao
    lado de "Falta" abre o cadastro já com o vínculo preenchido (só para quem pode incluir).
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
- **Índices recomendados** (o script do banco não cria). Sem eles, as colunas de pendência
  e a auditoria ficam lentas quando houver milhares de registros:

  ```sql
  CREATE INDEX idx_domicilio_pai  ON domicilios_import (id_submissao_pai);
  CREATE INDEX idx_socio_selo     ON cadastro_sociojuridico_import (codigo_selo);
  CREATE INDEX idx_caract_selo    ON caracterizacao_vulnerabilidade_import (codigo_selo);
  CREATE INDEX ix_auditoria_tabela ON auditoria (tabela, atualizado);
  ```
