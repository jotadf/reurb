<?php
require_once './verifica_login.php';
require_once './actions/ManterAtivo.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redireciona('ativos.php');
csrf_valida();
exige_perfil(1);
(new ManterAtivo())->excluir((int) ($_POST['id'] ?? 0));
flash('success', 'Ativo removido da lista. O histórico já baixado continua no banco.');
redireciona('ativos.php');
