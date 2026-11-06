<?php
/**
 * Relatório Geral: lotes, domicílios e os cadastros ligados (sócio jurídico e
 * caracterização), com filtros e exportação para Excel.
 *   relatorio.php                         tela
 *   relatorio.php?exportar=resumido|completo&(filtros)   baixa a planilha
 */
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/Relatorio.php';
require __DIR__ . '/lib/Xlsx.php';
exigir_login();

if (!pode_pagina('relatorio_geral')) {
    acesso_negado('Seu perfil (' . (perfil_nome() ?: 'sem perfil') . ') não tem permissão para ver o Relatório Geral.');
}
try {
    $rel = new Relatorio();
} catch (RuntimeException $e) {
    acesso_negado($e->getMessage());
}

// Filtros
$filtros = [
    'q'        => trim((string) ($_GET['q'] ?? '')),
    'setor'    => (string) ($_GET['setor'] ?? ''),
    'situacao' => isset($rel->situacoes()[$_GET['situacao'] ?? '']) ? (string) $_GET['situacao'] : '',
];
$podeExportar = pode_pagina('relatorio_exportar');

/* ================================================================== */
/* Exportação                                                          */
/* ================================================================== */
$exportar = $_GET['exportar'] ?? '';
if (in_array($exportar, ['resumido', 'completo'], true)) {
    if (!$podeExportar) {
        acesso_negado('Seu perfil (' . (perfil_nome() ?: 'sem perfil') . ') não tem permissão para exportar o relatório.');
    }
    @set_time_limit(0);
    $colunas = $rel->colunas($exportar);
    $nome    = 'relatorio_reurb_' . $exportar . '_' . date('Y-m-d_His');
    $linhas  = 0;

    if (Xlsx::disponivel()) {
        $x = new Xlsx('Relatório ' . ucfirst($exportar));
        // Grupos (linha 1) a partir das colunas consecutivas de mesmo grupo
        $grupos = [];
        foreach ($colunas as $c) {
            $n = count($grupos);
            if ($n && $grupos[$n - 1]['titulo'] === $c['grupo']) {
                $grupos[$n - 1]['qtd']++;
            } else {
                $grupos[] = ['titulo' => $c['grupo'], 'qtd' => 1];
            }
        }
        $x->cabecalho($grupos, array_column($colunas, 'label'));
        foreach ($rel->percorrer($filtros, $exportar) as $l) {
            $x->linha(array_map(fn($c) => $rel->valor($c, $l), $colunas));
            $linhas++;
        }
        $arquivo = $x->salvar();
        $formato = 'xlsx';
    } else {
        // Sem a extensão zip do PHP: CSV que o Excel abre (UTF-8, separador ";", vírgula decimal)
        $arquivo = tempnam(sys_get_temp_dir(), 'csv');
        $out = fopen($arquivo, 'wb');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, array_map(fn($c) => $c['grupo'] . ' - ' . $c['label'], $colunas), ';');
        foreach ($rel->percorrer($filtros, $exportar) as $l) {
            $valores = [];
            foreach ($colunas as $c) {
                [$tipo, $v] = $rel->valor($c, $l);
                $v = (string) $v;
                if ($v !== '' && in_array($tipo, ['numero', 'decimal'], true)) {
                    $v = str_replace('.', ',', $v);
                } elseif ($v !== '' && $tipo === 'data') {
                    $v = date('d/m/Y', strtotime($v));
                } elseif ($v !== '' && $tipo === 'datahora') {
                    $v = date('d/m/Y H:i', strtotime($v));
                } elseif ($v !== '' && in_array($v[0], ['=', '+', '-', '@'], true)) {
                    $v = "'" . $v;   // evita que o Excel interprete como fórmula
                }
                $valores[] = $v;
            }
            fputcsv($out, $valores, ';');
            $linhas++;
        }
        fclose($out);
        $formato = 'csv';
    }

    // Exportação fica registrada na auditoria (a planilha leva dados pessoais)
    db()->prepare('INSERT INTO auditoria (tabela, acao, usuario, valor_antigo, valor_atual, atualizado) VALUES (?, ?, ?, NULL, ?, NOW())')
        ->execute(['relatorio_geral', 'EXPORTAR', usuario_id(), json_encode(
            ['modelo' => $exportar, 'formato' => $formato, 'linhas' => $linhas, 'filtros' => array_filter($filtros, fn($v) => $v !== '')],
            JSON_UNESCAPED_UNICODE
        )]);

    header('Content-Type: ' . ($formato === 'xlsx'
        ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        : 'text/csv; charset=UTF-8'));
    header('Content-Disposition: attachment; filename="' . $nome . '.' . $formato . '"');
    header('Content-Length: ' . filesize($arquivo));
    readfile($arquivo);
    @unlink($arquivo);
    exit;
}

/* ================================================================== */
/* Tela                                                                */
/* ================================================================== */
$porPagina = 50;
$pagina    = max(1, (int) ($_GET['p'] ?? 1));
try {
    $total   = $rel->contar($filtros);
    $linhas  = $rel->linhas($filtros, 'tela', $porPagina, ($pagina - 1) * $porPagina);
    $resumo  = $rel->resumo($filtros);
    $setores = $rel->setores();
} catch (Throwable $e) {
    error_log($e->getMessage());
    flash('danger', 'Erro ao montar o relatório: ' . $e->getMessage());
    $total = 0; $linhas = []; $resumo = []; $setores = [];
}
$paginas = max(1, (int) ceil($total / $porPagina));
$colunas = $rel->colunas('tela');
$base    = array_filter($filtros, fn($v) => $v !== '');

// Links das chaves para as fichas
$fichas = ['s__id_submissao' => 'selagem', 'd__numero_selo' => 'domicilios', 'so__id_submissao' => 'socio', 'c__id_submissao' => 'caracterizacao'];

$celula = function (array $c, array $l) use ($rel, $fichas): string {
    if ($c['id'] === 'sit_situacao') {
        $p = $rel->pendencias($l);
        if (!$p) {
            return '<span class="badge badge-success"><i class="fas fa-check"></i> Completo</span>';
        }
        return implode(' ', array_map(fn($x) => '<span class="badge badge-' . ($x === 'Cadastro duplicado' ? 'warning' : 'danger') . '">' . e($x) . '</span>', $p));
    }
    if ($c['id'] === 'sit_socio' || $c['id'] === 'sit_caract') {
        $t = $rel->texto($c, $l);
        $cls = $t === 'Sim' ? 'success' : ($t === 'Não' ? 'danger' : ($t === '—' ? 'light' : 'warning'));
        return '<span class="badge badge-' . $cls . '">' . e($t) . '</span>';
    }
    $t = $rel->texto($c, $l);
    if (isset($fichas[$c['id']]) && $t !== '' && pode($fichas[$c['id']], 'ver')) {
        return '<a href="' . e(url('form.php', ['t' => $fichas[$c['id']], 'id' => $l[$c['id']]])) . '">' . e($t) . '</a>';
    }
    return e($t);
};

$tituloPagina = 'Relatório Geral';
$menuAtivo    = 'relatorio_geral';
require __DIR__ . '/includes/topo.php';
?>

<div class="card card-reurb shadow mb-4 reurb-lista reurb-relatorio">
    <div class="card-header">
        <h6><i class="fas fa-table"></i>Relatório Geral - <?= e(cfg('nome_ocupacao')) ?></h6>
        <?php if ($podeExportar): ?>
            <div class="btn-group">
                <a class="btn btn-outline-light btn-sm" href="<?= e(url('relatorio.php', $base + ['exportar' => 'resumido'])) ?>"
                   title="Colunas principais de cada cadastro">
                    <i class="fas fa-file-excel"></i> Excel resumido
                </a>
                <a class="btn btn-outline-light btn-sm" href="<?= e(url('relatorio.php', $base + ['exportar' => 'completo'])) ?>"
                   title="Todas as colunas de lote, domicílio, sócio jurídico e caracterização">
                    <i class="fas fa-file-excel"></i> Excel completo
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <?php $faltam = $rel->indicesFaltando(); if ($faltam): ?>
            <div class="alert alert-warning small">
                <strong><i class="fas fa-tachometer-alt"></i> Relatório lento:</strong>
                faltam índices no banco de dados. Com alguns milhares de registros, esta tela pode levar minutos para abrir.
                <?php if (pode('usuario', 'alterar')): ?>
                    Execute no MySQL (uma única vez):
                    <pre class="mb-0 mt-2 p-2 bg-white border rounded small"><?= e(implode("\n", $faltam)) ?></pre>
                <?php else: ?>
                    Avise um administrador.
                <?php endif; ?>
            </div>
        <?php endif; ?>
        <!-- Filtros -->
        <form method="get" class="form-row align-items-end mb-2">
            <div class="form-group col-lg-4 col-md-6 mb-2">
                <label class="small mb-1" for="f_q">Pesquisar</label>
                <input type="search" id="f_q" name="q" class="form-control" value="<?= e($filtros['q']) ?>"
                       placeholder="Selo, lote, endereço, morador, CPF, titular...">
            </div>
            <div class="form-group col-lg-3 col-md-6 mb-2">
                <label class="small mb-1" for="f_setor">Rua / Zona / Setor</label>
                <select id="f_setor" name="setor" class="form-control">
                    <option value="">Todos</option>
                    <?php foreach ($setores as $s): ?>
                        <option value="<?= e($s) ?>"<?= $filtros['setor'] === (string) $s ? ' selected' : '' ?>><?= e($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-lg-3 col-md-6 mb-2">
                <label class="small mb-1" for="f_sit">Situação</label>
                <select id="f_sit" name="situacao" class="form-control">
                    <option value="">Todas</option>
                    <?php foreach ($rel->situacoes() as $k => $t): ?>
                        <option value="<?= e($k) ?>"<?= $filtros['situacao'] === $k ? ' selected' : '' ?>><?= e($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group col-lg-2 col-md-6 mb-2">
                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-filter fa-sm"></i> Filtrar</button>
            </div>
        </form>

        <!-- Resumo (clicar filtra pela situação) -->
        <?php if ($resumo): ?>
            <div class="reurb-pendencias mb-3">
                <span class="small text-gray-600 mr-1">
                    <?= number_format($resumo['lotes'], 0, ',', '.') ?> lote(s) ·
                    <?= number_format($resumo['domicilios'], 0, ',', '.') ?> domicílio(s)
                    <?php if ($base): ?> · <a href="relatorio.php">limpar filtros</a><?php endif; ?>
                </span>
                <?php foreach ($rel->situacoes() as $k => $t):
                    if ($k === 'pendente') continue;
                    $qtd = $resumo[$k] ?? 0;
                    $ativo = $filtros['situacao'] === $k;
                    $cor = $k === 'completo' ? 'success' : ($k === 'duplicado' ? 'warning' : 'danger');
                    if ($qtd === 0 && !$ativo && $k !== 'completo') continue;   // esconde pendências zeradas
                    ?>
                    <a href="<?= e(url('relatorio.php', array_merge($base, ['situacao' => $ativo ? null : $k, 'p' => null]))) ?>"
                       class="btn btn-sm <?= $ativo ? 'btn-' . $cor : 'btn-outline-' . $cor ?>">
                        <?= e($t) ?> <span class="badge <?= $ativo ? 'badge-light' : 'badge-' . $cor ?>"><?= number_format($qtd, 0, ',', '.') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="small text-gray-600 mb-2">
            <?= number_format($total, 0, ',', '.') ?> linha(s)<?= $total > $porPagina ? ' · página ' . $pagina . ' de ' . $paginas : '' ?>
            · uma linha por domicílio; lote sem domicílio aparece em uma linha com o domicílio em branco.
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-3">
                <thead>
                <tr class="reurb-rel-grupos">
                    <?php
                    $grupos = [];
                    foreach ($colunas as $c) {
                        $n = count($grupos);
                        if ($n && $grupos[$n - 1][0] === $c['grupo']) { $grupos[$n - 1][1]++; } else { $grupos[] = [$c['grupo'], 1]; }
                    }
                    foreach ($grupos as [$g, $n]): ?>
                        <th colspan="<?= $n ?>"><?= e($g) ?></th>
                    <?php endforeach; ?>
                </tr>
                <tr class="thead-light">
                    <?php foreach ($colunas as $c): ?><th><?= e($c['label']) ?></th><?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php if (!$linhas): ?>
                    <tr><td colspan="<?= count($colunas) ?>" class="text-center text-gray-500 py-4">Nenhum registro encontrado.</td></tr>
                <?php endif; ?>
                <?php foreach ($linhas as $l): ?>
                    <tr<?= $rel->pendencias($l) ? '' : ' class="reurb-rel-ok"' ?>>
                        <?php foreach ($colunas as $c): ?>
                            <td class="<?= $c['id'] === 'sit_situacao' ? 'col-pendencias' : ($c['p'] === 'sit' ? 'col-indicador' : '') ?>"><?= $celula($c, $l) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($paginas > 1): ?>
            <nav aria-label="Paginação">
                <ul class="pagination pagination-sm justify-content-end flex-wrap">
                    <?php
                    $link = fn(int $p) => e(url('relatorio.php', $base + ['p' => $p]));
                    $ini  = max(1, $pagina - 3);
                    $fim  = min($paginas, $pagina + 3);
                    ?>
                    <li class="page-item<?= $pagina <= 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= $link(max(1, $pagina - 1)) ?>">Anterior</a></li>
                    <?php for ($i = $ini; $i <= $fim; $i++): ?>
                        <li class="page-item<?= $i === $pagina ? ' active' : '' ?>"><a class="page-link" href="<?= $link($i) ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                    <li class="page-item<?= $pagina >= $paginas ? ' disabled' : '' ?>"><a class="page-link" href="<?= $link(min($paginas, $pagina + 1)) ?>">Próxima</a></li>
                </ul>
            </nav>
        <?php endif; ?>

        <?php if ($podeExportar): ?>
            <p class="small text-gray-600 mb-0">
                <i class="fas fa-info-circle"></i>
                A exportação usa os mesmos filtros da tela. <strong>Resumido</strong>: colunas principais.
                <strong>Completo</strong>: todas as colunas das quatro tabelas.
                <?php if (!Xlsx::disponivel()): ?>
                    <br><i class="fas fa-exclamation-triangle text-warning"></i>
                    A extensão <code>zip</code> do PHP não está instalada neste servidor: a planilha sai em CSV (abre no Excel).
                <?php endif; ?>
                A planilha contém dados pessoais (nomes, CPFs, rendas); cada exportação fica registrada na auditoria.
            </p>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
