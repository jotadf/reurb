<?php
require_once './verifica_login.php';
require_once './actions/ManterUsuario.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_valida();
    $m = new ManterUsuario();
    $nova = (string) ($_POST['nova'] ?? '');
    if (!$m->confereSenha($usuario_logado->id, (string) ($_POST['atual'] ?? ''))) {
        flash('danger', 'A senha atual não confere.');
    } elseif ($nova !== (string) ($_POST['confirma'] ?? '')) {
        flash('danger', 'A confirmação não é igual à nova senha.');
    } else {
        try {
            $m->alterarSenha($usuario_logado->id, $nova);
            flash('success', 'Senha alterada.');
            redireciona('index.php');
        } catch (InvalidArgumentException $e) {
            flash('danger', $e->getMessage());
        }
    }
    redireciona('altera_senha.php');
}

$titulo = 'Alterar senha';
include './layout_topo.php';
?>
<h1 class="h3 mb-4 text-gray-800">Alterar senha</h1>
<div class="card mb-4 border-primary shadow" style="max-width: 480px">
    <?= card_cabecalho('fas fa-key', 'Nova senha') ?>
    <div class="card-body">
        <form method="post">
            <?= csrf_campo() ?>
            <div class="form-group">
                <label for="atual" class="small font-weight-bold">Senha atual</label>
                <input type="password" class="form-control form-control-sm" id="atual" name="atual" autocomplete="current-password" required>
            </div>
            <div class="form-group">
                <label for="nova" class="small font-weight-bold">Nova senha</label>
                <input type="password" class="form-control form-control-sm" id="nova" name="nova" minlength="6" autocomplete="new-password" required>
            </div>
            <div class="form-group">
                <label for="confirma" class="small font-weight-bold">Confirme a nova senha</label>
                <input type="password" class="form-control form-control-sm" id="confirma" name="confirma" minlength="6" autocomplete="new-password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save mr-1"></i>Salvar</button>
        </form>
    </div>
</div>
<?php include './layout_base.php'; ?>
</body>
</html>
