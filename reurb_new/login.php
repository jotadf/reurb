<?php
/**
 * Login simples pela tabela usuario (e-mail ou CPF + senha).
 * Se o seu sistema já tem login, não use este arquivo: basta ajustar
 * 'sessao_id_usuario' e 'sessao_nome_usuario' em config/config.php.
 *
 * Senhas: aceita password_hash, md5, sha1, sha256, sha512 ou texto puro
 * (ver lib/senha.php). Se o login falhar, ligue 'login_diagnostico' na
 * configuração para ver o motivo.
 */
require __DIR__ . '/lib/bootstrap.php';

$erro = '';
$motivo = '';   // detalhe técnico, só exibido com 'login_diagnostico' = true
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $login = trim((string) ($_POST['login'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $cpf   = preg_replace('/\D/', '', $login);

    try {
        $st = db()->prepare('SELECT id, nome, senha, ativo FROM usuario
                              WHERE email = ? OR (? <> \'\' AND REPLACE(REPLACE(REPLACE(cpf, \'.\', \'\'), \'-\', \'\'), \' \', \'\') = ?)
                              LIMIT 1');
        $st->execute([$login, $cpf, $cpf]);
        $u = $st->fetch();

        if (!$u) {
            $motivo = 'Nenhum usuário encontrado com esse e-mail ou CPF.';
        } elseif ((string) $u['ativo'] !== '1') {
            $motivo = 'Usuário encontrado (id ' . $u['id'] . '), mas está inativo (ativo = ' . var_export($u['ativo'], true) . ').';
        } elseif (!senha_confere($senha, (string) $u['senha'])) {
            $gravado = trim((string) $u['senha']);
            $formatoCfg = (string) cfg('senha_formato', 'auto');
            $motivo = 'Usuário encontrado (id ' . $u['id'] . '), mas a senha não confere. '
                    . ($formatoCfg === 'auto'
                        ? 'Formato reconhecido no banco: ' . senha_detectar_formato($gravado)
                        : 'Formato configurado: ' . $formatoCfg)
                    . ' (senha gravada com ' . strlen((string) $u['senha']) . ' caracteres). '
                    . 'Se o sistema atual usa outra regra (ex.: senha com "sal"), me informe qual.';
        } else {
            // Login correto
            if (cfg('converter_senha_no_login', false)) {
                $gravado = (string) $u['senha'];
                if (senha_detectar_formato($gravado) !== 'password_hash' || password_needs_rehash($gravado, PASSWORD_DEFAULT)) {
                    db()->prepare('UPDATE usuario SET senha = ? WHERE id = ?')
                        ->execute([password_hash($senha, PASSWORD_DEFAULT), $u['id']]);
                }
            }
            session_regenerate_id(true);
            $_SESSION[cfg('sessao_id_usuario')]   = (int) $u['id'];
            $_SESSION[cfg('sessao_nome_usuario')] = $u['nome'];
            redirecionar('index.php');
        }
    } catch (PDOException $e) {
        $motivo = 'Erro ao consultar o banco: ' . $e->getMessage();
    }

    error_log('[REURB login] ' . $login . ': ' . $motivo);
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
                    <?php if ($motivo !== '' && cfg('login_diagnostico', false)): ?>
                        <div class="alert alert-warning small"><strong>Diagnóstico:</strong> <?= e($motivo) ?></div>
                    <?php endif; ?>
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
