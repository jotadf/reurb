<?php
ob_start(); 
// Inicia sessao
session_start();

require_once('./actions/Login.php');
require_once('./dto/Usuario.php');

$db_login = new Login();
$usuario = new Usuario();

// Recupera o login
$cpf = isset($_POST["cpf"]) ? addslashes(trim($_POST["cpf"])) : FALSE;
// Recupera a senha
$senha = isset($_POST["senha"]) ? trim($_POST["senha"]) : FALSE;

;
if($usuario = $db_login->logar($cpf, $senha)){
    // Sessão nova e limpa: nada do usuário anterior pode sobrar
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['usuario_id']   = (int) $usuario->id;
    $_SESSION['usuario_nome'] = $usuario->nome;
    $_SESSION['usuario'] = serialize($usuario);
    header('Location: /reurb_new/index.php');   
    exit; 
}  else {
    header('Location: /form_login.php?error=1'); 
    exit;
}


