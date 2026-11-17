<?php
/**
 * Atualização automática (agende no cron do servidor ou no cPanel).
 * Exemplo, a cada 30 minutos em dias úteis, das 10h às 18h:
 *   0,30 10-18 * * 1-5  php /caminho/painel-b3/cron/atualizar_cotacoes.php
 * Uma vez por dia, depois do fechamento, use "--historico" para gravar o pregão:
 *   30 19 * * 1-5  php /caminho/painel-b3/cron/atualizar_cotacoes.php --historico
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Somente pela linha de comando.');
}
require_once __DIR__ . '/../actions/ManterAtivo.php';
require_once __DIR__ . '/../actions/ManterCarteira.php';
require_once __DIR__ . '/../actions/ServicoMercado.php';

config();
$servico = new ServicoMercado();
$tickers = (new ManterAtivo())->tickers();
$tickers[] = config('analise.indice_referencia');
// inclui também os ativos que estão nas carteiras dos usuários
foreach (Model::conexao()->query('SELECT DISTINCT ticker FROM carteira_posicao')->fetchAll(PDO::FETCH_COLUMN) as $t) {
    $tickers[] = $t;
}
$tickers = array_values(array_unique($tickers));

$inicio = microtime(true);
if (in_array('--historico', $argv, true)) {
    $servico->atualizaHistorico($tickers, config('brapi.range_maximo') ?: '3mo');
    $tipo = 'histórico';
} else {
    $servico->cotacoes($tickers, true);
    $tipo = 'cotações';
}
printf("[%s] %s de %d ativo(s) em %.1fs\n", date('Y-m-d H:i:s'), $tipo, count($tickers), microtime(true) - $inicio);
foreach ($servico->erros as $t => $m) {
    fwrite(STDERR, "  erro em $t: $m\n");
}
