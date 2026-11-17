<?php
ob_start();
require_once __DIR__ . '/funcoes.php';
require_once __DIR__ . '/dto/Usuario.php';
config(); // carrega configuração e fuso horário

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
    session_start();
}

if (empty($_SESSION['usuario'])) {
    // Usuário não logado: redireciona para a página de login
    header('Location: form_login.php');
    exit;
}
$usuario_logado = unserialize($_SESSION['usuario'], ['allowed_classes' => ['Usuario']]);
if (!$usuario_logado instanceof Usuario) {
    session_destroy();
    header('Location: form_login.php');
    exit;
}

// Erros inesperados (ex.: banco fora do ar) viram uma mensagem amigável.
set_exception_handler(function ($e) {
    error_log('[Painel B3] ' . $e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<div style="font-family:Nunito,Arial,sans-serif;max-width:640px;margin:60px auto;padding:24px;border:1px solid #e74a3b;border-radius:8px;color:#5a5c69">'
        . '<h3 style="color:#e74a3b;margin-top:0">Não foi possível carregar esta página</h3>'
        . '<p>Ocorreu um erro ao acessar o banco de dados ou a API de cotações. Verifique a configuração em <code>config/config.php</code> e tente novamente.</p>'
        . '<p style="font-size:12px;color:#858796">Detalhe técnico: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>'
        . '<a href="index.php">Voltar ao início</a></div>';
});
