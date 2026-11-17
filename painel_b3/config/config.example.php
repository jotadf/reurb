<?php
/**
 * Configuração do Painel B3.
 * Copie este arquivo para config.php e ajuste os valores.
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
        // Maior período de histórico que o seu plano libera na API.
        // Gratuito: '3mo'. Planos pagos: '1y', '5y', '10y'...
        // Períodos maiores que este são montados com o histórico acumulado no banco.
        'range_maximo' => '3mo',
    ],

    // ---------------- Séries Históricas da B3 (COTAHIST) ----------------
    // Arquivos oficiais e gratuitos com o histórico diário. Importados por cron/importar_cotahist.php.
    'b3' => [
        'url_base'      => getenv('PAINELB3_B3_URL') ?: 'https://bvmf.bmfbovespa.com.br/InstDados/SerHist/',
        // Se o download falhar por erro de certificado SSL no servidor da B3, troque para false
        // (ou baixe o arquivo manualmente e use a opção --arquivo).
        'verificar_ssl' => true,
    ],

    // ---------------- Cache (economiza requisições da API) ----------------
    'cache' => [
        // Idade máxima da cotação durante o pregão. O plano gratuito da brapi atualiza
        // os dados a cada 30 min (planos pagos: 15 min ou menos), então um cache menor
        // que isso não traz preço mais novo e só consome requisições.
        'cotacao_minutos'             => 30,
        // Fora do pregão o preço não muda, então o cache pode ser bem mais longo.
        'cotacao_minutos_fora_pregao' => 120,
        'historico_minutos'           => 360,  // idade máxima do histórico diário
    ],

    // Horário do pregão da B3 (horário de Brasília), usado para decidir o cache.
    'pregao' => [
        'dias'   => [1, 2, 3, 4, 5],   // 1 = segunda ... 5 = sexta
        'inicio' => '10:00',
        'fim'    => '18:30',
    ],

    // ---------------- Análises ----------------
    'analise' => [
        // Períodos oferecidos na tela. Os que passam de brapi.range_maximo usam o histórico
        // guardado no banco (importado da B3 com cron/importar_cotahist.php e acumulado a cada dia).
        'ranges' => ['1mo' => '1 mês', '3mo' => '3 meses', '6mo' => '6 meses', '1y' => '1 ano', '2y' => '2 anos', '5y' => '5 anos', '10y' => '10 anos'],
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
