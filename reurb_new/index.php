<?php
/** Painel inicial com o total de registros de cada tela. */
require __DIR__ . '/lib/bootstrap.php';
exigir_login();

$cards = [];
foreach ($GLOBALS['TABELAS'] as $chave => $cfgTabela) {
    if (!pode($chave, 'ver')) {
        continue;
    }
    try {
        $total = (int) db()->query('SELECT COUNT(*) FROM `' . str_replace('`', '``', $cfgTabela['tabela']) . '`')->fetchColumn();
    } catch (Throwable $e) {
        $total = null;
    }
    $cards[] = ['chave' => $chave, 'cfg' => $cfgTabela, 'total' => $total];
}

$tituloPagina = 'Início';
$menuAtivo    = '';
require __DIR__ . '/includes/topo.php';
?>

<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800"><?= e(cfg('nome_ocupacao')) ?></h1>
</div>

<?php if (!$cards): ?>
    <div class="alert alert-warning">
        Seu perfil (<?= e(perfil_nome() ?: 'sem perfil') ?>) não tem acesso a nenhuma tela.
        Peça a um administrador para ajustar seu perfil.
    </div>
<?php endif; ?>

<div class="row">
    <?php foreach ($cards as $c): ?>
        <div class="col-xl-3 col-md-6 mb-4">
            <a href="<?= e(url('lista.php', ['t' => $c['chave']])) ?>" class="text-decoration-none">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1"><?= e($c['cfg']['titulo']) ?></div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                    <?= $c['total'] === null ? '<span class="text-danger small">tabela indisponível</span>' : number_format($c['total'], 0, ',', '.') ?>
                                </div>
                            </div>
                            <div class="col-auto"><i class="fas <?= e($c['cfg']['icone'] ?? 'fa-table') ?> fa-2x text-gray-300"></i></div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php require __DIR__ . '/includes/rodape.php'; ?>
