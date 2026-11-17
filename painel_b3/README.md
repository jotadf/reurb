# Painel B3 — cotações e análises da bolsa brasileira (PHP + MySQL)

Painel web para acompanhar e analisar ações, FIIs, ETFs e BDRs da B3 a partir da API
[brapi.dev](https://brapi.dev). Segue a estrutura e o visual do sistema REURB:
tema **SB Admin 2**, menu lateral azul, barra superior com usuário, cartões com cabeçalho
em degradê, tabelas DataTables, login por CPF e páginas montadas com `menu.php`,
`top_bar.php` e `rodape.php`, além das classes em `actions/` e `dto/`.

## O que o painel faz

| Página | Conteúdo |
|---|---|
| **Visão geral** (`index.php`) | Ibovespa, maiores altas e baixas, tabela de cotações com faixa de 52 semanas, gráfico de variação do dia e resumo da carteira |
| **Análise de ativo** (`ativo.php`) | Gráfico de linha ou candles com médias de 20 e 50 dias e Bandas de Bollinger, volume, IFR(14), MACD, drawdown, retorno, volatilidade, Sharpe, beta contra o Ibovespa, dividend yield, fundamentos e proventos |
| **Comparar ativos** (`comparar.php`) | Até 8 ativos em base 100, gráfico risco × retorno, tabela de métricas e matriz de correlação |
| **Minha carteira** (`carteira.php`) | Posições por usuário, patrimônio, resultado, variação do dia, alocação e evolução da composição atual |
| **Ativos monitorados** (`ativos.php`) | Lista de acompanhamento (administrador inclui, altera e remove) |
| **Usuários** (`usuarios.php`) | Cadastro de usuários e perfis (somente administrador) |

## Requisitos

- PHP 7.4 ou superior (testado no 8.3) com as extensões `pdo_mysql`, `curl`, `json` e `mbstring`
- MySQL 5.7+/8.x ou MariaDB 10.3+
- Acesso de saída HTTPS do servidor para `brapi.dev`
- Token gratuito da brapi: crie a conta em https://brapi.dev/dashboard

As bibliotecas de interface (Bootstrap, jQuery, DataTables, Chart.js, Font Awesome) já estão
na pasta `vendor/`, então o painel funciona mesmo em servidores sem acesso a CDNs.

## Instalação

1. **Banco de dados:** importe `database/schema.sql` (phpMyAdmin → Importar, ou
   `mysql -u root -p < database/schema.sql`). O script cria o banco `painel_b3`, as tabelas,
   o usuário inicial e quatro ativos de exemplo. Se o seu provedor não deixa criar bancos,
   crie o banco pelo painel de controle, apague as duas linhas `CREATE DATABASE`/`USE` do
   script e importe o restante dentro do banco criado.
2. **Usuário do MySQL:** crie um usuário com permissão de leitura e escrita somente nesse banco.
3. **Configuração:** edite `config/config.php` e informe host, banco, usuário, senha e o
   token da brapi. Também é possível usar variáveis de ambiente (`PAINELB3_DB_HOST`,
   `PAINELB3_DB_NAME`, `PAINELB3_DB_USER`, `PAINELB3_DB_PASS`, `PAINELB3_BRAPI_TOKEN`).
4. **Envio dos arquivos:** copie a pasta inteira para o servidor (ex.: `public_html/painel-b3`).
   No Docker do REURB, basta colocá-la dentro de `www/`.
5. **Primeiro acesso:** abra o endereço no navegador e entre com
   **CPF `000.000.000-00` e senha `admin123`**. Troque a senha logo em seguida
   (menu do usuário → Alterar senha) e cadastre os demais usuários.

### Atualização automática (opcional, recomendado)

As páginas consultam a API sozinhas quando o cache vence. Para deixar tudo pronto antes
de alguém abrir o painel, agende no cron do servidor (ou em “Tarefas Cron” do cPanel):

```
0,30 10-18 * * 1-5        php /caminho/painel-b3/cron/atualizar_cotacoes.php
30 19 * * 1-5             php /caminho/painel-b3/cron/atualizar_cotacoes.php --historico
```

## Histórico oficial da B3 (Séries Históricas - COTAHIST)

A B3 publica gratuitamente arquivos com o histórico diário de todos os ativos à vista, incluindo
FIIs, desde 1986. O painel importa esses arquivos e grava somente os ativos da lista de
acompanhamento e das carteiras. Com isso, os períodos de 6 meses a 10 anos funcionam mesmo
no plano gratuito da brapi, que entrega só 3 meses.

```
# 1) Carga inicial (uma vez): baixa os arquivos anuais
php cron/importar_cotahist.php --anos=2016-2026

# 2) Todo dia útil à noite: baixa os arquivos diários ainda não importados
0 21 * * 1-5  php /caminho/painel-b3/cron/importar_cotahist.php --diario

# Alternativa: arquivo baixado manualmente no site da B3 (.ZIP ou .TXT)
php cron/importar_cotahist.php --arquivo=/caminho/COTAHIST_A2024.ZIP
```

- Depois de incluir um ativo novo na lista, rode de novo o `--anos` para trazer o passado dele.
- **Instalação já existente:** execute uma vez `database/atualizacao_cotahist.sql` antes de usar.
- Os arquivos da B3 trazem preços nominais. Depois de cada importação, o painel recalcula o
  **preço ajustado** usando os proventos da brapi e detectando desdobramentos e grupamentos
  (mudança de patamar numa proporção redonda, como 1:2 ou 10:1, já na abertura). As detecções
  aparecem na saída do comando, para conferência. Proventos antigos que a brapi não informar
  não entram no ajuste, então o retorno de períodos longos pode ficar um pouco subestimado.
- Nas datas cobertas pela B3, os dados oficiais prevalecem sobre os da API.
- Os gráficos de preço e os indicadores usam a série ajustada; a cotação do topo é a nominal.
- Endereço de download: `b3.url_base` em `config.php`. Se o servidor da B3 recusar a conexão por
  certificado SSL, defina `'verificar_ssl' => false` ou baixe os arquivos manualmente.
- O índice Ibovespa não faz parte desses arquivos; o histórico dele continua vindo da brapi.

## Plano gratuito da brapi: o que muda

Segundo a brapi, o plano gratuito tem até 15.000 requisições por mês, 1 ativo por requisição,
histórico dos últimos 3 meses e dados atualizados a cada 30 minutos. O painel já vem ajustado
para isso:

- **Cache de 30 minutos no pregão** (`cache.cotacao_minutos`) e de 120 minutos fora dele
  (`cache.cotacao_minutos_fora_pregao`). Um cache menor que a atualização da própria API não
  traz preço mais novo. Em plano pago (15 min ou menos), reduza o valor.
- **Consumo estimado** (1 requisição por ativo, ~9 h de pregão, 22 dias): com cache de 30 min,
  cerca de 400 requisições por ativo/mês, o que comporta uns 35 ativos (contando o Ibovespa e os
  ativos das carteiras). O consumo do mês aparece na visão geral. Feriados não são detectados;
  nesses dias o painel apenas consulta um pouco mais.
- **Histórico:** a API é consultada no máximo até `brapi.range_maximo` (`3mo` no gratuito).
  Os períodos maiores (6 meses a 10 anos) usam o histórico guardado no MySQL, importado da B3
  (seção acima) e acumulado a cada pregão. Enquanto o período não estiver completo, a tela avisa desde quando há dados.
  Para acumular sem depender de alguém abrir o painel, agende o cron `--historico` (abaixo).
  Em plano pago, aumente `brapi.range_maximo` (ex.: `1y` ou `5y`) e os períodos longos ficam
  completos na primeira consulta.
- Em plano pago, ative também `varios_ativos_por_requisicao` em `config.php`.
- Sem token, a brapi só responde PETR4, VALE3, MGLU3 e ITUB4.

## Estrutura

```
painel-b3/
├── index.php, ativo.php, comparar.php, carteira.php, ativos.php, usuarios.php, altera_senha.php
├── save_*.php, del_*.php, atualizar.php      ações de formulário (POST + token CSRF)
├── form_login.php, valida_login.php, verifica_login.php, sair.php
├── head.php, menu.php, top_bar.php, rodape.php, layout_topo.php, layout_base.php
├── funcoes.php                                formatação (R$, %), CSRF, mensagens
├── actions/
│   ├── Model.php           conexão PDO (mesmo papel do Model.php do REURB)
│   ├── Login.php, ManterUsuario.php, ManterAtivo.php, ManterCarteira.php
│   ├── BrapiClient.php     chamadas à API com curl_multi e registro em api_log
│   ├── ServicoMercado.php  regras de cache, gravação de cotações, histórico e proventos
│   ├── ImportadorCotahist.php  leitura dos arquivos da B3 e cálculo do preço ajustado
│   └── Analise.php         retornos, volatilidade, drawdown, Sharpe, beta, médias, IFR, MACD, Bollinger, correlação
├── dto/                    Usuario, Ativo, Posicao
├── cron/atualizar_cotacoes.php, cron/importar_cotahist.php
├── config/config.php       credenciais (bloqueado para acesso web pelo .htaccess)
├── database/schema.sql
├── css/ (sb-admin-2 do REURB + painel.css), js/ (sb-admin-2 + painel.js), vendor/, img/
```

## Diferenças em relação ao código do REURB

A organização é a mesma, mas algumas partes foram reforçadas:

- Consultas com PDO e parâmetros preparados no lugar de SQL concatenado (evita SQL injection).
- Senhas gravadas com `password_hash()`, não em texto puro.
- Formulários com token CSRF; exclusões feitas por POST com confirmação em modal.
- Sessão com cookie `HttpOnly`/`SameSite` e troca do ID de sessão no login.
- `.htaccess` bloqueia o acesso direto a `config/`, `actions/`, `dto/`, `database/` e `cron/`.
  Em **Nginx**, crie regras `location` equivalentes (`deny all;`) para essas pastas.

## Observações

- Os indicadores (médias, IFR, MACD, Sharpe etc.) são calculados automaticamente e têm caráter
  apenas informativo. Não constituem recomendação de investimento.
- A taxa livre de risco usada no Sharpe fica em `analise.taxa_livre_risco_anual`; atualize-a
  conforme o CDI/Selic vigente.
- Os dados de mercado são fornecidos pela brapi.dev e estão sujeitos aos termos de uso dela.
