<?php
require_once './verifica_login.php';
require_once './actions/ManterCarteira.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireciona('carteira.php');
csrf_valida();
try {
    (new ManterCarteira())->salvar($usuario_logado->id, $_POST);
    flash('success', 'Posição salva.');
} catch (InvalidArgumentException $e) {
    flash('danger', $e->getMessage());
}
redireciona('carteira.php');
