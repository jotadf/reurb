<?php
require_once './verifica_login.php';
require_once './actions/ManterAtivo.php';
require_once './actions/ServicoMercado.php';
require_once './actions/Analise.php';

const MAX_COMPARACAO = 8;

$servico = new ServicoMercado();
$lista   = (new ManterAtivo())->listar();
$ranges  = config('analise.ranges');
$indice  = config('analise.indice_referencia');
$range   = isset($_GET['r']) && !is_array($_GET['r']) && isset($ranges[$_GET['r']]) ? $_GET['r'] : '6mo';
if (!isset($ranges[$range])) {
    $range = config('analise.range_padrao');
}

// Tickers escolhidos: caixas marcadas + campo livre
$escolhidos = [];
foreach ((array) ($_GET['t'] ?? []) as $t) {
    if (!is_array($t) && ($n = normaliza_ticker($t))) $escolhidos[] = $n;
}
foreach (preg_split('/[\s,;]+/', (string) ($_GET['extra'] ?? '')) as $t) {
    if ($n = normaliza_ticker($t)) $escolhidos[] = $n;
}
$incluirIndice = !empty($_GET['indice']);
if (!$escolhidos && !isset($_GET['r'])) {
    // primeira visita: compara os primeiros ativos da lista com o índice
    $escolhidos = array_slice(array_column($lista, 'ticker'), 0, 4);
    $incluirIndice = true;
}
if ($incluirIndice) {
    $escolhidos[] = $indice;
}
$escolhidos = array_slice(array_values(array_unique($escolhidos)), 0, MAX_COMPARACAO);

// Busca histórico (o serviço usa o cache) e monta as séries
$series = [];
$cotacoes = $escolhidos ? $servico->cotacoes($escolhidos) : [];
foreach ($escolhidos as $t) {
    $s = [];
    foreach ($servico->historico($t, $range) as $h) {
        $s[$h['data']] = (float) ($h['fechamento_ajustado'] ?? $h['fechamento']);
    }
    if (count($s) >= 2) {
        $series[$t] = $s;
    }
}
$semDados = array_diff($escolhidos, array_keys($series));
$alinhado = $series ? Analise::alinhar($series) : ['datas' => [], 'series' => []];
$rf = (float) config('analise.taxa_livre_risco_anual');

$metricas = [];
$retornos = [];
foreach ($alinhado['series'] as $t => $precos) {
    $r = Analise::retornos($precos);
    $retornos[$t] = $r;
    $metricas[$t] = [
        'retorno' => Analise::retornoTotal($precos),
        'vol'     => Analise::volatilidadeAnual($r),
        'mdd'     => Analise::maxDrawdown($precos),
        'sharpe'  => Analise::sharpe($r, $rf),
        'preco'   => $cotacoes[$t]['preco'] ?? end($precos),
        'dia'     => $cotacoes[$t]['variacao_pct'] ?? null,
    ];
}
$corr = [];
foreach ($retornos as $a => $ra) {
    foreach ($retornos as $b => $rb) {
        $corr[$a][$b] = $a === $b ? 1.0 : Analise::correlacao($ra, $rb);
    }
}
$poucasDatas = $series && count($alinhado['datas']) < 10;

// Dados para gráficos
$jsBase = [];
$jsDispersao = [];
foreach ($alinhado['series'] as $t => $precos) {
    $jsBase[] = ['t' => nome_ticker($t), 'v' => Analise::base100($precos)];
    if ($metricas[$t]['vol'] !== null && $metricas[$t]['retorno'] !== null) {
        $jsDispersao[] = ['t' => nome_ticker($t), 'x' => round($metricas[$t]['vol'] * 100, 2), 'y' => round($metricas[$t]['retorno'] * 100, 2)];
    }
}

/** Cor de fundo da célula de correlação (azul = positiva, vermelho = negativa). */
function cor_corr($v)
{
    if ($v === null) return '#f8f9fc';
    $a = min(1, abs($v));
    return $v >= 0 ? sprintf('rgba(78,115,223,%.2f)', 0.08 + 0.6 * $a) : sprintf('rgba(231,74,59,%.2f)', 0.08 + 0.6 * $a);
}

$titulo = 'Comparar ativos';
include './layout_topo.php';
?>
<h1 class="h3 mb-4 text-gray-800">Comparar ativos</h1>

<!-- Seleção -->
<div class="card mb-4 border-primary shadow">
    <?= card_cabecalho('fas fa-sliders-h', 'Ativos e período') ?>
    <div class="card-body">
        <form method="get">
            <div class="mb-2">
                <?php foreach ($lista as $a) { ?>
                    <div class="custom-control custom-checkbox custom-control-inline">
                        <input type="checkbox" class="custom-control-input" id="c_<?= e($a['ticker']) ?>" name="t[]" value="<?= e($a['ticker']) ?>"
                            <?= in_array($a['ticker'], $escolhidos, true) ? 'checked' : '' ?>>
                        <label class="custom-control-label font-weight-bold" for="c_<?= e($a['ticker']) ?>"><?= e($a['ticker']) ?></label>
                    </div>
                <?php } ?>
                <div class="custom-control custom-checkbox custom-control-inline">
                    <input type="checkbox" class="custom-control-input" id="c_indice" name="indice" value="1" <?= $incluirIndice ? 'checked' : '' ?>>
                    <label class="custom-control-label font-weight-bold text-primary" for="c_indice"><?= e(nome_ticker($indice)) ?></label>
                </div>
            </div>
            <div class="form-row align-items-end">
                <div class="col-md-5 mb-2">
                    <label class="small text-gray-600 mb-1" for="extra">Outros códigos (separados por vírgula)</label>
                    <input type="text" class="form-control form-control-sm text-uppercase" id="extra" name="extra" placeholder="ex.: BBAS3, WEGE3, BOVA11"
                           value="<?= e(implode(', ', array_diff($escolhidos, array_column($lista, 'ticker'), [$indice]))) ?>">
                </div>
                <div class="col-md-3 mb-2">
                    <label class="small text-gray-600 mb-1" for="r">Período</label>
                    <select class="form-control form-control-sm" id="r" name="r">
                        <?php foreach ($ranges as $k => $rot) { ?>
                            <option value="<?= e($k) ?>" <?= $k === $range ? 'selected' : '' ?>><?= e($rot) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2 mb-2">
                    <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-play mr-1"></i> Comparar</button>
                </div>
                <div class="col-md-2 mb-2 small text-gray-500">Até <?= MAX_COMPARACAO ?> ativos.</div>
            </div>
        </form>
    </div>
</div>

<?= alerta_erros_api($servico->erros) ?>
<?php if ($semDados) { ?>
    <div class="alert alert-secondary small">Sem histórico suficiente para: <strong><?= e(implode(', ', $semDados)) ?></strong>.</div>
<?php } ?>
<?php if ($series && ($desde = ServicoMercado::periodoIncompleto($range, reset($alinhado['datas'])))) { ?>
    <div class="alert alert-info small"><i class="fas fa-history mr-1"></i>Histórico disponível no banco a partir de <strong><?= data_br($desde) ?></strong>.
        O período escolhido vai ficando completo à medida que os pregões são acumulados.</div>
<?php } ?>
<?php if ($poucasDatas) { ?>
    <div class="alert alert-warning small">Os ativos têm poucas datas em comum no período, então as métricas podem não ser representativas.</div>
<?php } ?>

<?php if ($series) { ?>
<div class="row">
    <div class="col-xl-8">
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-chart-line', 'Desempenho (base 100) &middot; ' . e($ranges[$range])) ?>
            <div class="card-body"><div class="grafico grafico-lg"><canvas id="graficoBase100"></canvas></div></div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-crosshairs', 'Risco × retorno') ?>
            <div class="card-body"><div class="grafico grafico-lg"><canvas id="graficoDispersao"></canvas></div></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-7">
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-table', 'Métricas do período') ?>
            <div class="card-body">
                <table id="tabelaMetricas" class="table table-sm table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr><th>Ativo</th><th class="num">Preço</th><th class="num">Hoje</th><th class="num">Retorno</th>
                            <th class="num">Volatilidade a.a.</th><th class="num">Queda máx.</th><th class="num">Sharpe</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($metricas as $t => $m) { ?>
                        <tr>
                            <td><a class="ticker" href="ativo.php?t=<?= e(rawurlencode($t)) ?>&r=<?= e($range) ?>"><?= e(nome_ticker($t)) ?></a></td>
                            <td class="num"><?= $t === $indice ? numero($m['preco'], 0) : moeda($m['preco']) ?></td>
                            <td class="num <?= cor($m['dia']) ?>" data-order="<?= (float) $m['dia'] ?>"><?= pct($m['dia']) ?></td>
                            <td class="num font-weight-bold <?= cor($m['retorno']) ?>" data-order="<?= (float) $m['retorno'] ?>"><?= pct($m['retorno'], 2, true) ?></td>
                            <td class="num" data-order="<?= (float) $m['vol'] ?>"><?= pct($m['vol'], 1, true, false) ?></td>
                            <td class="num text-danger" data-order="<?= (float) $m['mdd'] ?>"><?= pct($m['mdd'], 1, true) ?></td>
                            <td class="num" data-order="<?= (float) $m['sharpe'] ?>"><?= numero($m['sharpe'], 2) ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <div class="small text-gray-500 mt-2">Calculado com preços ajustados por proventos, nas <?= count($alinhado['datas']) ?> datas em comum
                    (<?= data_br(reset($alinhado['datas'])) ?> a <?= data_br(end($alinhado['datas'])) ?>). Sharpe com taxa livre de risco de <?= pct($rf, 2, true, false) ?> a.a.</div>
            </div>
        </div>
    </div>
    <div class="col-xl-5">
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-th', 'Correlação dos retornos diários') ?>
            <div class="card-body table-responsive">
                <table class="table table-sm table-bordered tabela-corr mb-2">
                    <thead><tr><th></th><?php foreach (array_keys($corr) as $t) { ?><th><?= e(nome_ticker($t)) ?></th><?php } ?></tr></thead>
                    <tbody>
                    <?php foreach ($corr as $a => $linha) { ?>
                        <tr><th><?= e(nome_ticker($a)) ?></th>
                        <?php foreach ($linha as $b => $v) { ?>
                            <td style="background: <?= cor_corr($v) ?>"><?= $v === null ? '—' : numero($v, 2) ?></td>
                        <?php } ?>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <div class="small text-gray-500">Perto de 1: os ativos sobem e caem juntos. Perto de 0: movimentos independentes. Negativa: tendem a se mover em direções opostas, o que ajuda a diversificar.</div>
            </div>
        </div>
    </div>
</div>
<?php } elseif ($escolhidos) { ?>
    <div class="card border-left-warning shadow mb-4"><div class="card-body">Nenhum dos ativos escolhidos retornou histórico para o período.</div></div>
<?php } ?>

<?php include './layout_base.php'; ?>
<?php if ($series) { ?>
<script>
$(function () {
    $('#tabelaMetricas').DataTable({ paging: false, searching: false, info: false, order: [[3, 'desc']] });
    var datas = <?= json_encode($alinhado['datas']) ?>;
    var base = <?= json_encode($jsBase) ?>;
    var disp = <?= json_encode($jsDispersao) ?>;
    var ts = datas.map(function (d) { return luxon.DateTime.fromISO(d).valueOf(); });

    new Chart(document.getElementById('graficoBase100'), {
        type: 'line',
        data: { datasets: base.map(function (s, i) {
            return { label: s.t, data: s.v.map(function (v, j) { return { x: ts[j], y: v }; }),
                     borderColor: Painel.serie[i % Painel.serie.length], fill: false, tension: 0.1 };
        }) },
        options: {
            interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'top', align: 'end' },
                tooltip: { itemSort: function (a, b) { return b.parsed.y - a.parsed.y; },
                    callbacks: { label: function (c) { return c.dataset.label + ': ' + Painel.num(c.parsed.y, 1) + ' (' + Painel.pct(c.parsed.y - 100, 1) + ')'; } } } },
            scales: {
                x: Painel.eixoTempo(),
                y: { position: 'right', grid: { color: '#eaecf4' } }
            }
        }
    });

    new Chart(document.getElementById('graficoDispersao'), {
        type: 'scatter',
        data: { datasets: disp.map(function (p, i) {
            return { label: p.t, data: [{ x: p.x, y: p.y }], backgroundColor: Painel.serie[i % Painel.serie.length], pointRadius: 7, pointHoverRadius: 9 };
        }) },
        options: {
            plugins: { legend: { position: 'bottom' },
                tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': retorno ' + Painel.pct(c.parsed.y, 1) + ', volatilidade ' + Painel.num(c.parsed.x, 1) + '%'; } } } },
            scales: {
                x: { title: { display: true, text: 'Volatilidade anualizada (%)' }, grid: { color: '#eaecf4' } },
                y: { title: { display: true, text: 'Retorno no período (%)' }, grid: { color: '#eaecf4' } }
            }
        }
    });
});
</script>
<?php } ?>
</body>
</html>
