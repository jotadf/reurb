<?php
require_once './verifica_login.php';
require_once './actions/ManterCarteira.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireciona('carteira.php');
csrf_valida();
(new ManterCarteira())->excluir($usuario_logado->id, (int) ($_POST['id'] ?? 0));
flash('success', 'Posição excluída.');
redireciona('carteira.php');
