<?php
require_once './verifica_login.php';
require_once './actions/ManterCarteira.php';
require_once './actions/ServicoMercado.php';
require_once './actions/Analise.php';

$servico  = new ServicoMercado();
$posicoes = (new ManterCarteira())->listar($usuario_logado->id);
$ranges   = config('analise.ranges');
$range    = isset($_GET['r']) && !is_array($_GET['r']) && isset($ranges[$_GET['r']]) ? $_GET['r'] : config('analise.range_padrao');
$novo     = isset($_GET['novo']) && !is_array($_GET['novo']) ? normaliza_ticker($_GET['novo']) : null;

$cotacoes = $posicoes ? $servico->cotacoes(array_column($posicoes, 'ticker')) : [];

// Valores de cada posição
$linhas = [];
$tot = ['valor' => 0.0, 'custo' => 0.0, 'dia' => 0.0];
foreach ($posicoes as $p) {
    $c = $cotacoes[$p['ticker']] ?? null;
    $preco = $c['preco'] ?? null;
    $valor = $p['quantidade'] * ($preco ?? $p['preco_medio']);
    $custo = $p['quantidade'] * $p['preco_medio'];
    $dia   = $p['quantidade'] * (float) ($c['variacao'] ?? 0);
    $linhas[] = $p + ['preco' => $preco, 'valor' => $valor, 'custo' => $custo, 'resultado' => $valor - $custo,
                      'resultado_pct' => $custo > 0 ? ($valor - $custo) / $custo : null, 'dia' => $dia,
                      'dia_pct' => $c['variacao_pct'] ?? null, 'logo' => $c['logo_url'] ?? null, 'sem_cotacao' => $preco === null];
    $tot['valor'] += $valor;
    $tot['custo'] += $custo;
    $tot['dia']   += $dia;
}
foreach ($linhas as &$l) {
    $l['peso'] = $tot['valor'] > 0 ? $l['valor'] / $tot['valor'] : 0;
}
unset($l);
usort($linhas, function ($a, $b) { return $b['valor'] <=> $a['valor']; });
$resultado = $tot['valor'] - $tot['custo'];

// Evolução da composição atual no período (quantidades fixas)
$evolucao = null;
if ($posicoes) {
    $series = [];
    foreach ($posicoes as $p) {
        $s = [];
        foreach ($servico->historico($p['ticker'], $range) as $h) {
            $s[$h['data']] = (float) $h['fechamento'];
        }
        if (count($s) >= 2) {
            $series[$p['ticker']] = $s;
        }
    }
    if ($series) {
        $al = Analise::alinhar($series);
        $qtd = array_column($posicoes, 'quantidade', 'ticker');
        $valores = [];
        foreach ($al['datas'] as $i => $d) {
            $v = 0.0;
            foreach ($al['series'] as $t => $precos) {
                $v += $qtd[$t] * $precos[$i];
            }
            $valores[] = round($v, 2);
        }
        if (count($valores) >= 2) {
            $ret = Analise::retornos($valores);
            $evolucao = [
                'datas' => $al['datas'], 'valores' => $valores,
                'retorno' => Analise::retornoTotal($valores),
                'vol' => Analise::volatilidadeAnual($ret),
                'mdd' => Analise::maxDrawdown($valores),
                'parcial' => count($series) < count($posicoes),
            ];
        }
    }
}

$jsAloc = array_map(function ($l) { return ['t' => $l['ticker'], 'v' => round($l['valor'], 2)]; }, $linhas);
$jsRes  = array_map(function ($l) { return ['t' => $l['ticker'], 'v' => round($l['resultado'], 2)]; }, $linhas);

$titulo = 'Minha carteira';
include './layout_topo.php';
?>
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Minha carteira</h1>
    <span class="small text-gray-600">Os dados são individuais: cada usuário vê apenas a própria carteira.</span>
</div>

<?= alerta_erros_api($servico->erros) ?>

<!-- Indicadores -->
<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Patrimônio atual</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800 num"><?= moeda($tot['valor']) ?></div>
            <div class="small text-gray-600"><?= count($linhas) ?> posição(ões)</div>
        </div></div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-secondary shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">Custo total</div>
            <div class="h5 mb-0 font-weight-bold text-gray-800 num"><?= moeda($tot['custo']) ?></div>
            <div class="small text-gray-600">quantidade × preço médio</div>
        </div></div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-<?= $resultado >= 0 ? 'success' : 'danger' ?> shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-<?= $resultado >= 0 ? 'success' : 'danger' ?> text-uppercase mb-1">Resultado</div>
            <div class="h5 mb-0 font-weight-bold num <?= cor($resultado) ?>"><?= moeda($resultado) ?></div>
            <div class="small font-weight-bold <?= cor($resultado) ?>"><?= $tot['custo'] > 0 ? pct($resultado / $tot['custo'], 2, true) : '—' ?></div>
        </div></div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2"><div class="card-body">
            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Variação hoje</div>
            <div class="h5 mb-0 font-weight-bold num <?= cor($tot['dia']) ?>"><?= moeda($tot['dia']) ?></div>
            <div class="small font-weight-bold <?= cor($tot['dia']) ?>"><?= ($tot['valor'] - $tot['dia']) > 0 ? pct($tot['dia'] / ($tot['valor'] - $tot['dia']), 2, true) : '—' ?></div>
        </div></div>
    </div>
</div>

<!-- Formulário (abre recolhido, como no REURB) -->
<div class="collapse <?= $novo ? 'show' : '' ?>" id="form_posicao">
    <div class="card mb-4 border-primary shadow" style="max-width: 900px">
        <?= card_cabecalho('fas fa-edit', '<span id="tituloForm">Nova posição</span>') ?>
        <div class="card-body">
            <form method="post" action="save_posicao.php" id="formPosicao">
                <?= csrf_campo() ?>
                <input type="hidden" name="id" id="pos_id" value="0">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="pos_ticker" class="small font-weight-bold">Código</label>
                        <input type="text" class="form-control form-control-sm text-uppercase" id="pos_ticker" name="ticker" maxlength="15" required value="<?= e($novo) ?>">
                    </div>
                    <div class="form-group col-md-2">
                        <label for="pos_qtd" class="small font-weight-bold">Quantidade</label>
                        <input type="text" class="form-control form-control-sm" id="pos_qtd" name="quantidade" inputmode="decimal" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="pos_preco" class="small font-weight-bold">Preço médio (R$)</label>
                        <input type="text" class="form-control form-control-sm" id="pos_preco" name="preco_medio" inputmode="decimal" required>
                    </div>
                    <div class="form-group col-md-2">
                        <label for="pos_data" class="small font-weight-bold">Data da compra</label>
                        <input type="date" class="form-control form-control-sm" id="pos_data" name="data_compra">
                    </div>
                    <div class="form-group col-md-4">
                        <label for="pos_obs" class="small font-weight-bold">Observação</label>
                        <input type="text" class="form-control form-control-sm" id="pos_obs" name="observacao" maxlength="255">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i>Salvar</button>
                <button type="button" class="btn btn-secondary btn-sm" id="btnCancelar">Cancelar</button>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-8">
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-briefcase', 'Posições',
                '<button id="btn_cadastrar" class="btn btn-outline-light btn-sm" type="button" title="Nova posição"><i class="fa fa-plus-circle text-white"></i></button>') ?>
            <div class="card-body">
                <?php if (!$linhas) { ?>
                    <p class="text-gray-600 mb-0">Nenhuma posição cadastrada. Clique em <i class="fa fa-plus-circle"></i> para adicionar.</p>
                <?php } else { ?>
                <table id="tabelaPosicoes" class="table table-sm table-striped table-bordered dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr><th>Ativo</th><th class="num">Qtd.</th><th class="num">Preço médio</th><th class="num">Preço atual</th>
                            <th class="num">Valor</th><th class="num">Resultado</th><th class="num">Hoje</th><th class="num">Peso</th><th>Opções</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($linhas as $l) { ?>
                        <tr>
                            <td><div class="d-flex align-items-center"><?= logo_html($l['ticker'], $l['logo'], 'mr-2') ?>
                                <a class="ticker" href="ativo.php?t=<?= e(rawurlencode($l['ticker'])) ?>"><?= e($l['ticker']) ?></a></div></td>
                            <td class="num" data-order="<?= (float) $l['quantidade'] ?>"><?= numero($l['quantidade'], fmod((float) $l['quantidade'], 1) ? 2 : 0) ?></td>
                            <td class="num" data-order="<?= (float) $l['preco_medio'] ?>"><?= moeda($l['preco_medio']) ?></td>
                            <td class="num" data-order="<?= (float) $l['preco'] ?>"><?= $l['sem_cotacao'] ? '<span class="text-warning" title="Sem cotação: usando o preço médio">—</span>' : moeda($l['preco']) ?></td>
                            <td class="num font-weight-bold" data-order="<?= $l['valor'] ?>"><?= moeda($l['valor']) ?></td>
                            <td class="num <?= cor($l['resultado']) ?>" data-order="<?= $l['resultado'] ?>"><?= moeda($l['resultado']) ?><br><small><?= pct($l['resultado_pct'], 2, true) ?></small></td>
                            <td class="num <?= cor($l['dia_pct']) ?>" data-order="<?= (float) $l['dia_pct'] ?>"><?= pct($l['dia_pct']) ?></td>
                            <td class="num" data-order="<?= $l['peso'] ?>"><?= pct($l['peso'], 1, true, false) ?></td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-primary btn-editar" title="Alterar"
                                    data-id="<?= (int) $l['id'] ?>" data-ticker="<?= e($l['ticker']) ?>"
                                    data-qtd="<?= e(numero($l['quantidade'], 4)) ?>" data-preco="<?= e(numero($l['preco_medio'], 4)) ?>"
                                    data-data="<?= e($l['data_compra']) ?>" data-obs="<?= e($l['observacao']) ?>"><i class="fas fa-edit"></i></button>
                                <button type="button" class="btn btn-sm btn-outline-danger btn-excluir" title="Excluir"
                                    data-id="<?= (int) $l['id'] ?>" data-nome="<?= e($l['ticker']) ?>"><i class="fas fa-trash"></i></button>
                            </td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <?php } ?>
            </div>
        </div>

        <?php if ($evolucao) { ?>
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-chart-area', 'Evolução da composição atual',
                '<form method="get" class="form-inline"><select name="r" class="form-control form-control-sm" onchange="this.form.submit()">'
                . implode('', array_map(function ($k, $v) use ($range) { return '<option value="' . e($k) . '"' . ($k === $range ? ' selected' : '') . '>' . e($v) . '</option>'; }, array_keys($ranges), $ranges))
                . '</select></form>') ?>
            <div class="card-body">
                <div class="row text-center mb-3">
                    <div class="col"><div class="text-xs text-uppercase text-gray-500 font-weight-bold">Retorno</div><div class="kpi-valor <?= cor($evolucao['retorno']) ?>"><?= pct($evolucao['retorno'], 2, true) ?></div></div>
                    <div class="col"><div class="text-xs text-uppercase text-gray-500 font-weight-bold">Volatilidade a.a.</div><div class="kpi-valor"><?= pct($evolucao['vol'], 1, true, false) ?></div></div>
                    <div class="col"><div class="text-xs text-uppercase text-gray-500 font-weight-bold">Queda máxima</div><div class="kpi-valor text-danger"><?= pct($evolucao['mdd'], 1, true) ?></div></div>
                </div>
                <div class="grafico grafico-md"><canvas id="graficoEvolucao"></canvas></div>
                <div class="small text-gray-500 mt-2">Simula quanto valeria a carteira de hoje, com as mesmas quantidades, em cada dia do período (preços sem ajuste de proventos).
                    <?= $evolucao['parcial'] ? 'Alguns ativos ficaram de fora por falta de histórico.' : '' ?></div>
            </div>
        </div>
        <?php } ?>
    </div>

    <div class="col-xl-4">
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-chart-pie', 'Alocação') ?>
            <div class="card-body">
                <?php if ($linhas) { ?><div class="grafico grafico-md"><canvas id="graficoAlocacao"></canvas></div>
                <?php } else { ?><p class="text-gray-600 mb-0">—</p><?php } ?>
            </div>
        </div>
        <div class="card mb-4 border-primary shadow">
            <?= card_cabecalho('fas fa-chart-bar', 'Resultado por ativo') ?>
            <div class="card-body">
                <?php if ($linhas) { ?><div class="grafico" style="height: <?= max(160, count($linhas) * 30 + 40) ?>px"><canvas id="graficoResultado"></canvas></div>
                <?php } else { ?><p class="text-gray-600 mb-0">—</p><?php } ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal de exclusão -->
<div class="modal fade" id="confirm" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="modal-content" method="post" action="del_posicao.php">
            <?= csrf_campo() ?>
            <input type="hidden" name="id" id="del_id">
            <div class="modal-header"><h5 class="modal-title">Excluir posição</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button></div>
            <div class="modal-body">Deseja excluir a posição em <strong id="nome_excluir"></strong>?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Excluir</button>
            </div>
        </form>
    </div>
</div>

<?php include './layout_base.php'; ?>
<script>
$(function () {
    $('#tabelaPosicoes').DataTable({ order: [[4, 'desc']], columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }, { orderable: false, targets: -1 }] });

    function limpa() {
        $('#pos_id').val(0); $('#formPosicao')[0].reset(); $('#pos_ticker').prop('readonly', false); $('#tituloForm').text('Nova posição');
    }
    $('#btn_cadastrar').on('click', function () { limpa(); $('#form_posicao').collapse('show'); $('#pos_ticker').focus(); });
    $('#btnCancelar').on('click', function () { limpa(); $('#form_posicao').collapse('hide'); });
    $(document).on('click', '.btn-editar', function () {
        var b = $(this);
        $('#pos_id').val(b.data('id')); $('#pos_ticker').val(b.data('ticker')); $('#pos_qtd').val(b.data('qtd'));
        $('#pos_preco').val(b.data('preco')); $('#pos_data').val(b.data('data')); $('#pos_obs').val(b.data('obs'));
        $('#tituloForm').text('Alterar posição ' + b.data('ticker'));
        $('#form_posicao').collapse('show');
        $('html, body').animate({ scrollTop: $('#form_posicao').offset().top - 80 }, 300);
    });
    $(document).on('click', '.btn-excluir', function () {
        $('#del_id').val($(this).data('id')); $('#nome_excluir').text($(this).data('nome')); $('#confirm').modal('show');
    });

    var aloc = <?= json_encode($jsAloc) ?>;
    var res = <?= json_encode($jsRes) ?>;
    if (aloc.length) {
        new Chart(document.getElementById('graficoAlocacao'), {
            type: 'doughnut',
            data: { labels: aloc.map(function (a) { return a.t; }),
                    datasets: [{ data: aloc.map(function (a) { return a.v; }), backgroundColor: aloc.map(function (a, i) { return Painel.serie[i % Painel.serie.length]; }), borderWidth: 2, hoverOffset: 6 }] },
            options: { cutout: '65%', plugins: { legend: { position: 'bottom' },
                tooltip: { callbacks: { label: function (c) {
                    var tot = c.dataset.data.reduce(function (s, v) { return s + v; }, 0);
                    return c.label + ': ' + Painel.moeda(c.parsed) + ' (' + Painel.num(c.parsed / tot * 100, 1) + '%)';
                } } } } }
        });
        new Chart(document.getElementById('graficoResultado'), {
            type: 'bar',
            data: { labels: res.map(function (r) { return r.t; }),
                    datasets: [{ data: res.map(function (r) { return r.v; }), borderRadius: 3, barThickness: 16,
                                 backgroundColor: res.map(function (r) { return r.v >= 0 ? Painel.cores.success : Painel.cores.danger; }) }] },
            options: { indexAxis: 'y', plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return Painel.moeda(c.parsed.x); } } } },
                scales: { x: { grid: { color: '#eaecf4' }, ticks: { callback: function (v) { return Painel.abreviado(v); } } }, y: { grid: { display: false }, ticks: { font: { weight: 'bold' } } } } }
        });
    }

    <?php if ($evolucao) { ?>
    var ev = <?= json_encode(['d' => $evolucao['datas'], 'v' => $evolucao['valores']]) ?>;
    new Chart(document.getElementById('graficoEvolucao'), {
        type: 'line',
        data: { datasets: [{ label: 'Valor da carteira', data: ev.d.map(function (d, i) { return { x: luxon.DateTime.fromISO(d).valueOf(), y: ev.v[i] }; }),
                             borderColor: Painel.cores.primary, backgroundColor: 'rgba(78,115,223,0.08)', fill: true, tension: 0.15 }] },
        options: { interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return Painel.moeda(c.parsed.y); } } } },
            scales: { x: Painel.eixoTempo(),
                      y: { position: 'right', grid: { color: '#eaecf4' }, ticks: { callback: function (v) { return Painel.abreviado(v); } } } } }
    });
    <?php } ?>
});
</script>
</body>
</html>
