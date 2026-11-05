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

    $erroBanco = false;
    try {
        // Busca TODOS os usuários com esse e-mail ou CPF (o CPF pode estar repetido na tabela)
        $st = db()->prepare('SELECT id, nome, senha, ativo FROM usuario
                              WHERE TRIM(email) = ?
                                 OR (? <> \'\' AND REPLACE(REPLACE(REPLACE(REPLACE(cpf, \'.\', \'\'), \'-\', \'\'), \' \', \'\'), \'/\', \'\') = ?)
                              ORDER BY ativo DESC, id DESC');
        $st->execute([$login, $cpf, $cpf]);
        $encontrados = $st->fetchAll();

        $u = null;
        $detalhes = [];
        foreach ($encontrados as $c) {
            if ((string) $c['ativo'] !== '1') {
                $detalhes[] = 'id ' . $c['id'] . ': inativo (ativo = ' . var_export($c['ativo'], true) . ')';
            } elseif (!senha_confere($senha, (string) $c['senha'])) {
                $detalhes[] = 'id ' . $c['id'] . ': senha não confere. ' . senha_explicar_diferenca($senha, (string) $c['senha']);
            } else {
                $u = $c;
                break;
            }
        }

        if (!$encontrados) {
            $motivo = 'Nenhum usuário encontrado com o e-mail ou CPF "' . $login . '"'
                    . ($cpf !== '' ? ' (CPF pesquisado só com números: ' . $cpf . ')' : '') . '.';
        } elseif ($u === null) {
            $motivo = count($encontrados) . ' usuário(s) encontrado(s). ' . implode(' | ', $detalhes);
        } else {
            // Login correto
            if (cfg('converter_senha_no_login', false)) {
                $gravado = (string) $u['senha'];
                if (senha_detectar_formato($gravado) !== 'password_hash' || password_needs_rehash($gravado, PASSWORD_DEFAULT)) {
                    db()->prepare('UPDATE usuario SET senha = ? WHERE id = ?')
                        ->execute([password_hash($senha, PASSWORD_DEFAULT), $u['id']]);
                }
            }
            // Sessão nova e limpa: nada do usuário anterior pode sobrar
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION[cfg('sessao_id_usuario')]   = (int) $u['id'];
            $_SESSION[cfg('sessao_nome_usuario')] = $u['nome'];
            redirecionar('index.php');
        }
    } catch (PDOException $e) {
        $erroBanco = true;
        $c = cfg('db');
        $motivo = 'Erro no banco de dados (host ' . $c['host'] . ', banco ' . $c['banco'] . ', usuário ' . $c['usuario'] . '): '
                . $e->getMessage();
    }

    error_log('[REURB login] ' . $login . ': ' . $motivo);
    // Falha de conexão não é culpa do usuário: mostra uma mensagem diferente
    $erro = $erroBanco
        ? 'Não foi possível acessar o banco de dados. Verifique a conexão em config/config.php.'
        : 'Usuário ou senha inválidos.';
}

// Diagnóstico: true = sempre; 'local' = só quando acessado do próprio servidor; false = nunca
$diag = cfg('login_diagnostico', 'local');
$ipCliente = $_SERVER['REMOTE_ADDR'] ?? '';
$mostrarDiagnostico = $diag === true
    || ($diag === 'local' && in_array($ipCliente, ['127.0.0.1', '::1'], true));
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
                    <?php
                    $conectado = '';
                    if ($_SERVER['REQUEST_METHOD'] !== 'POST' && usuario_id() !== null) {
                        try { $conectado = usuario_nome(); } catch (Throwable $e) { $conectado = ''; }
                    }
                    if ($conectado !== ''): ?>
                        <div class="alert alert-info small">
                            Você ainda está conectado como <strong><?= e($conectado) ?></strong>.
                            Ao entrar com outro usuário, essa sessão é encerrada.
                            <a href="logout.php">Sair agora</a>
                        </div>
                    <?php endif; ?>
                    <?php foreach (flashes() as $f): ?>
                        <div class="alert alert-<?= e($f['tipo']) ?> small"><?= e($f['msg']) ?></div>
                    <?php endforeach; ?>
                    <?php if ($erro): ?><div class="alert alert-danger small"><?= e($erro) ?></div><?php endif; ?>
                    <?php if ($motivo !== '' && $mostrarDiagnostico): ?>
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
