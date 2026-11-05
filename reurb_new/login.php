<?php
/**
 * Login simples pela tabela usuario (e-mail ou CPF + senha).
 * Se o seu sistema já tem login, não use este arquivo: basta ajustar
 * 'sessao_id_usuario' e 'sessao_nome_usuario' em config/config.php.
 *
 * Senhas: aceita hash do password_hash(). Se a senha antiga estiver em MD5,
 * o login funciona e a senha é convertida para password_hash automaticamente.
 */
require __DIR__ . '/lib/bootstrap.php';

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $login = trim((string) ($_POST['login'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $cpf   = preg_replace('/\D/', '', $login);

    $st = db()->prepare('SELECT id, nome, senha, ativo FROM usuario
                          WHERE email = ? OR (? <> \'\' AND REPLACE(REPLACE(REPLACE(cpf, \'.\', \'\'), \'-\', \'\'), \' \', \'\') = ?)
                          LIMIT 1');
    $st->execute([$login, $cpf, $cpf]);
    $u = $st->fetch();

    $ok = false;
    if ($u && (int) $u['ativo'] === 1) {
        $hash = (string) $u['senha'];
        if (password_verify($senha, $hash)) {
            $ok = true;
        } elseif (preg_match('/^[a-f0-9]{32}$/i', $hash) && hash_equals(strtolower($hash), md5($senha))) {
            $ok = true;   // senha legada em MD5: converte
            db()->prepare('UPDATE usuario SET senha = ? WHERE id = ?')->execute([password_hash($senha, PASSWORD_DEFAULT), $u['id']]);
        }
    }

    if ($ok) {
        session_regenerate_id(true);
        $_SESSION[cfg('sessao_id_usuario')]   = (int) $u['id'];
        $_SESSION[cfg('sessao_nome_usuario')] = $u['nome'];
        redirecionar('index.php');
    }
    $erro = 'Usuário ou senha inválidos.';
}
$tema = rtrim((string) cfg('tema_base'), '/') . '/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Entrar | <?= e(cfg('app_nome')) ?></title>
    <link href="<?= e($tema) ?>vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800,900" rel="stylesheet">
    <link href="<?= e($tema) ?>css/sb-admin-2.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-primary">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-xl-5 col-lg-6 col-md-8">
            <div class="card o-hidden border-0 shadow-lg my-5">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <i class="fas fa-desktop fa-2x text-primary"></i>
                        <h1 class="h4 text-gray-900 mt-2"><?= e(cfg('app_nome')) ?></h1>
                    </div>
                    <?php if ($erro): ?><div class="alert alert-danger small"><?= e($erro) ?></div><?php endif; ?>
                    <form method="post" class="user">
                        <?= csrf_campo() ?>
                        <div class="form-group">
                            <input type="text" name="login" class="form-control form-control-user" placeholder="E-mail ou CPF"
                                   value="<?= e($_POST['login'] ?? '') ?>" required autofocus>
                        </div>
                        <div class="form-group">
                            <input type="password" name="senha" class="form-control form-control-user" placeholder="Senha" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-user btn-block">Entrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
