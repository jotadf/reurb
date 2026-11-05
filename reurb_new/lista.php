<?php
/**
 * Listagem genérica: lista.php?t=<chave da tela>
 * Parâmetros opcionais: q (pesquisa), o (ordenar por), d (asc|desc), p (página)
 * e os filtros configurados em 'filtros' (ex.: &id_submissao_pai=761081201).
 */
require __DIR__ . '/lib/bootstrap.php';
exigir_login();

$t    = $_GET['t'] ?? null;
$crud = crud_ou_404(is_string($t) ? $t : null);

exigir_permissao($crud->chave, 'ver');
$podeIncluir = !$crud->somenteLeitura && pode($crud->chave, 'incluir');
$podeAlterar = !$crud->somenteLeitura && pode($crud->chave, 'alterar');
$podeExcluir = !$crud->somenteLeitura && pode($crud->chave, 'excluir');

$busca  = trim((string) ($_GET['q'] ?? ''));
$ordem  = isset($_GET['o']) && is_string($_GET['o']) ? $_GET['o'] : null;
$dir    = ($_GET['d'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
$pagina = max(1, (int) ($_GET['p'] ?? 1));
$porPag = (int) cfg('registros_pagina', 25);

$filtros = [];
foreach ($crud->filtrosPermitidos() as $f) {
    if (isset($_GET[$f]) && is_string($_GET[$f]) && $_GET[$f] !== '') {
        $filtros[$f] = $_GET[$f];
    }
}

// Indicadores de vínculo (ex.: selagem sem domicílio) e filtro de pendência
$indicadores = $crud->indicadores();
$pendencia   = isset($_GET['pend']) && is_string($_GET['pend'])
            && ($_GET['pend'] === 'qualquer' || isset($indicadores[$_GET['pend']])) ? $_GET['pend'] : '';
$pendencias  = [];

try {
    [$linhas, $total] = $crud->listar($busca, $filtros, $ordem, $dir, $pagina, $porPag, $pendencia);
    $pendencias = $crud->contarPendencias($busca, $filtros);
} catch (Throwable $e) {
    error_log($e->getMessage());
    $linhas = [];
    $total  = 0;
    flash('danger', 'Erro ao consultar ' . $crud->cfg['titulo'] . ': ' . $e->getMessage());
}
$paginas  = max(1, (int) ceil($total / $porPag));
$colunas  = $crud->colunasLista();

// Parâmetros atuais (para montar links mantendo pesquisa/filtros)
$base = array_merge(['t' => $crud->chave, 'q' => $busca, 'o' => $ordem, 'd' => $ordem ? $dir : null, 'pend' => $pendencia], $filtros);

/** Selo da coluna de situação de um indicador */
$seloIndicador = function (array $ind, array $linha) use ($crud): string {
    $qtd   = (int) ($linha[$ind['alias']] ?? 0);
    $valor = $linha[$ind['campo_local']] ?? null;
    $tela  = $ind['tela'];
    // Sem vínculo: selo vermelho (com atalho para cadastrar, se o perfil puder)
    if ($qtd === 0) {
        $selo = '<span class="badge badge-danger"><i class="fas fa-exclamation-circle"></i> ' . e($ind['falta']) . '</span>';
        if (pode($tela, 'incluir') && empty($GLOBALS['TABELAS'][$tela]['somente_leitura'])) {
            $selo .= ' <a class="small" title="Cadastrar agora" href="'
                   . e(url('form.php', ['t' => $tela, $ind['coluna'] => $valor])) . '"><i class="fas fa-plus-circle"></i></a>';
        }
        return $selo;
    }
    // Com vínculo: link para os registros ligados
    $href = url('lista.php', ['t' => $tela, $ind['coluna'] => $valor]);
    if (($ind['mostrar'] ?? 'quantidade') === 'sim') {
        $selo = $qtd === 1
            ? '<span class="badge badge-success"><i class="fas fa-check"></i> Sim</span>'
            : '<span class="badge badge-warning" title="Mais de um cadastro para o mesmo registro">' . $qtd . ' cadastros</span>';
    } else {
        $declarado = $ind['declarado'] !== null ? (int) ($linha[$ind['declarado']] ?? 0) : 0;
        $selo = $declarado > $qtd
            ? '<span class="badge badge-warning" title="Cadastrados / declarados no lote">' . $qtd . ' de ' . $declarado . '</span>'
            : '<span class="badge badge-success">' . $qtd . '</span>';
    }
    return pode($tela, 'ver') ? '<a href="' . e($href) . '" class="text-decoration-none">' . $selo . '</a>' : $selo;
};

$tituloPagina = $crud->cfg['titulo'];
$menuAtivo    = $crud->chave;
require __DIR__ . '/includes/topo.php';
?>

<div class="card card-reurb shadow mb-4 reurb-lista">
    <div class="card-header">
        <h6><i class="fas <?= e($crud->cfg['icone'] ?? 'fa-table') ?>"></i><?= e($crud->cfg['titulo']) ?> - <?= e(cfg('nome_ocupacao')) ?></h6>
        <?php if ($podeIncluir): ?>
            <a href="<?= e(url('form.php', array_merge(['t' => $crud->chave], $filtros))) ?>" class="btn btn-outline-light btn-sm">
                <i class="fas fa-plus"></i> Novo registro
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <form method="get" class="form-row align-items-center mb-3" role="search">
            <input type="hidden" name="t" value="<?= e($crud->chave) ?>">
            <?php if ($pendencia !== ''): ?><input type="hidden" name="pend" value="<?= e($pendencia) ?>"><?php endif; ?>
            <?php foreach ($filtros as $f => $v): ?>
                <input type="hidden" name="<?= e($f) ?>" value="<?= e($v) ?>">
            <?php endforeach; ?>
            <div class="col-md-6 col-lg-5 mb-2">
                <div class="input-group">
                    <input type="search" name="q" class="form-control" value="<?= e($busca) ?>"
                           placeholder="Pesquisar por <?= e(implode(', ', array_map(fn($c) => mb_strtolower($crud->campos[$c]['label'] ?? $c), array_slice(array_filter($crud->cfg['busca'] ?? [], fn($c) => isset($crud->campos[$c])), 0, 3)))) ?>..."
                           aria-label="Pesquisar">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-search fa-sm"></i></button>
                    </div>
                </div>
            </div>
            <div class="col mb-2 text-md-right small text-gray-600">
                <?= number_format($total, 0, ',', '.') ?> registro(s)
                <?php if ($busca !== '' || $filtros || $pendencia !== ''): ?>
                    &middot; <a href="<?= e(url('lista.php', ['t' => $crud->chave])) ?>">limpar pesquisa</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($filtros): ?>
            <div class="mb-3">
                <?php foreach ($filtros as $f => $v): ?>
                    <span class="badge badge-primary p-2 mr-1">
                        <?= e($crud->campos[$f]['label']) ?>: <?= e($crud->exibir($f, $v)) ?>
                    </span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($pendencias):
            $rotulos = array_map(fn($i) => $i['falta'] === 'Falta' ? 'Sem ' . mb_strtolower($i['titulo']) : $i['falta'], $indicadores);
            $rotulos['qualquer'] = 'Com alguma pendência';
            ?>
            <div class="reurb-pendencias mb-3" role="group" aria-label="Filtrar por pendência">
                <span class="small text-gray-600 mr-1">Pendências:</span>
                <a href="<?= e(url('lista.php', array_merge($base, ['pend' => null, 'p' => null]))) ?>"
                   class="btn btn-sm <?= $pendencia === '' ? 'btn-primary' : 'btn-outline-primary' ?>">Todos</a>
                <?php foreach ($pendencias as $id => $qtd): ?>
                    <a href="<?= e(url('lista.php', array_merge($base, ['pend' => $id, 'p' => null]))) ?>"
                       class="btn btn-sm <?= $pendencia === $id ? 'btn-danger' : 'btn-outline-danger' ?>">
                        <?= e($rotulos[$id]) ?> <span class="badge <?= $pendencia === $id ? 'badge-light' : 'badge-danger' ?>"><?= number_format($qtd, 0, ',', '.') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="table-responsive">
            <table class="table table-bordered table-hover table-sm mb-3">
                <thead class="thead-light">
                <tr>
                    <?php foreach ($colunas as $n => $c):
                        $ativa   = $ordem === $c;
                        $novaDir = $ativa && $dir === 'asc' ? 'desc' : 'asc';
                        ?>
                        <th scope="col">
                            <a href="<?= e(url('lista.php', array_merge($base, ['o' => $c, 'd' => $novaDir, 'p' => null]))) ?>">
                                <?= e($crud->campos[$c]['label']) ?>
                                <?php if ($ativa): ?><i class="fas fa-sort-<?= $dir === 'asc' ? 'up' : 'down' ?> fa-sm"></i><?php endif; ?>
                            </a>
                        </th>
                        <?php if ($n === 0): // situação logo após a 1ª coluna, sempre à vista ?>
                            <?php foreach ($indicadores as $ind): ?>
                                <th scope="col" class="col-indicador"><?= e($ind['titulo']) ?></th>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <th scope="col" class="col-acoes">Ações</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!$linhas): ?>
                    <tr><td colspan="<?= count($colunas) + count($indicadores) + 1 ?>" class="text-center text-gray-500 py-4">Nenhum registro encontrado.</td></tr>
                <?php endif; ?>
                <?php foreach ($linhas as $l):
                    $id   = $l[$crud->pk];
                    $link = url('form.php', ['t' => $crud->chave, 'id' => $id]);
                    ?>
                    <tr>
                        <?php foreach ($colunas as $n => $c): ?>
                            <td><?php if ($c === $crud->pk): ?><a href="<?= e($link) ?>"><?= e($crud->exibir($c, $l[$c])) ?></a><?php else: ?><?= e($crud->exibir($c, $l[$c])) ?><?php endif; ?></td>
                            <?php if ($n === 0): ?>
                                <?php foreach ($indicadores as $ind): ?>
                                    <td class="col-indicador"><?= $seloIndicador($ind, $l) ?></td>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <td class="col-acoes">
                            <a href="<?= e($link) ?>" class="btn btn-primary btn-sm" title="<?= $podeAlterar ? 'Editar' : 'Ver' ?>">
                                <i class="fas <?= $podeAlterar ? 'fa-edit' : 'fa-eye' ?>"></i>
                            </a>
                            <?php if ($podeExcluir): ?>
                                <button type="button" class="btn btn-danger btn-sm" title="Excluir"
                                        data-excluir-t="<?= e($crud->chave) ?>" data-excluir-id="<?= e($id) ?>"
                                        data-excluir-desc="<?= e($crud->campos[$crud->pk]['label'] . ' ' . $id) ?>">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($paginas > 1): ?>
            <nav aria-label="Paginação">
                <ul class="pagination pagination-sm justify-content-end flex-wrap">
                    <?php
                    $link = fn(int $p) => e(url('lista.php', array_merge($base, ['p' => $p])));
                    $ini  = max(1, $pagina - 3);
                    $fim  = min($paginas, $pagina + 3);
                    ?>
                    <li class="page-item<?= $pagina <= 1 ? ' disabled' : '' ?>"><a class="page-link" href="<?= $link(max(1, $pagina - 1)) ?>">Anterior</a></li>
                    <?php if ($ini > 1): ?>
                        <li class="page-item"><a class="page-link" href="<?= $link(1) ?>">1</a></li>
                        <?php if ($ini > 2): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $ini; $i <= $fim; $i++): ?>
                        <li class="page-item<?= $i === $pagina ? ' active' : '' ?>"><a class="page-link" href="<?= $link($i) ?>"><?= $i ?></a></li>
                    <?php endfor; ?>
                    <?php if ($fim < $paginas): ?>
                        <?php if ($fim < $paginas - 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; ?>
                        <li class="page-item"><a class="page-link" href="<?= $link($paginas) ?>"><?= $paginas ?></a></li>
                    <?php endif; ?>
                    <li class="page-item<?= $pagina >= $paginas ? ' disabled' : '' ?>"><a class="page-link" href="<?= $link(min($paginas, $pagina + 1)) ?>">Próxima</a></li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
