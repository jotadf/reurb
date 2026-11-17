<?php
require_once './verifica_login.php';
require_once './actions/ManterAtivo.php';
require_once './actions/ServicoMercado.php';
require_once './actions/Analise.php';

$mAtivo  = new ManterAtivo();
$servico = new ServicoMercado();
$lista   = $mAtivo->listar();
$ranges  = config('analise.ranges');
$indice  = config('analise.indice_referencia');

$ticker = isset($_GET['t']) && !is_array($_GET['t']) ? normaliza_ticker($_GET['t']) : null;
if (!$ticker) {
    $ticker = $lista ? $lista[0]['ticker'] : 'PETR4';
}
$range = isset($_GET['r']) && isset($ranges[$_GET['r']]) ? $_GET['r'] : config('analise.range_padrao');

$cot  = $servico->cotacao($ticker);
$hist = $servico->historico($ticker, $range);
$naLista = in_array($ticker, array_column($lista, 'ticker'), true);

// ---------------- Séries ----------------
$datas = $abert = $max = $min = $fech = $ajust = $vol = [];
foreach ($hist as $h) {
    $datas[] = $h['data'];
    $abert[] = $h['abertura'] !== null ? (float) $h['abertura'] : (float) $h['fechamento'];
    $max[]   = $h['maxima'] !== null ? (float) $h['maxima'] : (float) $h['fechamento'];
    $min[]   = $h['minima'] !== null ? (float) $h['minima'] : (float) $h['fechamento'];
    $fech[]  = (float) $h['fechamento'];
    $ajust[] = (float) ($h['fechamento_ajustado'] ?? $h['fechamento']);
    $vol[]   = (int) $h['volume'];
}
$temDados = count($fech) >= 2;

// ---------------- Indicadores ----------------
$ret      = Analise::retornos($ajust);
$retTotal = Analise::retornoTotal($ajust);
$volAnual = Analise::volatilidadeAnual($ret);
$mdd      = Analise::maxDrawdown($ajust);
$sharpe   = Analise::sharpe($ret, (float) config('analise.taxa_livre_risco_anual'));
$mms20    = Analise::mms($fech, 20);
$mms50    = Analise::mms($fech, 50);
$boll     = Analise::bollinger($fech, 20, 2);
$ifr      = Analise::ifr($fech, 14);
$macd     = Analise::macd($fech);
$dd       = Analise::serieDrawdown($ajust);

// Beta contra o índice de referência (datas alinhadas)
$beta = null;
if ($ticker !== $indice && $temDados) {
    $histIdx = $servico->historico($indice, $range);
    $sA = array_combine($datas, $ajust);
    $sI = [];
    foreach ($histIdx as $h) {
        $sI[$h['data']] = (float) ($h['fechamento_ajustado'] ?? $h['fechamento']);
    }
    if ($sI) {
        $al = Analise::alinhar(['a' => $sA, 'i' => $sI]);
        $beta = Analise::beta(Analise::retornos($al['series']['a']), Analise::retornos($al['series']['i']));
    }
}

$ultimo = function (array $s) {
    for ($i = count($s) - 1; $i >= 0; $i--) {
        if ($s[$i] !== null) return $s[$i];
    }
    return null;
};
$precoAtual = $cot['preco'] ?? ($fech ? end($fech) : null);
$ifrAtual   = $ultimo($ifr);
$mms20Atual = $ultimo($mms20);
$mms50Atual = $ultimo($mms50);
$macdAtual  = $ultimo($macd['macd']);
$sinalAtual = $ultimo($macd['sinal']);
$prov12m    = $servico->proventos12m($ticker);
$dy12m      = ($prov12m && $precoAtual) ? $prov12m / $precoAtual : null;
$dividendos = $servico->dividendos($ticker, date('Y-m-d', strtotime('-24 months')));

// ---------------- Leitura técnica ----------------
$sinais = [];
if ($precoAtual && $mms20Atual) {
    $sinais[] = $precoAtual >= $mms20Atual
        ? ['success', 'Preço acima da média de 20 dias', 'tendência de curto prazo positiva']
        : ['danger', 'Preço abaixo da média de 20 dias', 'tendência de curto prazo negativa'];
}
if ($mms20Atual && $mms50Atual) {
    $sinais[] = $mms20Atual >= $mms50Atual
        ? ['success', 'Média de 20 acima da de 50', 'médias alinhadas para alta']
        : ['danger', 'Média de 20 abaixo da de 50', 'médias alinhadas para baixa'];
}
if ($ifrAtual !== null) {
    if ($ifrAtual >= 70)      $sinais[] = ['warning', 'IFR em ' . numero($ifrAtual, 0), 'região de sobrecompra (acima de 70)'];
    elseif ($ifrAtual <= 30)  $sinais[] = ['info', 'IFR em ' . numero($ifrAtual, 0), 'região de sobrevenda (abaixo de 30)'];
    else                      $sinais[] = ['secondary', 'IFR em ' . numero($ifrAtual, 0), 'zona neutra (entre 30 e 70)'];
}
if ($macdAtual !== null && $sinalAtual !== null) {
    $sinais[] = $macdAtual >= $sinalAtual
        ? ['success', 'MACD acima da linha de sinal', 'momento comprador']
        : ['danger', 'MACD abaixo da linha de sinal', 'momento vendedor'];
}

// ---------------- Dados para os gráficos ----------------
$r4 = function ($v) { return $v === null ? null : round($v, 4); };
$jsDados = [
    'ticker' => $ticker,
    'datas'  => $datas,
    'o' => $abert, 'h' => $max, 'l' => $min, 'c' => $fech, 'v' => $vol,
    'mms20' => array_map($r4, $mms20), 'mms50' => array_map($r4, $mms50),
    'bsup' => array_map($r4, $boll['superior']), 'binf' => array_map($r4, $boll['inferior']),
    'ifr' => array_map($r4, $ifr),
    'macd' => array_map($r4, $macd['macd']), 'sinal' => array_map($r4, $macd['sinal']), 'hist' => array_map($r4, $macd['histograma']),
    'dd' => array_map(function ($v) { return round($v * 100, 2); }, $dd),
];

$titulo = $ticker;
include './layout_topo.php';
?>
<!-- Cabeçalho do ativo -->
<div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
    <div class="d-flex align-items-center mb-2">
        <?= logo_html($ticker, $cot['logo_url'] ?? null, 'logo-ativo-lg mr-3') ?>
        <div>
            <h1 class="h3 mb-0 text-gray-800 font-weight-bold"><?= e(nome_ticker($ticker)) ?>
                <small class="text-gray-500 font-weight-normal"><?= e($cot['nome_longo'] ?? ($cot['nome_curto'] ?? '')) ?></small></h1>
            <div>
                <span class="h4 font-weight-bold text-gray-800 num"><?= $ticker === $indice ? numero($precoAtual, 0) . ' pts' : moeda($precoAtual) ?></span>
                <span class="h5 font-weight-bold ml-2 num <?= cor($cot['variacao_pct'] ?? null) ?>"><?= pct($cot['variacao_pct'] ?? null) ?></span>
                <span class="small text-gray-500 ml-2">cotação de <?= data_br($cot['mercado_em'] ?? null, true) ?></span>
            </div>
        </div>
    </div>
    <div class="d-flex flex-wrap align-items-center mb-2">
        <form method="get" class="form-inline mr-2">
            <input type="hidden" name="t" value="<?= e($ticker) ?>">
            <div class="btn-group btn-group-sm" role="group" aria-label="Período">
                <?php foreach ($ranges as $k => $rot) { ?>
                    <button type="submit" name="r" value="<?= e($k) ?>" class="btn <?= $k === $range ? 'btn-primary' : 'btn-outline-primary' ?>"><?= e(str_replace([' meses', ' mês', ' anos', ' ano'], ['M', 'M', 'A', 'A'], $rot)) ?></button>
                <?php } ?>
            </div>
        </form>
        <form method="post" action="atualizar.php" class="mr-2" data-carregando>
            <?= csrf_campo() ?>
            <input type="hidden" name="escopo" value="ativo">
            <input type="hidden" name="t" value="<?= e($ticker) ?>">
            <input type="hidden" name="r" value="<?= e($range) ?>">
            <input type="hidden" name="volta" value="ativo.php?t=<?= e(rawurlencode($ticker)) ?>&r=<?= e($range) ?>">
            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Buscar dados novos na API"><i class="fas fa-sync-alt"></i></button>
        </form>
        <a href="carteira.php?novo=<?= e(rawurlencode($ticker)) ?>" class="btn btn-sm btn-outline-success mr-2" title="Adicionar à carteira"><i class="fas fa-briefcase"></i></a>
        <?php if (!$naLista && $usuario_logado->perfil <= 1) { ?>
            <form method="post" action="save_ativo.php">
                <?= csrf_campo() ?>
                <input type="hidden" name="ticker" value="<?= e($ticker) ?>">
                <input type="hidden" name="ativo" value="1">
                <input type="hidden" name="volta" value="ativo.php?t=<?= e(rawurlencode($ticker)) ?>">
                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-plus mr-1"></i>Monitorar</button>
            </form>
        <?php } ?>
    </div>
</div>

<?= alerta_erros_api($servico->erros) ?>

<?php if (!$temDados) { ?>
    <div class="card border-left-warning shadow mb-4"><div class="card-body">
        Não há histórico de preços de <strong><?= e($ticker) ?></strong> para o período escolhido.
        Confira o código de negociação, o token da brapi e se o seu plano permite este período.
    </div></div>
<?php } else { ?>

<!-- Indicadores de risco e retorno -->
<div class="row">
    <?php
    $kpis = [
        ['Retorno no período', pct($retTotal, 2, true), cor($retTotal), 'fa-percentage', 'primary', 'Variação do preço ajustado por proventos entre o primeiro e o último pregão do período.'],
        ['Volatilidade anual', pct($volAnual, 1, true, false), 'text-gray-800', 'fa-wave-square', 'info', 'Desvio-padrão dos retornos diários × √252.'],
        ['Queda máxima', pct($mdd, 1, true), 'text-danger', 'fa-level-down-alt', 'danger', 'Maior queda de um topo até um fundo dentro do período (max drawdown).'],
        ['Índice de Sharpe', numero($sharpe, 2), cor($sharpe), 'fa-balance-scale', 'success', 'Retorno acima da taxa livre de risco (' . pct(config('analise.taxa_livre_risco_anual'), 2, true, false) . ' a.a.) por unidade de risco.'],
        ['Beta vs ' . nome_ticker($indice), numero($beta, 2), 'text-gray-800', 'fa-project-diagram', 'warning', 'Sensibilidade do ativo aos movimentos do índice. 1 = acompanha o índice.'],
        ['Dividend yield 12m', pct($dy12m, 2, true, false), 'text-gray-800', 'fa-hand-holding-usd', 'secondary', 'Proventos com data-com nos últimos 12 meses ÷ preço atual (com base no que a API retornou).'],
    ];
    foreach ($kpis as $k) { ?>
        <div class="col-xl-2 col-md-4 col-6 mb-4">
            <div class="card border-left-<?= $k[4] ?> shadow h-100 py-2" title="<?= e($k[5]) ?>" data-toggle="tooltip">
                <div class="card-body py-2">
                    <div class="text-xs font-weight-bold text-<?= $k[4] ?> text-uppercase mb-1"><?= e($k[0]) ?></div>
                    <div class="h5 mb-0 font-weight-bold num <?= $k[2] ?>"><?= $k[1] ?></div>
                </div>
            </div>
        </div>
    <?php } ?>
</div>

<div class="row">
    <!-- Gráfico principal -->
    <div class="col-xl-9 col-lg-8">
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-chart-line', 'Preço &middot; ' . e($ranges[$range]),
                '<div class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons">'
                . '<label class="btn btn-outline-light active"><input type="radio" name="tipoGrafico" value="linha" checked> Linha</label>'
                . '<label class="btn btn-outline-light"><input type="radio" name="tipoGrafico" value="candle"> Candles</label></div>') ?>
            <div class="card-body">
                <div class="mb-2 small">
                    <div class="custom-control custom-checkbox custom-control-inline">
                        <input type="checkbox" class="custom-control-input sobreposicao" id="chkMms20" value="mms20" checked>
                        <label class="custom-control-label" for="chkMms20">Média 20</label>
                    </div>
                    <div class="custom-control custom-checkbox custom-control-inline">
                        <input type="checkbox" class="custom-control-input sobreposicao" id="chkMms50" value="mms50" checked>
                        <label class="custom-control-label" for="chkMms50">Média 50</label>
                    </div>
                    <div class="custom-control custom-checkbox custom-control-inline">
                        <input type="checkbox" class="custom-control-input sobreposicao" id="chkBoll" value="boll">
                        <label class="custom-control-label" for="chkBoll">Bandas de Bollinger</label>
                    </div>
                </div>
                <div class="grafico grafico-lg"><canvas id="graficoPreco"></canvas></div>
                <div class="grafico grafico-sm mt-2"><canvas id="graficoVolume"></canvas></div>
            </div>
        </div>

        <!-- Indicadores técnicos em abas -->
        <div class="card mb-4 border-primary shadow">
            <div class="card-header py-2 bg-gradient-primary">
                <ul class="nav nav-pills card-header-pills" role="tablist">
                    <li class="nav-item"><a class="nav-link active text-white" data-toggle="pill" href="#abaIfr" role="tab">IFR (14)</a></li>
                    <li class="nav-item"><a class="nav-link text-white" data-toggle="pill" href="#abaMacd" role="tab">MACD (12, 26, 9)</a></li>
                    <li class="nav-item"><a class="nav-link text-white" data-toggle="pill" href="#abaDd" role="tab">Drawdown</a></li>
                </ul>
            </div>
            <div class="card-body tab-content">
                <div class="tab-pane fade show active" id="abaIfr" role="tabpanel"><div class="grafico grafico-md"><canvas id="graficoIfr"></canvas></div></div>
                <div class="tab-pane fade" id="abaMacd" role="tabpanel"><div class="grafico grafico-md"><canvas id="graficoMacd"></canvas></div></div>
                <div class="tab-pane fade" id="abaDd" role="tabpanel"><div class="grafico grafico-md"><canvas id="graficoDd"></canvas></div></div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-lg-4">
        <!-- Leitura técnica -->
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-compass', 'Leitura técnica') ?>
            <ul class="list-group list-group-flush">
                <?php foreach ($sinais as $s) { ?>
                    <li class="list-group-item">
                        <span class="badge badge-<?= $s[0] ?> badge-sinal mr-1">&nbsp;</span>
                        <strong><?= e($s[1]) ?></strong>
                        <div class="small text-gray-600"><?= e($s[2]) ?></div>
                    </li>
                <?php } ?>
                <?php if (!$sinais) { ?><li class="list-group-item text-gray-600">Período curto demais para calcular os indicadores.</li><?php } ?>
            </ul>
            <div class="card-footer small text-gray-500">Indicadores automáticos, apenas informativos. Não constituem recomendação de compra ou venda.</div>
        </div>

        <!-- Dados do pregão e fundamentos -->
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-info-circle', 'Dados do ativo') ?>
            <table class="table table-sm mb-0">
                <tbody>
                    <tr><td class="text-gray-600">Abertura</td><td class="num"><?= numero($cot['abertura'] ?? null) ?></td></tr>
                    <tr><td class="text-gray-600">Fechamento anterior</td><td class="num"><?= numero($cot['fechamento_anterior'] ?? null) ?></td></tr>
                    <tr><td class="text-gray-600">Mínima / máxima do dia</td><td class="num"><?= numero($cot['minima_dia'] ?? null) ?> / <?= numero($cot['maxima_dia'] ?? null) ?></td></tr>
                    <tr><td class="text-gray-600">Mínima / máxima 52 sem.</td><td class="num"><?= numero($cot['minima_52s'] ?? null) ?> / <?= numero($cot['maxima_52s'] ?? null) ?></td></tr>
                    <tr><td class="text-gray-600">Volume</td><td class="num"><?= abreviado($cot['volume'] ?? null) ?></td></tr>
                    <tr><td class="text-gray-600">Valor de mercado</td><td class="num"><?= abreviado($cot['valor_mercado'] ?? null, 'R$ ') ?></td></tr>
                    <tr><td class="text-gray-600">P/L</td><td class="num"><?= numero($cot['pl'] ?? null) ?></td></tr>
                    <tr><td class="text-gray-600">LPA</td><td class="num"><?= numero($cot['lpa'] ?? null) ?></td></tr>
                    <tr><td class="text-gray-600">Proventos 12 meses</td><td class="num"><?= $prov12m !== null ? moeda($prov12m, 4) : '—' ?></td></tr>
                </tbody>
            </table>
        </div>

        <!-- Proventos -->
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-hand-holding-usd', 'Proventos') ?>
            <?php if (!$dividendos) { ?>
                <div class="card-body text-gray-600">Nenhum provento retornado pela API nos últimos 24 meses.</div>
            <?php } else { ?>
                <div style="max-height: 320px; overflow-y: auto;">
                <table class="table table-sm table-striped mb-0">
                    <thead><tr><th>Tipo</th><th>Data-com</th><th class="num">Valor</th></tr></thead>
                    <tbody>
                    <?php foreach ($dividendos as $d) { ?>
                        <tr title="Pagamento: <?= data_br($d['data_pagamento']) ?>">
                            <td><?= e(strlen($d['tipo']) <= 4 ? $d['tipo'] : ucfirst(strtolower($d['tipo']))) ?></td>
                            <td><?= data_br($d['data_com']) ?></td>
                            <td class="num"><?= numero($d['valor'], 4) ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<?php } ?>

<?php include './layout_base.php'; ?>
<?php if ($temDados) { ?>
<script>
$(function () {
    $('[data-toggle="tooltip"]').tooltip();
    var D = <?= json_encode($jsDados) ?>;
    var C = Painel.cores;
    var ts = D.datas.map(function (d) { return luxon.DateTime.fromISO(d).valueOf(); });
    var serie = function (arr) { return arr.map(function (v, i) { return { x: ts[i], y: v }; }); };
    var eixoX = Painel.eixoTempo();
    var eixoMoeda = { position: 'right', afterFit: Painel.larguraEixo, grid: { color: '#eaecf4' }, ticks: { callback: function (v) { return Painel.num(v, 2); } } };
    var graficoPreco = null;

    function montaPreco() {
        var tipo = $('input[name=tipoGrafico]:checked').val();
        var mostra = {};
        $('.sobreposicao').each(function () { mostra[this.value] = this.checked; });
        var ds = [];
        if (tipo === 'candle') {
            ds.push({
                type: 'candlestick', label: D.ticker,
                data: D.datas.map(function (d, i) { return { x: ts[i], o: D.o[i], h: D.h[i], l: D.l[i], c: D.c[i] }; }),
                color: { up: C.success, down: C.danger, unchanged: C.secondary },
                borderColor: { up: C.success, down: C.danger, unchanged: C.secondary }
            });
        } else {
            ds.push({ type: 'line', label: D.ticker, data: serie(D.c), borderColor: C.primary, backgroundColor: 'rgba(78,115,223,0.07)', fill: true, tension: 0.15 });
        }
        if (mostra.mms20) ds.push({ type: 'line', label: 'Média 20', data: serie(D.mms20), borderColor: C.warning, borderWidth: 1.5, fill: false });
        if (mostra.mms50) ds.push({ type: 'line', label: 'Média 50', data: serie(D.mms50), borderColor: '#6f42c1', borderWidth: 1.5, fill: false });
        if (mostra.boll) {
            ds.push({ type: 'line', label: 'Bollinger sup.', data: serie(D.bsup), borderColor: C.info, borderWidth: 1, borderDash: [4, 3], fill: false });
            ds.push({ type: 'line', label: 'Bollinger inf.', data: serie(D.binf), borderColor: C.info, borderWidth: 1, borderDash: [4, 3], fill: '-1', backgroundColor: 'rgba(54,185,204,0.06)' });
        }
        if (graficoPreco) graficoPreco.destroy();
        graficoPreco = new Chart(document.getElementById('graficoPreco'), {
            type: tipo === 'candle' ? 'candlestick' : 'line',
            data: { datasets: ds },
            options: {
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'top', align: 'end' },
                    tooltip: { callbacks: { label: function (c) {
                        var r = c.raw;
                        if (r && r.o !== undefined) return 'A ' + Painel.num(r.o, 2) + '  M ' + Painel.num(r.h, 2) + '  m ' + Painel.num(r.l, 2) + '  F ' + Painel.num(r.c, 2);
                        return c.dataset.label + ': ' + Painel.num(c.parsed.y, 2);
                    } } }
                },
                scales: { x: eixoX, y: eixoMoeda }
            }
        });
    }
    montaPreco();
    $('input[name=tipoGrafico], .sobreposicao').on('change', montaPreco);

    // Volume
    new Chart(document.getElementById('graficoVolume'), {
        type: 'bar',
        data: { datasets: [{ label: 'Volume', data: serie(D.v),
            backgroundColor: D.c.map(function (c, i) { return i > 0 && c < D.c[i - 1] ? 'rgba(231,74,59,0.55)' : 'rgba(28,200,138,0.55)'; }) }] },
        options: {
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return 'Volume: ' + Painel.abreviado(c.parsed.y); } } } },
            scales: { x: Painel.eixoTempo({ ticks: { display: false } }), y: { position: 'right', afterFit: Painel.larguraEixo, grid: { color: '#eaecf4' }, ticks: { callback: function (v) { return Painel.abreviado(v); }, maxTicksLimit: 3 } } }
        }
    });

    // IFR
    new Chart(document.getElementById('graficoIfr'), {
        type: 'line',
        data: { datasets: [
            { label: 'IFR 14', data: serie(D.ifr), borderColor: C.primary, fill: false },
            { label: 'Sobrecompra (70)', data: ts.map(function (t) { return { x: t, y: 70 }; }), borderColor: C.danger, borderWidth: 1, borderDash: [5, 4] },
            { label: 'Sobrevenda (30)', data: ts.map(function (t) { return { x: t, y: 30 }; }), borderColor: C.success, borderWidth: 1, borderDash: [5, 4] }
        ] },
        options: { interaction: { mode: 'index', intersect: false }, scales: { x: eixoX, y: { min: 0, max: 100, position: 'right', ticks: { stepSize: 10 } } } }
    });

    // MACD
    new Chart(document.getElementById('graficoMacd'), {
        type: 'bar',
        data: { datasets: [
            { type: 'line', label: 'MACD', data: serie(D.macd), borderColor: C.primary, fill: false },
            { type: 'line', label: 'Sinal', data: serie(D.sinal), borderColor: C.warning, fill: false },
            { type: 'bar', label: 'Histograma', data: serie(D.hist),
              backgroundColor: D.hist.map(function (v) { return v >= 0 ? 'rgba(28,200,138,0.6)' : 'rgba(231,74,59,0.6)'; }) }
        ] },
        options: { interaction: { mode: 'index', intersect: false }, scales: { x: eixoX, y: { position: 'right', grid: { color: '#eaecf4' } } } }
    });

    // Drawdown
    new Chart(document.getElementById('graficoDd'), {
        type: 'line',
        data: { datasets: [{ label: 'Distância do topo (%)', data: serie(D.dd), borderColor: C.danger, backgroundColor: 'rgba(231,74,59,0.12)', fill: true }] },
        options: { interaction: { mode: 'index', intersect: false },
            plugins: { tooltip: { callbacks: { label: function (c) { return Painel.pct(c.parsed.y, 2); } } } },
            scales: { x: eixoX, y: { position: 'right', max: 0, ticks: { callback: function (v) { return v + '%'; } } } } }
    });
});
</script>
<?php } ?>
</body>
</html>
