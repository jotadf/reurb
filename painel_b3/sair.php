<?php
// Removendo usuário da sessão
session_start();
$_SESSION = [];
session_unset();
session_destroy();

// Redireciona para login
header('Location: form_login.php');
exit;
