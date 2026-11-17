<?php
require_once './verifica_login.php';
require_once './actions/ManterUsuario.php';
exige_perfil(1);

$m = new ManterUsuario();
$lista = $m->listar();
$perfis = $m->perfis();

$titulo = 'Usuários';
include './layout_topo.php';
?>
<h1 class="h3 mb-4 text-gray-800">Usuários</h1>

<div class="collapse" id="form_usuario">
    <div class="card mb-4 border-primary shadow" style="max-width: 900px">
        <?= card_cabecalho('fas fa-user-edit', '<span id="tituloForm">Novo usuário</span>') ?>
        <div class="card-body">
            <form method="post" action="save_usuario.php" id="formUsuario">
                <?= csrf_campo() ?>
                <input type="hidden" name="id" id="us_id" value="0">
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="us_cpf" class="small font-weight-bold">CPF</label>
                        <input type="text" class="form-control form-control-sm mascara-cpf" id="us_cpf" name="cpf" required>
                    </div>
                    <div class="form-group col-md-5">
                        <label for="us_nome" class="small font-weight-bold">Nome</label>
                        <input type="text" class="form-control form-control-sm" id="us_nome" name="nome" maxlength="120" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="us_email" class="small font-weight-bold">E-mail</label>
                        <input type="email" class="form-control form-control-sm" id="us_email" name="email" maxlength="160">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-3">
                        <label for="us_perfil" class="small font-weight-bold">Perfil</label>
                        <select class="form-control form-control-sm" id="us_perfil" name="id_perfil">
                            <?php foreach ($perfis as $p) { ?><option value="<?= (int) $p['id'] ?>" <?= $p['id'] == 2 ? 'selected' : '' ?>><?= e($p['perfil']) ?></option><?php } ?>
                        </select>
                    </div>
                    <div class="form-group col-md-4">
                        <label for="us_senha" class="small font-weight-bold">Senha <span class="text-gray-500 font-weight-normal" id="dicaSenha">(mínimo 6 caracteres)</span></label>
                        <input type="password" class="form-control form-control-sm" id="us_senha" name="senha" autocomplete="new-password">
                    </div>
                    <div class="form-group col-md-2 d-flex align-items-end">
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" class="custom-control-input" id="us_ativo" name="ativo" value="1" checked>
                            <label class="custom-control-label" for="us_ativo">Ativo</label>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i>Salvar</button>
                <button type="button" class="btn btn-secondary btn-sm" id="btnCancelar">Cancelar</button>
            </form>
        </div>
    </div>
</div>

<div class="card mb-4 border-primary shadow" style="max-width: 900px">
    <?= card_cabecalho('fas fa-user', 'Usuários',
        '<button id="btn_cadastrar" class="btn btn-outline-light btn-sm" type="button" title="Novo usuário"><i class="fa fa-plus-circle text-white"></i></button>') ?>
    <div class="card-body">
        <table id="usuarios" class="table table-sm table-striped table-bordered dt-responsive nowrap" style="width:100%">
            <thead><tr><th style="width:5%">ID</th><th>CPF</th><th>Nome</th><th>Perfil</th><th>Ativo</th><th style="width:12%">Opções</th></tr></thead>
            <tbody>
            <?php foreach ($lista as $u) { ?>
                <tr>
                    <td><?= (int) $u['id'] ?></td>
                    <td><?= e(formata_cpf($u['cpf'])) ?></td>
                    <td><?= e($u['nome']) ?><?php if ($u['email']) { ?><div class="small text-gray-500"><?= e($u['email']) ?></div><?php } ?></td>
                    <td><?= e($u['perfil']) ?></td>
                    <td><?= $u['ativo'] ? '<span class="badge badge-success">Sim</span>' : '<span class="badge badge-secondary">Não</span>' ?></td>
                    <td class="text-nowrap">
                        <button type="button" class="btn btn-sm btn-outline-primary btn-editar" title="Alterar"
                            data-id="<?= (int) $u['id'] ?>" data-cpf="<?= e(formata_cpf($u['cpf'])) ?>" data-nome="<?= e($u['nome']) ?>"
                            data-email="<?= e($u['email']) ?>" data-perfil="<?= (int) $u['id_perfil'] ?>" data-ativo="<?= (int) $u['ativo'] ?>"><i class="fas fa-edit"></i></button>
                        <?php if ((int) $u['id'] !== (int) $usuario_logado->id) { ?>
                        <button type="button" class="btn btn-sm btn-outline-danger btn-excluir" title="Excluir"
                            data-id="<?= (int) $u['id'] ?>" data-nome="<?= e($u['nome']) ?>"><i class="fas fa-trash"></i></button>
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="confirm" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="modal-content" method="post" action="del_usuario.php">
            <?= csrf_campo() ?>
            <input type="hidden" name="id" id="del_id">
            <div class="modal-header"><h5 class="modal-title">Excluir usuário</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button></div>
            <div class="modal-body">Deseja excluir <strong id="nome_excluir"></strong>? A carteira dele também será apagada.</div>
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
    $('#usuarios').DataTable({ order: [[2, 'asc']], columnDefs: [{ responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }, { orderable: false, targets: -1 }] });
    function limpa() {
        $('#us_id').val(0); $('#formUsuario')[0].reset(); $('#tituloForm').text('Novo usuário');
        $('#us_senha').prop('required', true); $('#dicaSenha').text('(mínimo 6 caracteres)');
    }
    $('#btn_cadastrar').on('click', function () { limpa(); $('#form_usuario').collapse('show'); });
    $('#btnCancelar').on('click', function () { limpa(); $('#form_usuario').collapse('hide'); });
    $(document).on('click', '.btn-editar', function () {
        var b = $(this);
        $('#us_id').val(b.data('id')); $('#us_cpf').val(b.data('cpf')); $('#us_nome').val(b.data('nome'));
        $('#us_email').val(b.data('email')); $('#us_perfil').val(b.data('perfil')); $('#us_ativo').prop('checked', b.data('ativo') == 1);
        $('#us_senha').val('').prop('required', false); $('#dicaSenha').text('(deixe em branco para manter)');
        $('#tituloForm').text('Alterar ' + b.data('nome'));
        $('#form_usuario').collapse('show');
    });
    $(document).on('click', '.btn-excluir', function () {
        $('#del_id').val($(this).data('id')); $('#nome_excluir').text($(this).data('nome')); $('#confirm').modal('show');
    });
});
</script>
</body>
</html>
