<?php
require_once './verifica_login.php';
require_once './actions/ManterUsuario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireciona('usuarios.php');
csrf_valida();
exige_perfil(1);
try {
    $dados = $_POST;
    // O administrador não pode rebaixar nem desativar a si mesmo
    if ((int) ($dados['id'] ?? 0) === (int) $usuario_logado->id) {
        $dados['id_perfil'] = $usuario_logado->perfil;
        $dados['ativo'] = 1;
    }
    (new ManterUsuario())->salvar($dados);
    flash('success', 'Usuário salvo.');
} catch (InvalidArgumentException $e) {
    flash('danger', $e->getMessage());
}
redireciona('usuarios.php');
