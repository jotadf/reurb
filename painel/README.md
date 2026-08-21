# Painel de Acompanhamento — Selagem / REURB

Aplicação PHP + MySQL + Bootstrap 4.6 + jQuery + Chart.js.

## Estrutura
- `index.php`: painel responsivo.
- `api/dashboard.php`: consultas e indicadores em JSON.
- `db.php`: conexão PDO/MySQL.
- `config.php`: credenciais do banco.
- `assets/css/painel.css`: estilos.
- `assets/js/painel.js`: gráficos e filtros.

## Banco esperado
Schema `reurb` com as tabelas do arquivo SQL fornecido:
- `selagem_lotes_import`
- `domicilios_import`
- `caracterizacao_vulnerabilidade_import`
- `cadastro_sociojuridico_import`

## Regra de conciliação
O indicador de integração usa o código do selo:
- domicílio: `domicilios_import.numero_selo`
- sociojurídico: `cadastro_sociojuridico_import.codigo_selo`

A comparação usa `TRIM(UPPER(...))`, de modo que diferenças de espaços e caixa não impeçam o vínculo.

## Instalação
1. Crie/importa o schema `reurb` usando o SQL fornecido.
2. Copie a pasta para o Apache/Nginx + PHP.
3. Edite `config.php` com host, porta, usuário e senha.
4. Garanta PHP 7.4+ com extensão PDO_MySQL.
5. Acesse `index.php` pelo navegador.

## Observação
Os indicadores de conciliação usam códigos distintos (`DISTINCT`) para não inflar os números em caso de duplicidade de selo.
