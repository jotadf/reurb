<?php
/**
 * Configurações gerais do CRUD REURB.
 * Ajuste os dados de conexão e os caminhos conforme o seu servidor.
 */

return [
    // ---------------------------------------------------------------
    // Banco de dados (MySQL / MariaDB)
    // ---------------------------------------------------------------
    'db' => [
        'host'    => getenv('REURB_DB_HOST') ?: 'localhost',
        'porta'   => getenv('REURB_DB_PORT') ?: '3306',
        'banco'   => getenv('REURB_DB_NAME') ?: 'reurb',
        'usuario' => getenv('REURB_DB_USER') ?: 'root',
        'senha'   => getenv('REURB_DB_PASS') !== false ? getenv('REURB_DB_PASS') : '',
        'charset' => 'utf8mb4',
    ],

    // ---------------------------------------------------------------
    // Identidade da aplicação
    // ---------------------------------------------------------------
    'app_nome'        => 'REURB',
    'nome_ocupacao'   => 'Ocupação Dorothy Stang',   // aparece no título das fichas
    'rodape'          => 'Copyright &copy; JotaDF ' . date('Y'),
    'registros_pagina'=> 25,
    'fuso_horario'    => 'America/Sao_Paulo',

    // ---------------------------------------------------------------
    // Arquivos do tema SB Admin 2.
    // Se o seu sistema já tem a pasta do tema (css/, js/, vendor/),
    // troque pelo caminho local, ex.: 'tema_base' => '/reurb/'
    // ---------------------------------------------------------------
    'tema_base' => getenv('REURB_TEMA') ?: 'https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/',

    // Endereço da pasta do CRUD no site, ex.: '/reurb_crud/'.
    // Deixe '' para descobrir sozinho. Só preencha se os links do menu
    // ficarem errados quando ele for incluído em páginas de outras pastas.
    'crud_url' => '',

    // Endereço (no site) da pasta das fotos: ~/www/arquivos -> '/arquivos/'.
    // Cada tela completa com a sua subpasta ('pasta_fotos' em config/tabelas.php):
    //   domicilio/, socio_juridico/, caracterizacao/
    // Valores já gravados como URL completa (http://...) ou caminho com '/' no
    // início são usados como estão.
    'fotos_base_url' => '/arquivos/',

    // ---------------------------------------------------------------
    // Sessão / login.
    // Se o sistema atual já tem login próprio, informe aqui as chaves de
    // sessão que ele usa, e o CRUD reaproveita o usuário logado.
    // ---------------------------------------------------------------
    'sessao_id_usuario'   => 'usuario_id',
    'sessao_nome_usuario' => 'usuario_nome',
    'pagina_login'        => 'login.php',

    // Formato da coluna usuario.senha, usado para gravar E para conferir no login.
    // 'texto' = sem criptografia (igual ao sistema atual).
    // 'auto'  = reconhece o formato de cada senha gravada e grava no mesmo formato.
    // Outros: 'password_hash' | 'md5' | 'sha1' | 'sha256' | 'sha512'
    'senha_formato' => 'texto',

    // true = ao entrar, converte a senha para password_hash (mais seguro).
    // Só ligue se o sistema atual também aceitar password_hash, senão o
    // usuário deixa de conseguir entrar nele.
    'converter_senha_no_login' => false,

    // Mostra na tela o motivo da falha no login (usuário não encontrado,
    // inativo, senha diferente, erro de conexão...). A senha nunca é exibida.
    // 'local' = só quando acessado do próprio servidor (localhost)
    // true    = sempre (use só para diagnosticar e depois volte)
    // false   = nunca (recomendado em produção)
    'login_diagnostico' => 'local',

    // Links do menu lateral que pertencem a telas que já existem no seu sistema.
    // Troque '#' pelo arquivo correspondente.
    'links_externos' => [
        'importar_dados'      => '#',
        'gerenciar_imagens'   => '#',
        'gerar_relatorio'     => '#',
        'selagens_sem_socio'  => '#',
        'caract_sem_selagem'  => '#',
    ],
];
