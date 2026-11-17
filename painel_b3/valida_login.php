<?php
ob_start();
require_once __DIR__ . '/funcoes.php';
require_once __DIR__ . '/actions/Login.php';
config();
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redireciona('form_login.php');
}
csrf_valida();

// Pequena pausa contra tentativas automatizadas em sequência
$_SESSION['tentativas'] = ($_SESSION['tentativas'] ?? 0) + 1;
if ($_SESSION['tentativas'] > 5) {
    sleep(min(10, $_SESSION['tentativas'] - 5));
}

try {
    $usuario = (new Login())->logar($_POST['cpf'] ?? '', $_POST['senha'] ?? '');
} catch (Throwable $e) {
    redireciona('form_login.php?error=2');
}

if ($usuario) {
    session_regenerate_id(true);
    $_SESSION['usuario'] = serialize($usuario);
    unset($_SESSION['tentativas']);
    redireciona('index.php');
}
redireciona('form_login.php?error=1');
