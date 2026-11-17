<?php
require_once './verifica_login.php';
require_once './actions/ManterUsuario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireciona('usuarios.php');
csrf_valida();
exige_perfil(1);
$id = (int) ($_POST['id'] ?? 0);
if ($id === (int) $usuario_logado->id) {
    flash('danger', 'Você não pode excluir o próprio usuário.');
} else {
    (new ManterUsuario())->excluir($id);
    flash('success', 'Usuário excluído.');
}
redireciona('usuarios.php');
