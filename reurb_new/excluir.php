<?php
/** Exclusão (somente via POST com token CSRF). */
require __DIR__ . '/lib/bootstrap.php';
exigir_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}
csrf_validar();

$t    = $_POST['t'] ?? null;
$crud = crud_ou_404(is_string($t) ? $t : null);
$id   = $_POST['id'] ?? '';

if ($crud->somenteLeitura || !is_string($id) || $id === '') {
    redirecionar(url('lista.php', ['t' => $crud->chave]));
}
exigir_permissao($crud->chave, 'excluir');

try {
    proteger_ultimo_admin($crud, $id, null);
    $crud->excluir($id);
    flash('success', 'Registro excluído com sucesso.');
    redirecionar(url('lista.php', ['t' => $crud->chave]));
} catch (Throwable $e) {
    error_log($e->getMessage());
    flash('danger', $e->getMessage());
    redirecionar(url('form.php', ['t' => $crud->chave, 'id' => $id]));
}
