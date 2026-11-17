<?php
require_once './verifica_login.php';
require_once './actions/ManterAtivo.php';
require_once './actions/ManterCarteira.php';
require_once './actions/ServicoMercado.php';

$mAtivo   = new ManterAtivo();
$servico  = new ServicoMercado();
$ativos   = $mAtivo->listar();
$indice   = config('analise.indice_referencia');
$tickers  = array_column($ativos, 'ticker');
$cotacoes = $servico->cotacoes(array_merge([$indice], $tickers));
$cotIndice = $cotacoes[$indice] ?? null;

// Destaques do dia entre os ativos monitorados
$comVariacao = array_filter($tickers, function ($t) use ($cotacoes) {
    return isset($cotacoes[$t]['variacao_pct']);
});
usort($comVariacao, function ($a, $b) use ($cotacoes) {
    return $cotacoes[$b]['variacao_pct'] <=> $cotacoes[$a]['variacao_pct'];
});
$maiorAlta  = $comVariacao ? $comVariacao[0] : null;
$maiorBaixa = $comVariacao ? end($comVariacao) : null;
$emAlta  = count(array_filter($comVariacao, function ($t) use ($cotacoes) { return $cotacoes[$t]['variacao_pct'] > 0; }));
$emBaixa = count(array_filter($comVariacao, function ($t) use ($cotacoes) { return $cotacoes[$t]['variacao_pct'] < 0; }));

// Resumo da carteira do usuário
$posicoes = (new ManterCarteira())->listar($usuario_logado->id);
$cotCarteira = $posicoes ? $servico->cotacoes(array_column($posicoes, 'ticker')) : [];
$patrimonio = $custo = $varDia = 0.0;
foreach ($posicoes as $p) {
    $c = $cotCarteira[$p['ticker']] ?? null;
    $preco = $c['preco'] ?? $p['preco_medio'];
    $patrimonio += $p['quantidade'] * $preco;
    $custo      += $p['quantidade'] * $p['preco_medio'];
    $varDia     += $p['quantidade'] * (float) ($c['variacao'] ?? 0);
}

// Dados do gráfico de variação do dia
$grafico = [];
foreach ($comVariacao as $t) {
    $grafico[] = ['t' => $t, 'v' => round((float) $cotacoes[$t]['variacao_pct'], 2)];
}

$requisicoes = $servico->requisicoesNoMes();
$ultima = $servico->ultimaAtualizacao();

$titulo = 'Visão geral';
include './layout_topo.php';
?>
<!-- Page Heading -->
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Visão geral do mercado</h1>
    <form method="post" action="atualizar.php" class="mt-2 mt-sm-0" data-carregando>
        <?= csrf_campo() ?>
        <input type="hidden" name="escopo" value="lista">
        <input type="hidden" name="volta" value="index.php">
        <span class="small text-gray-600 mr-2">Atualizado em <?= data_br($ultima, true) ?></span>
        <button type="submit" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-sync-alt fa-sm text-white-50 mr-1"></i> Atualizar cotações</button>
    </form>
</div>

<?= alerta_erros_api($servico->erros) ?>

<!-- Indicadores -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?= e(nome_ticker($indice)) ?></div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800 num"><?= $cotIndice ? numero($cotIndice['preco'], 0) . ' pts' : '—' ?></div>
                        <div class="small font-weight-bold <?= cor($cotIndice['variacao_pct'] ?? null) ?>"><?= pct($cotIndice['variacao_pct'] ?? null) ?> no dia</div>
                    </div>
                    <div class="col-auto"><i class="fas fa-landmark fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Ativos monitorados</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($tickers) ?></div>
                        <div class="small"><span class="text-success font-weight-bold"><i class="fas fa-caret-up"></i> <?= $emAlta ?> em alta</span>
                            &nbsp;<span class="text-danger font-weight-bold"><i class="fas fa-caret-down"></i> <?= $emBaixa ?> em baixa</span></div>
                    </div>
                    <div class="col-auto"><i class="fas fa-list fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Maior alta do dia</div>
                        <?php if ($maiorAlta) { ?>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><a class="text-gray-800" href="ativo.php?t=<?= e($maiorAlta) ?>"><?= e($maiorAlta) ?></a></div>
                            <div class="small font-weight-bold <?= cor($cotacoes[$maiorAlta]['variacao_pct']) ?>"><?= pct($cotacoes[$maiorAlta]['variacao_pct']) ?> &middot; <?= moeda($cotacoes[$maiorAlta]['preco']) ?></div>
                        <?php } else { ?>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">—</div>
                        <?php } ?>
                    </div>
                    <div class="col-auto"><i class="fas fa-arrow-up fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-danger shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Maior baixa do dia</div>
                        <?php if ($maiorBaixa && $maiorBaixa !== $maiorAlta) { ?>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><a class="text-gray-800" href="ativo.php?t=<?= e($maiorBaixa) ?>"><?= e($maiorBaixa) ?></a></div>
                            <div class="small font-weight-bold <?= cor($cotacoes[$maiorBaixa]['variacao_pct']) ?>"><?= pct($cotacoes[$maiorBaixa]['variacao_pct']) ?> &middot; <?= moeda($cotacoes[$maiorBaixa]['preco']) ?></div>
                        <?php } else { ?>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">—</div>
                        <?php } ?>
                    </div>
                    <div class="col-auto"><i class="fas fa-arrow-down fa-2x text-gray-300"></i></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tabela de cotações -->
    <div class="col-xl-8 col-lg-7">
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-table', 'Cotações dos ativos monitorados',
                $usuario_logado->perfil <= 1 ? '<a href="ativos.php" class="btn btn-outline-light btn-sm" title="Gerenciar lista"><i class="fa fa-plus-circle"></i></a>' : '') ?>
            <div class="card-body">
                <?php if (!$ativos) { ?>
                    <p class="text-gray-600 mb-0">Nenhum ativo cadastrado. <a href="ativos.php">Cadastre os ativos que deseja acompanhar.</a></p>
                <?php } else { ?>
                <table id="cotacoes" class="table table-sm table-striped table-bordered table-hover dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Ativo</th>
                            <th class="num">Preço</th>
                            <th class="num">Var. dia</th>
                            <th class="num">Mín / Máx dia</th>
                            <th class="num">Volume</th>
                            <th>Faixa 52 semanas</th>
                            <th class="num">P/L</th>
                            <th>Cotação de</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ativos as $a) {
                        $c = $cotacoes[$a['ticker']] ?? null;
                        $pos52 = null;
                        if ($c && $c['maxima_52s'] > $c['minima_52s']) {
                            $pos52 = max(0, min(100, ($c['preco'] - $c['minima_52s']) / ($c['maxima_52s'] - $c['minima_52s']) * 100));
                        } ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?= logo_html($a['ticker'], $c['logo_url'] ?? null, 'mr-2') ?>
                                    <div>
                                        <a href="ativo.php?t=<?= e($a['ticker']) ?>" class="ticker"><?= e($a['ticker']) ?></a>
                                        <div class="small text-gray-500"><?= e($a['nome'] ?: ($c['nome_curto'] ?? '')) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="num font-weight-bold" data-order="<?= (float) ($c['preco'] ?? 0) ?>"><?= moeda($c['preco'] ?? null) ?></td>
                            <td class="num font-weight-bold <?= cor($c['variacao_pct'] ?? null) ?>" data-order="<?= (float) ($c['variacao_pct'] ?? 0) ?>"><?= pct($c['variacao_pct'] ?? null) ?></td>
                            <td class="num"><?= numero($c['minima_dia'] ?? null) ?> / <?= numero($c['maxima_dia'] ?? null) ?></td>
                            <td class="num" data-order="<?= (int) ($c['volume'] ?? 0) ?>"><?= abreviado($c['volume'] ?? null) ?></td>
                            <td data-order="<?= $pos52 === null ? -1 : round($pos52) ?>">
                                <?php if ($pos52 !== null) { ?>
                                    <div class="d-flex align-items-center small text-gray-600">
                                        <span class="num mr-2"><?= numero($c['minima_52s']) ?></span>
                                        <div class="faixa52 flex-fill" title="<?= numero($pos52, 0) ?>% do caminho entre a mínima e a máxima"><span class="marcador" style="left: <?= round($pos52, 1) ?>%"></span></div>
                                        <span class="num ml-2"><?= numero($c['maxima_52s']) ?></span>
                                    </div>
                                <?php } else { echo '<span class="text-gray-500">—</span>'; } ?>
                            </td>
                            <td class="num"><?= numero($c['pl'] ?? null) ?></td>
                            <td class="small text-gray-600" data-order="<?= e($c['mercado_em'] ?? '') ?>"><?= data_br($c['mercado_em'] ?? null, true) ?></td>
                            <td class="text-center"><a href="ativo.php?t=<?= e($a['ticker']) ?>" class="btn btn-sm btn-outline-primary" title="Analisar"><i class="fas fa-chart-area"></i></a></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-lg-5">
        <!-- Gráfico de variação -->
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-chart-bar', 'Variação do dia') ?>
            <div class="card-body">
                <?php if ($grafico) { ?>
                    <div class="grafico" style="height: <?= max(160, count($grafico) * 30 + 40) ?>px"><canvas id="graficoVariacao"></canvas></div>
                <?php } else { ?>
                    <p class="text-gray-600 mb-0">Sem cotações disponíveis.</p>
                <?php } ?>
            </div>
        </div>

        <!-- Resumo da carteira -->
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-briefcase', 'Minha carteira', '<a href="carteira.php" class="btn btn-outline-light btn-sm">Abrir</a>') ?>
            <div class="card-body">
                <?php if (!$posicoes) { ?>
                    <p class="text-gray-600 mb-0">Você ainda não cadastrou posições. <a href="carteira.php">Montar carteira</a>.</p>
                <?php } else {
                    $resultado = $patrimonio - $custo; ?>
                    <div class="row text-center">
                        <div class="col-6 mb-3">
                            <div class="text-xs text-uppercase text-gray-500 font-weight-bold">Patrimônio</div>
                            <div class="kpi-valor num"><?= moeda($patrimonio) ?></div>
                        </div>
                        <div class="col-6 mb-3">
                            <div class="text-xs text-uppercase text-gray-500 font-weight-bold">Resultado</div>
                            <div class="kpi-valor num <?= cor($resultado) ?>"><?= $custo > 0 ? pct($resultado / $custo, 2, true) : '—' ?></div>
                            <div class="small num <?= cor($resultado) ?>"><?= moeda($resultado) ?></div>
                        </div>
                        <div class="col-12">
                            <div class="text-xs text-uppercase text-gray-500 font-weight-bold">Variação hoje</div>
                            <div class="font-weight-bold num <?= cor($varDia) ?>"><?= moeda($varDia) ?>
                                (<?= ($patrimonio - $varDia) > 0 ? pct($varDia / ($patrimonio - $varDia), 2, true) : '—' ?>)</div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>

        <div class="small text-gray-600 mb-4">
            <i class="fas fa-info-circle mr-1"></i>
            Requisições à brapi neste mês: <strong><?= numero($requisicoes, 0) ?></strong>.
            Cotações ficam em cache por <?= (int) config('cache.cotacao_minutos') ?> min.
        </div>
    </div>
</div>

<?php include './layout_base.php'; ?>
<script>
$(function () {
    $('#cotacoes').DataTable({ order: [], pageLength: 25, columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }, { responsivePriority: 3, targets: [1, 2] }, { orderable: false, targets: -1 }] });

    var dados = <?= json_encode($grafico) ?>;
    if (dados.length && document.getElementById('graficoVariacao')) {
        new Chart(document.getElementById('graficoVariacao'), {
            type: 'bar',
            data: {
                labels: dados.map(function (d) { return d.t; }),
                datasets: [{
                    data: dados.map(function (d) { return d.v; }),
                    backgroundColor: dados.map(function (d) { return d.v >= 0 ? Painel.cores.success : Painel.cores.danger; }),
                    borderRadius: 3, barThickness: 16
                }]
            },
            options: {
                indexAxis: 'y',
                plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return Painel.pct(c.parsed.x); } } } },
                scales: {
                    x: { grid: { color: '#eaecf4' }, ticks: { callback: function (v) { return v + '%'; } } },
                    y: { grid: { display: false }, ticks: { font: { weight: 'bold' } } }
                },
                onClick: function (e, el) { if (el.length) { location.href = 'ativo.php?t=' + encodeURIComponent(dados[el[0].index].t); } }
            }
        });
    }
});
</script>
</body>
</html>
