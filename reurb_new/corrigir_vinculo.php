<?php
/**
 * Corrigir o vínculo de um registro (ex.: código do selo de um cadastro
 * sociojurídico ou de uma caracterização que não existe em Domicílios).
 *   corrigir_vinculo.php?t=socio&id=<id_submissao>
 * Configuração: 'corrigir_vinculo' em config/tabelas.php.
 */
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/vinculos.php';
exigir_login();

$t    = $_GET['t'] ?? null;
$crud = crud_ou_404(is_string($t) ? $t : null);
if (empty($crud->cfg['corrigir_vinculo']) || $crud->somenteLeitura) {
    http_response_code(404);
    exit('Esta tela não tem correção de vínculo.');
}
exigir_permissao($crud->chave, 'alterar');

$cfgV   = $crud->cfg['corrigir_vinculo'];
$campo  = $cfgV['campo'];
$rotulo = $cfgV['rotulo'] ?? 'vínculo';
$id     = isset($_GET['id']) && is_string($_GET['id']) ? $_GET['id'] : '';
$linha  = $id !== '' ? $crud->buscar($id) : null;
if ($linha === null) {
    flash('warning', 'Registro não encontrado.');
    redirecionar(url('lista.php', ['t' => $crud->chave]));
}

/** Para onde voltar: só listagem/ficha do próprio CRUD (nunca endereço externo). */
$voltarPadrao = url('lista.php', ['t' => $crud->chave, 'pend' => 'sem_domicilio']);
$voltar = (string) ($_POST['voltar'] ?? '');
if ($voltar === '' && !empty($_SERVER['HTTP_REFERER'])) {
    $ref = parse_url((string) $_SERVER['HTTP_REFERER']);
    $pag = basename((string) ($ref['path'] ?? ''));
    if (in_array($pag, ['lista.php', 'form.php'], true)) {
        $voltar = $pag . (isset($ref['query']) ? '?' . $ref['query'] : '');
    }
}
if (!preg_match('/^(lista|form)\.php(\?[^\s<>"]*)?$/', $voltar)) {
    $voltar = $voltarPadrao;
}

$erro = '';
$digitado = '';

/* ---------------- Gravação ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $digitado = trim((string) ($_POST['novo_valor'] ?? ''));
    $anterior = (string) ($linha[$campo] ?? '');
    try {
        if ($digitado === '') {
            throw new ErroValidacao([$campo => 'Informe o ' . $rotulo . ' correto.'], 'Informe o ' . $rotulo . ' correto.');
        }
        // Já existe outro registro desta tela com esse selo? (aviso, não bloqueia)
        $st = db()->prepare('SELECT COUNT(*) FROM `' . str_replace('`', '``', $crud->tabela) . '` WHERE `'
                          . str_replace('`', '``', $campo) . '` = ? AND `' . str_replace('`', '``', $crud->pk) . '` <> ?');
        $st->execute([$digitado, $id]);
        $outros = (int) $st->fetchColumn();

        $crud->atualizar($id, [$campo => $digitado]);   // valida a existência do selo e grava na auditoria
        flash('success', ucfirst($rotulo) . ' corrigido: "' . $anterior . '" → "' . $digitado . '".');
        if ($outros > 0) {
            flash('warning', 'Atenção: o ' . $rotulo . ' "' . $digitado . '" já tinha ' . $outros
                . ' outro(s) registro(s) em ' . $crud->cfg['titulo'] . '. Verifique se há cadastro duplicado.');
        }
        redirecionar($voltar);
    } catch (ErroValidacao $e) {
        $erro = $e->erros[$campo] ?? $e->getMessage();
    } catch (Throwable $e) {
        error_log($e->getMessage());
        $erro = $e->getMessage();
    }
}

$dados   = vinculo_sugestoes($crud, $linha);
/** @var Crud $destino */
$destino = $dados['destino'];
$atual   = (string) ($linha[$campo] ?? '');
$existe  = $atual !== '' && $crud->vinculoExiste($campo, $atual);

$tituloPagina = 'Corrigir ' . $rotulo;
$menuAtivo    = $crud->chave;
require __DIR__ . '/includes/topo.php';
?>

<div class="reurb-ficha-wrap">
    <div class="card card-reurb shadow-sm reurb-ficha">
        <div class="card-header">
            <h6><i class="fas fa-wrench"></i>Corrigir <?= e($rotulo) ?> - <?= e($crud->cfg['ficha'] ?? $crud->cfg['titulo']) ?></h6>
            <a href="<?= e($voltar) ?>" class="btn btn-outline-light btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
        </div>
        <div class="card-body">

            <!-- Registro que está sendo corrigido -->
            <div class="reurb-secao">Registro</div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label><?= e($crud->campos[$crud->pk]['label']) ?>:</label>
                    <div class="input-group">
                        <input type="text" class="form-control" value="<?= e($id) ?>" readonly>
                        <div class="input-group-append">
                            <a class="btn btn-outline-primary" title="Abrir a ficha" href="<?= e(url('form.php', ['t' => $crud->chave, 'id' => $id])) ?>"><i class="fas fa-external-link-alt"></i></a>
                        </div>
                    </div>
                </div>
                <div class="form-group col-md-8">
                    <label><?= e($crud->campos[$campo]['label']) ?> gravado:</label>
                    <input type="text" class="form-control <?= $existe ? 'is-valid' : 'is-invalid' ?>" value="<?= e($atual) ?>" readonly>
                    <div class="<?= $existe ? 'valid' : 'invalid' ?>-feedback d-block">
                        <?= $existe
                            ? 'Este ' . e($rotulo) . ' existe em ' . e($destino->cfg['titulo']) . '.'
                            : ($atual === '' ? 'Nenhum ' . e($rotulo) . ' gravado.' : 'Não existe em ' . e($destino->cfg['titulo']) . '.') ?>
                    </div>
                </div>
                <?php foreach ($cfgV['exibir'] ?? [] as $c): if (!isset($crud->campos[$c])) continue; ?>
                    <div class="form-group col-md-6">
                        <label><?= e($crud->campos[$c]['label']) ?>:</label>
                        <input type="text" class="form-control" value="<?= e($crud->exibir($c, $linha[$c] ?? null)) ?>" readonly>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Sugestões -->
            <div class="reurb-secao">Sugestões em <?= e($destino->cfg['titulo']) ?></div>
            <?php if (!$dados['sugestoes']): ?>
                <p class="text-gray-600 small mb-4">
                    Nenhum <?= e($rotulo) ?> parecido encontrado<?= !empty($cfgV['cpf']) ? ', nem domicílio com o mesmo CPF ou nome' : '' ?>.
                    Informe o <?= e($rotulo) ?> correto abaixo.
                </p>
            <?php else: ?>
                <div class="table-responsive mb-4">
                    <table class="table table-sm table-hover table-bordered mb-0 reurb-sugestoes">
                        <thead class="thead-light">
                        <tr>
                            <?php foreach ($dados['colunas'] as $c): ?><th><?= e($destino->campos[$c]['label']) ?></th><?php endforeach; ?>
                            <th>Por que foi sugerido</th>
                            <th class="text-center"><?= e($crud->cfg['titulo']) ?></th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($dados['sugestoes'] as $s): ?>
                            <tr>
                                <?php foreach ($dados['colunas'] as $i => $c): ?>
                                    <td>
                                        <?php if ($i === 0): ?>
                                            <a href="<?= e(url('form.php', ['t' => $destino->chave, 'id' => $s['valor']])) ?>" target="_blank" rel="noopener"><strong><?= e($s['valor']) ?></strong></a>
                                        <?php else: ?>
                                            <?= e($destino->exibir($c, $s['linha'][$c] ?? null)) ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <?php foreach ($s['motivos'] as $m): ?>
                                        <span class="badge badge-info mb-1"><?= e($m) ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($s['vinculados'] > 0): ?>
                                        <span class="badge badge-warning" title="Escolher este selo pode gerar cadastro duplicado">Já tem <?= $s['vinculados'] ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-light">Nenhum</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <form method="post" class="m-0">
                                        <?= csrf_campo() ?>
                                        <input type="hidden" name="voltar" value="<?= e($voltar) ?>">
                                        <input type="hidden" name="novo_valor" value="<?= e($s['valor']) ?>">
                                        <button type="submit" class="btn btn-salvar btn-sm"><i class="fas fa-check"></i> Usar este</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Informar manualmente -->
            <div class="reurb-secao">Informar o <?= e($rotulo) ?> correto</div>
            <form method="post" class="form-row align-items-start">
                <?= csrf_campo() ?>
                <input type="hidden" name="voltar" value="<?= e($voltar) ?>">
                <div class="form-group col-md-8">
                    <input type="text" name="novo_valor" class="form-control<?= $erro ? ' is-invalid' : '' ?>" list="lista-vinculos"
                           value="<?= e($digitado) ?>" placeholder="Digite para buscar entre os <?= e($rotulo) ?>s de <?= e(mb_strtolower($destino->cfg['titulo'])) ?>" required autocomplete="off">
                    <datalist id="lista-vinculos">
                        <?php foreach ($dados['todos'] as $v): ?><option value="<?= e($v) ?>"><?php endforeach; ?>
                    </datalist>
                    <?php if ($erro): ?><div class="invalid-feedback d-block"><?= e($erro) ?></div><?php endif; ?>
                    <small class="form-text text-muted">Só é aceito um <?= e($rotulo) ?> que exista em <?= e($destino->cfg['titulo']) ?>. A correção fica registrada na auditoria.</small>
                </div>
                <div class="form-group col-md-4">
                    <button type="submit" class="btn btn-salvar btn-block"><i class="fas fa-save"></i> Salvar <?= e($rotulo) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
