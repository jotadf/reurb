<?php

require_once('./actions/ManterUsuario.php');
require_once('./dto/Usuario.php');

$db_usuario = new ManterUsuario();
$usuario = new Usuario();

$id     = isset($_POST['id']) ? $_POST['id'] : 0;
$cpf    = addslashes($_POST['cpf']);
$nome   = addslashes($_POST['nome']);
$senha  = $_POST['senha'];
$email  = $_POST['email'];
$equipe = $_POST['equipe'];
$perfil = $_POST['perfil'];
$ativo  = isset($_POST['ativo']) ? $_POST['ativo'] : 1;

$usuario->id      = $id;
$usuario->cpf     = $cpf;
$usuario->nome    = $nome;
$usuario->senha   = $senha;   
$usuario->email   = $email;
$usuario->equipe  = $equipe;
$usuario->perfil  = $perfil;
$usuario->ativo   = $ativo;

//print_r($usuario);
$db_usuario->salvar($usuario);

header('Location: usuarios.php');

