<?php
require_once __DIR__ . '/funcoes.php';
config();
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();
if (!empty($_SESSION['usuario'])) {
    redireciona('index.php');
}
$erro = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title><?= e(config('app.nome')) ?> - Login</title>
        <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800&display=swap" rel="stylesheet">
        <link href="css/sb-admin-2.min.css" rel="stylesheet">
        <link href="css/painel.css" rel="stylesheet">
        <link rel="icon" href="img/favicon.svg" type="image/svg+xml">
    </head>

    <body class="alert-dark">
        <div class="container d-flex justify-content-center">

            <!-- Exibe form de login -->
            <div class="card mt-5 mb-3 border-primary shadow w-100" style="max-width: 460px;">
                <div class="card-body bg-gradient-primary" style="min-height: 5.0rem;">
                    <div class="row align-items-center">
                        <div class="col ml-2">
                            <div class="h3 mb-0 text-white font-weight-bold">Acesso ao <?= e(config('app.nome')) ?></div>
                            <div class="small text-white-50">Cotações e análises da bolsa brasileira</div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-lock fa-3x text-white"></i>
                        </div>
                    </div>
                </div>
                <div class="card-body px-4">
                    <?php if ($erro === '1') { ?>
                        <div class="alert alert-danger small">Usuário ou senha inválidos!</div>
                    <?php } elseif ($erro === '2') { ?>
                        <div class="alert alert-warning small">Não foi possível conectar ao banco de dados. Verifique o arquivo config/config.php.</div>
                    <?php } ?>
                    <form id="form_login" action="valida_login.php" method="post" data-carregando>
                        <?= csrf_campo() ?>
                        <div class="form-group">
                            <label for="cpf" class="small font-weight-bold text-gray-700">CPF</label>
                            <input type="text" name="cpf" class="form-control mascara-cpf" id="cpf" inputmode="numeric" autocomplete="username" required autofocus>
                        </div>
                        <div class="form-group">
                            <label for="senha" class="small font-weight-bold text-gray-700">Senha</label>
                            <input type="password" name="senha" class="form-control" id="senha" autocomplete="current-password" required>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block mt-4">Entrar</button>
                    </form>
                </div>
            </div>
            <!-- fim da exibição -->
        </div>

        <script src="vendor/jquery/jquery.min.js"></script>
        <script src="vendor/bootstrap/bootstrap.bundle.min.js"></script>
        <script src="vendor/jquery-mask/jquery.mask.min.js"></script>
        <script src="js/painel.js"></script>
    </body>
</html>
