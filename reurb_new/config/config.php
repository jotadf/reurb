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

    // Prefixo usado para abrir as fotos (campos foto_*).
    // Se no banco estiver gravado só o nome do arquivo, informe a pasta, ex.: '/reurb/uploads/'
    // Se já estiver gravada a URL completa, deixe ''.
    'fotos_base_url' => '',

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

    // true = mostra na tela o motivo exato da falha no login (usuário não
    // encontrado, inativo, formato da senha...). Use só para diagnosticar e
    // depois volte para false.
    'login_diagnostico' => false,

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
