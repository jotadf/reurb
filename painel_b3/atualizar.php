<?php
/** Força a busca de dados novos na API (botões "Atualizar"). */
require_once './verifica_login.php';
require_once './actions/ManterAtivo.php';
require_once './actions/ServicoMercado.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireciona('index.php');
csrf_valida();
$volta = volta_segura($_POST['volta'] ?? '');

// Evita cliques repetidos gastando a cota da API
if (isset($_SESSION['ultima_atualizacao']) && time() - $_SESSION['ultima_atualizacao'] < 30) {
    flash('info', 'Aguarde alguns segundos antes de atualizar novamente.');
    redireciona($volta);
}
$_SESSION['ultima_atualizacao'] = time();

$servico = new ServicoMercado();
if (($_POST['escopo'] ?? '') === 'ativo') {
    $ticker = normaliza_ticker($_POST['t'] ?? '');
    $range = $_POST['r'] ?? config('analise.range_padrao');
    if ($ticker) {
        $servico->historico($ticker, $range, true);
    }
} else {
    $tickers = (new ManterAtivo())->tickers();
    $tickers[] = config('analise.indice_referencia');
    $servico->cotacoes($tickers, true);
}
if ($servico->erros) {
    flash('warning', 'Alguns ativos não foram atualizados: ' . implode(', ', array_keys($servico->erros)) . '.');
} else {
    flash('success', 'Dados atualizados.');
}
redireciona($volta);
