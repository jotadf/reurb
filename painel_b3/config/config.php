<?php
/**
 * Configuração do Painel B3.
 * Ajuste os valores abaixo (banco de dados e token da brapi).
 * Variáveis de ambiente (PAINELB3_*) têm prioridade sobre os valores abaixo.
 */
return [
    // ---------------- Banco de dados MySQL / MariaDB ----------------
    'db' => [
        'host'     => getenv('PAINELB3_DB_HOST') ?: 'localhost',
        'port'     => getenv('PAINELB3_DB_PORT') ?: '3306',
        'database' => getenv('PAINELB3_DB_NAME') ?: 'painel_b3',
        'username' => getenv('PAINELB3_DB_USER') ?: 'painel_b3',
        'password' => getenv('PAINELB3_DB_PASS') ?: 'troque_esta_senha',
        'charset'  => 'utf8mb4',
    ],

    // ---------------- API brapi.dev ----------------
    'brapi' => [
        // Gere o token em https://brapi.dev/dashboard
        // Sem token, só PETR4, VALE3, MGLU3 e ITUB4 respondem.
        'token'    => getenv('PAINELB3_BRAPI_TOKEN') ?: '',
        'base_url' => getenv('PAINELB3_BRAPI_URL') ?: 'https://brapi.dev/api',
        'timeout'  => 15,
        // Plano gratuito: 1 ativo por requisição. Em planos pagos pode ser true.
        'varios_ativos_por_requisicao' => false,
    ],

    // ---------------- Cache (economiza requisições da API) ----------------
    'cache' => [
        'cotacao_minutos'   => 15,   // idade máxima da cotação antes de consultar a API de novo
        'historico_minutos' => 360,  // idade máxima do histórico diário
    ],

    // ---------------- Análises ----------------
    'analise' => [
        // Intervalos oferecidos na tela. Remova os que o seu plano da brapi não permite.
        'ranges' => ['1mo' => '1 mês', '3mo' => '3 meses', '6mo' => '6 meses', '1y' => '1 ano', '2y' => '2 anos', '5y' => '5 anos'],
        'range_padrao' => '3mo',
        // Taxa livre de risco anual usada no índice de Sharpe (ex.: CDI). Atualize periodicamente.
        'taxa_livre_risco_anual' => 0.15,
        // Índice de referência para o beta.
        'indice_referencia' => '^BVSP',
    ],

    'app' => [
        'nome'     => 'Painel B3',
        'timezone' => 'America/Sao_Paulo',
        'rodape'   => 'Copyright &copy; JotaDF 2026',
    ],
];
