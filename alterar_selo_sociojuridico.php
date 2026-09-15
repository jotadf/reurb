<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

require_once('verifica_login.php');
include_once('actions/ManterRelatorio.php');

$manterRelatorio = new ManterRelatorio();
   
// 🔥 ALTERADO: Pega o estado do formulário enviado pelo input hidden
$id= (int)$_REQUEST['id'];
$selo = trim($_REQUEST['selo']);
$selo_atual = trim($_REQUEST['selo_atual']);

if ($id <= 0 || empty($selo)) {
    echo "<script>alert('ID e Selo são obrigatórios.'); window.history.back();</script>";
    exit();
}

$sql = "UPDATE cadastro_sociojuridico_import SET codigo_selo = '{$selo}' WHERE id_submissao = " . $id;
//echo $sql;
//exit();
$resultado = $manterRelatorio->db->Execute($sql);
$mensagem = "Selo atualizado com sucesso!";

if ($resultado) {
    $sql_log = "INSERT INTO auditoria (tabela, acao, usuario, valor_antigo, valor_novo, atualizado) 
    VALUES ('cadastro_sociojuridico_import', 'UPDATE', '{$usuario_logado->id}', '{$selo_atual}', '{$selo}', now())";
    $manterRelatorio->db->Execute($sql_log);
    echo "<script>alert('{$mensagem}'); window.location.href = 'sociojuridicos.php';</script>";
    exit();
} else {
    echo "<script>alert('Erro ao persistir dados na tabela Sóciojurídico.'); window.history.back();</script>";
    exit();
}
