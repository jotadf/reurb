<?php
/**
 * Ficha genérica (incluir / editar / visualizar):
 *   form.php?t=<chave>            -> novo registro
 *   form.php?t=<chave>&id=<valor> -> edição
 */
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/campos_html.php';
exigir_login();

$t    = $_GET['t'] ?? null;
$crud = crud_ou_404(is_string($t) ? $t : null);

$id     = isset($_GET['id']) && is_string($_GET['id']) && $_GET['id'] !== '' ? $_GET['id'] : null;
$novo   = $id === null;
$erros  = [];
$linha  = null;

if ($novo && $crud->somenteLeitura) {
    redirecionar(url('lista.php', ['t' => $crud->chave]));
}

if (!$novo) {
    $linha = $crud->buscar($id);
    if ($linha === null) {
        flash('warning', 'Registro não encontrado.');
        redirecionar(url('lista.php', ['t' => $crud->chave]));
    }
}

/* ---------------- Gravação ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$crud->somenteLeitura) {
    csrf_validar();
    try {
        if ($novo) {
            $novoId = $crud->inserir($_POST);
            flash('success', 'Registro incluído com sucesso.');
            redirecionar(url('form.php', ['t' => $crud->chave, 'id' => $novoId]));
        }
        $mudou = $crud->atualizar($id, $_POST);
        flash($mudou ? 'success' : 'info', $mudou ? 'Alterações salvas com sucesso.' : 'Nenhuma alteração para salvar.');
        redirecionar(url('form.php', ['t' => $crud->chave, 'id' => $id]));
    } catch (ErroValidacao $e) {
        $erros = $e->erros;
        flash('danger', $e->getMessage());
    } catch (Throwable $e) {
        error_log($e->getMessage());
        flash('danger', $e->getMessage());
    }
    // Reexibe o que foi digitado
    $linha = array_merge($linha ?? [], array_intersect_key($_POST, $crud->campos));
}

/* ---------------- Valores iniciais de um novo registro ---------------- */
if ($novo && $linha === null) {
    $linha = $crud->valoresPadrao();          // DEFAULT das colunas (ex.: numero_residentes = 1)
    foreach ($crud->campos as $nome => $c) {
        if (isset($_GET[$nome]) && is_string($_GET[$nome])) {
            $linha[$nome] = $_GET[$nome];   // ex.: novo domicílio já ligado ao lote
        }
    }
}

$relacionados = !$novo ? $crud->relacionados($linha) : [];

$tituloFicha  = $crud->cfg['ficha'] ?? $crud->cfg['titulo'];
$tituloPagina = $tituloFicha;
$menuAtivo    = $crud->chave;
require __DIR__ . '/includes/topo.php';
?>

<div class="reurb-ficha-wrap">
    <form method="post" class="card card-reurb shadow-sm reurb-ficha" novalidate
          action="<?= e(url('form.php', ['t' => $crud->chave, 'id' => $id])) ?>">
        <?= csrf_campo() ?>

        <div class="card-header">
            <h6>
                <i class="fas fa-edit"></i><?= e($tituloFicha) ?> - <?= e(cfg('nome_ocupacao')) ?>
                <?php if ($novo): ?><span class="badge badge-light ml-2">Novo</span><?php endif; ?>
            </h6>
            <a href="<?= e(url('lista.php', ['t' => $crud->chave])) ?>" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="card-body">
            <?php
            // Monta o HTML de cada seção e conta os erros de cada uma
            $secoesHtml  = [];
            $secoesErros = [];
            foreach ($crud->secoes() as $titulo => $campos) {
                $html = '';
                foreach ($campos as $nome) {
                    $html .= campo_html($crud, $nome, $linha[$nome] ?? null, $novo, $erros[$nome] ?? null, $crud->somenteLeitura);
                }
                if ($html !== '') {
                    $secoesHtml[$titulo]  = $html;
                    $secoesErros[$titulo] = count(array_intersect($campos, array_keys($erros)));
                }
            }
            $abas = $crud->abas(array_keys($secoesHtml));
            ?>

            <?php if (!$abas): ?>
                <?php foreach ($secoesHtml as $titulo => $html): ?>
                    <?php if ($titulo !== ''): ?><div class="reurb-secao"><?= e($titulo) ?></div><?php endif; ?>
                    <div class="form-row"><?= $html ?></div>
                <?php endforeach; ?>
            <?php else:
                // Com erro de validação, abre a primeira aba que tem erro
                $abaComErro = null;
                foreach ($abas as $i => $aba) {
                    $n = array_sum(array_map(fn($s) => $secoesErros[$s], $aba['secoes']));
                    $abas[$i]['erros'] = $n;
                    if ($n > 0 && $abaComErro === null) {
                        $abaComErro = $i;
                    }
                }
                $abaAtiva = $abaComErro ?? 0;
                $prefixo  = 'aba-' . $crud->chave . '-';
                ?>
                <ul class="nav nav-tabs reurb-abas" role="tablist"
                    data-memoria="<?= e($crud->chave . ':' . ($id ?? 'novo')) ?>"
                    data-forcar="<?= $abaComErro !== null ? '1' : '0' ?>">
                    <?php foreach ($abas as $i => $aba): ?>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link<?= $i === $abaAtiva ? ' active' : '' ?>" id="<?= e($prefixo . $i) ?>-tab"
                               data-toggle="tab" href="#<?= e($prefixo . $i) ?>" role="tab"
                               aria-controls="<?= e($prefixo . $i) ?>" aria-selected="<?= $i === $abaAtiva ? 'true' : 'false' ?>">
                                <?= e($aba['titulo']) ?>
                                <?php if ($aba['erros'] > 0): ?>
                                    <span class="badge badge-danger ml-1" title="Campos com erro"><?= $aba['erros'] ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <div class="tab-content reurb-abas-conteudo">
                    <?php foreach ($abas as $i => $aba): ?>
                        <div class="tab-pane fade<?= $i === $abaAtiva ? ' show active' : '' ?>" id="<?= e($prefixo . $i) ?>"
                             role="tabpanel" aria-labelledby="<?= e($prefixo . $i) ?>-tab">
                            <?php foreach ($aba['secoes'] as $s): ?>
                                <?php if (count($aba['secoes']) > 1 || $s !== $aba['titulo']): ?>
                                    <div class="reurb-secao"><?= e($s) ?></div>
                                <?php endif; ?>
                                <div class="form-row"><?= $secoesHtml[$s] ?></div>
                            <?php endforeach; ?>
                            <div class="reurb-abas-navegar">
                                <?php if ($i > 0): ?>
                                    <button type="button" class="btn btn-link btn-sm px-0" data-ir-aba="<?= e($prefixo . ($i - 1)) ?>">
                                        <i class="fas fa-chevron-left fa-sm"></i> <?= e($abas[$i - 1]['titulo']) ?>
                                    </button>
                                <?php endif; ?>
                                <?php if ($i < count($abas) - 1): ?>
                                    <button type="button" class="btn btn-link btn-sm px-0 ml-auto" data-ir-aba="<?= e($prefixo . ($i + 1)) ?>">
                                        <?= e($abas[$i + 1]['titulo']) ?> <i class="fas fa-chevron-right fa-sm"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (!$crud->somenteLeitura): ?>
            <div class="reurb-rodape-ficha">
                <?php if (!$novo && !$crud->somenteLeitura): ?>
                    <button type="button" class="btn btn-outline-danger"
                            data-excluir-t="<?= e($crud->chave) ?>" data-excluir-id="<?= e($id) ?>"
                            data-excluir-desc="<?= e($crud->campos[$crud->pk]['label'] . ' ' . $id) ?>">
                        <i class="fas fa-trash-alt"></i> Excluir
                    </button>
                <?php endif; ?>
                <div class="acoes-direita">
                    <?php if (!$crud->somenteLeitura): ?>
                        <button type="submit" class="btn btn-salvar">
                            <i class="fas fa-save"></i> <?= $novo ? 'Salvar Registro' : 'Salvar Alterações' ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </form>

    <?php foreach ($relacionados as $r):
        /** @var Crud $outro */
        $outro = $r['crud'];
        $cols  = $outro->colunasLista();
        ?>
        <div class="card shadow-sm mt-4 reurb-relacionados">
            <div class="card-header py-3 d-flex align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">
                    <i class="fas <?= e($outro->cfg['icone'] ?? 'fa-table') ?> mr-1"></i>
                    <?= e($r['rel']['titulo']) ?> <span class="badge badge-primary"><?= count($r['linhas']) ?></span>
                </h6>
                <?php if (!$outro->somenteLeitura): ?>
                    <a class="btn btn-primary btn-sm"
                       href="<?= e(url('form.php', ['t' => $outro->chave, $r['rel']['coluna'] => $r['valor']])) ?>">
                        <i class="fas fa-plus"></i> Novo
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body p-0">
                <?php if (!$r['linhas']): ?>
                    <p class="text-gray-500 text-center my-3 small">Nenhum registro vinculado.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm mb-0">
                            <thead class="thead-light">
                            <tr>
                                <?php foreach ($cols as $c): ?><th><?= e($outro->campos[$c]['label']) ?></th><?php endforeach; ?>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($r['linhas'] as $l): ?>
                                <tr>
                                    <?php foreach ($cols as $c): ?>
                                        <td>
                                            <?php if ($c === $outro->pk): ?>
                                                <a href="<?= e(url('form.php', ['t' => $outro->chave, 'id' => $l[$c]])) ?>"><?= e($l[$c]) ?></a>
                                            <?php else: ?>
                                                <?= e($outro->exibir($c, $l[$c])) ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
