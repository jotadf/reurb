<?php
require_once './verifica_login.php';
require_once './actions/ManterAtivo.php';
require_once './actions/ServicoMercado.php';

$lista = (new ManterAtivo())->listar(false);
$admin = $usuario_logado->perfil <= 1;

$titulo = 'Ativos monitorados';
include './layout_topo.php';
?>
<h1 class="h3 mb-4 text-gray-800">Ativos monitorados</h1>

<?php if ($admin) { ?>
<div class="collapse" id="form_ativo">
    <div class="card mb-4 border-primary shadow" style="max-width: 900px">
        <?= card_cabecalho('fas fa-edit', '<span id="tituloForm">Novo ativo</span>') ?>
        <div class="card-body">
            <form method="post" action="save_ativo.php" id="formAtivo" data-carregando>
                <?= csrf_campo() ?>
                <input type="hidden" name="id" id="at_id" value="0">
                <div class="form-row">
                    <div class="form-group col-md-2">
                        <label for="at_ticker" class="small font-weight-bold">Código</label>
                        <input type="text" class="form-control form-control-sm text-uppercase" id="at_ticker" name="ticker" maxlength="15" placeholder="PETR4" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="at_nome" class="small font-weight-bold">Nome <span class="text-gray-500 font-weight-normal">(opcional, a API preenche)</span></label>
                        <input type="text" class="form-control form-control-sm" id="at_nome" name="nome" maxlength="120">
                    </div>
                    <div class="form-group col-md-3">
                        <label for="at_setor" class="small font-weight-bold">Setor</label>
                        <input type="text" class="form-control form-control-sm" id="at_setor" name="setor" maxlength="80">
                    </div>
                    <div class="form-group col-md-1">
                        <label for="at_ordem" class="small font-weight-bold">Ordem</label>
                        <input type="number" class="form-control form-control-sm" id="at_ordem" name="ordem" value="0">
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-end">
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" id="at_ativo" name="ativo" value="1" checked>
                            <label class="custom-control-label" for="at_ativo">Ativo</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i>Salvar</button>
                <button type="button" class="btn btn-secondary btn-sm" id="btnCancelar">Cancelar</button>
            </form>
        </div>
    </div>
</div>
<?php } ?>

<div class="card mb-4 border-primary shadow" style="max-width: 900px">
    <?= card_cabecalho('fas fa-list', 'Lista de acompanhamento',
        $admin ? '<button id="btn_cadastrar" class="btn btn-outline-light btn-sm" type="button" title="Novo ativo"><i class="fa fa-plus-circle text-white"></i></button>' : '') ?>
    <div class="card-body">
        <table id="tabelaAtivos" class="table table-sm table-striped table-bordered dt-responsive nowrap" style="width:100%">
            <thead><tr><th style="width:8%">Ordem</th><th>Código</th><th>Nome</th><th>Setor</th><th>Situação</th><th style="width:15%">Opções</th></tr></thead>
            <tbody>
            <?php foreach ($lista as $a) { ?>
                <tr>
                    <td><?= (int) $a['ordem'] ?></td>
                    <td><a class="ticker" href="ativo.php?t=<?= e(rawurlencode($a['ticker'])) ?>"><?= e($a['ticker']) ?></a></td>
                    <td><?= e($a['nome']) ?></td>
                    <td><?= e($a['setor']) ?></td>
                    <td><?= $a['ativo'] ? '<span class="badge badge-success">Monitorado</span>' : '<span class="badge badge-secondary">Pausado</span>' ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="ativo.php?t=<?= e(rawurlencode($a['ticker'])) ?>" title="Analisar"><i class="fas fa-chart-area"></i></a>
                        <?php if ($admin) { ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary btn-editar" title="Alterar"
                            data-id="<?= (int) $a['id'] ?>" data-ticker="<?= e($a['ticker']) ?>" data-nome="<?= e($a['nome']) ?>"
                            data-setor="<?= e($a['setor']) ?>" data-ordem="<?= (int) $a['ordem'] ?>" data-ativo="<?= (int) $a['ativo'] ?>"><i class="fas fa-edit"></i></button>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-excluir" title="Excluir"
                            data-id="<?= (int) $a['id'] ?>" data-nome="<?= e($a['ticker']) ?>"><i class="fas fa-trash"></i></button>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
        <?php if (!$admin) { ?><div class="small text-gray-500 mt-2">Somente administradores alteram a lista.</div><?php } ?>
    </div>
</div>

<?php if ($admin) { ?>
<div class="modal fade" id="confirm" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="modal-content" method="post" action="del_ativo.php">
            <?= csrf_campo() ?>
            <input type="hidden" name="id" id="del_id">
            <div class="modal-header"><h5 class="modal-title">Excluir ativo</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button></div>
            <div class="modal-body">Deseja retirar <strong id="nome_excluir"></strong> da lista de acompanhamento?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-danger">Excluir</button>
            </div>
        </form>
    </div>
</div>
<?php } ?>

<?php include './layout_base.php'; ?>
<script>
$(function () {
    $('#tabelaAtivos').DataTable({ order: [[0, 'asc']], columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }, { orderable: false, targets: -1 }] });
    function limpa() { $('#at_id').val(0); $('#formAtivo')[0].reset(); $('#tituloForm').text('Novo ativo'); }
    $('#btn_cadastrar').on('click', function () { limpa(); $('#form_ativo').collapse('show'); $('#at_ticker').focus(); });
    $('#btnCancelar').on('click', function () { limpa(); $('#form_ativo').collapse('hide'); });
    $(document).on('click', '.btn-editar', function () {
        var b = $(this);
        $('#at_id').val(b.data('id')); $('#at_ticker').val(b.data('ticker')); $('#at_nome').val(b.data('nome'));
        $('#at_setor').val(b.data('setor')); $('#at_ordem').val(b.data('ordem')); $('#at_ativo').prop('checked', b.data('ativo') == 1);
        $('#tituloForm').text('Alterar ' + b.data('ticker'));
        $('#form_ativo').collapse('show');
    });
    $(document).on('click', '.btn-excluir', function () {
        $('#del_id').val($(this).data('id')); $('#nome_excluir').text($(this).data('nome')); $('#confirm').modal('show');
    });
});
</script>
</body>
</html>
