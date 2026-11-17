<?php
require_once './verifica_login.php';
require_once './actions/ManterAtivo.php';
require_once './actions/ServicoMercado.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireciona('ativos.php');
csrf_valida();
exige_perfil(1);
$volta = volta_segura($_POST['volta'] ?? '', 'ativos.php');

try {
    $m = new ManterAtivo();
    $m->salvar($_POST);
    $ticker = normaliza_ticker($_POST['ticker']);
    // Confere o código na API e aproveita para preencher o nome
    $servico = new ServicoMercado();
    $cot = $servico->cotacao($ticker, true);
    if ($cot) {
        $m->completarNome($ticker, $cot['nome_longo'] ?: $cot['nome_curto']);
        flash('success', $ticker . ' salvo na lista de ativos monitorados.');
    } else {
        flash('warning', $ticker . ' foi salvo, mas a API não retornou cotação (' . ($servico->erros[$ticker] ?? 'sem resposta') . '). Confira o código.');
    }
} catch (InvalidArgumentException $e) {
    flash('danger', $e->getMessage());
}
redireciona($volta);
