<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$pasta = isset($_REQUEST['pasta']) ? $_REQUEST['pasta'] : "";

//echo "Chegou!!";

if(!empty($_FILES['file'])){
    $caminho = "./arquivos/" . $pasta . "/";
    echo $caminho;
    $arquivo = basename($_FILES['file']['name']);
    $targetFilePath = $caminho . $arquivo;
    if(move_uploaded_file($_FILES['file']['tmp_name'], $targetFilePath)){
        echo 'Arquivo enviado';
    }
}

//header('Location: gerenciar_arquivos_ponto.php');
