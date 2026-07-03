<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

require_once('verifica_login.php');
include_once('actions/ManterRelatorio.php');

$manterRelatorio = new ManterRelatorio();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 🔥 ALTERADO: Pega o estado do formulário enviado pelo input hidden
    $isEdit = isset($_POST['is_edit']) ? (int)$_POST['is_edit'] : 0;
    $id_submissao = isset($_POST['id_submissao']) ? (int)$_POST['id_submissao'] : 0;
    $codigo_selo = trim($_POST['codigo_selo']);

    if ($id_submissao <= 0 || empty($codigo_selo)) {
        echo "<script>alert('ID de Submissão e Código do Selo são obrigatórios.'); window.history.back();</script>";
        exit();
    }

    // [Coleta e limpeza de dados]
    $uuid             = trim($_POST['uuid']);
    $r1_nome          = trim($_POST['r1_nome']);
    $r1_rg            = !empty($_POST['r1_rg']) ? trim($_POST['r1_rg']) : null;
    $r1_cpf           = !empty($_POST['r1_cpf']) ? str_replace(['.', '-'], '', trim($_POST['r1_cpf'])) : null;
    $r1_naturalidade  = !empty($_POST['r1_naturalidade']) ? trim($_POST['r1_naturalidade']) : null;
    $r1_data_nascimento = !empty($_POST['r1_data_nascimento']) ? $_POST['r1_data_nascimento'] : null;
    $r1_estado_civil  = !empty($_POST['r1_estado_civil']) ? trim($_POST['r1_estado_civil']) : null;
    $r1_profissao     = !empty($_POST['r1_profissao']) ? trim($_POST['r1_profissao']) : null;
    $r1_escolaridade  = !empty($_POST['r1_escolaridade']) ? trim($_POST['r1_escolaridade']) : null;
    $r1_pcd           = !empty($_POST['r1_pcd']) ? trim($_POST['r1_pcd']) : null;
    $r1_especifiacao_pcd = !empty($_POST['r1_especifiacao_pcd']) ? trim($_POST['r1_especifiacao_pcd']) : null;
    $r1_telefone      = !empty($_POST['r1_telefone']) ? trim($_POST['r1_telefone']) : null;
    $cadunico_nis     = !empty($_POST['cadunico_nis']) ? trim($_POST['cadunico_nis']) : null;
    $numero_nis       = !empty($_POST['numero_nis']) ? trim($_POST['numero_nis']) : null;
    $recebe_beneficio_social = !empty($_POST['recebe_beneficio_social']) ? trim($_POST['recebe_beneficio_social']) : null;
    $beneficios_detalhe = !empty($_POST['beneficios_detalhe']) ? trim($_POST['beneficios_detalhe']) : null;
    $reacao_com_imovel = !empty($_POST['reacao_com_imovel']) ? trim($_POST['reacao_com_imovel']) : null;
    $forma_aquisicao  = !empty($_POST['forma_aquisicao']) ? trim($_POST['forma_aquisicao']) : null;
    $tempo_ocupacao   = !empty($_POST['tempo_ocupacao']) ? trim($_POST['tempo_ocupacao']) : null;
    $paga_iptu        = !empty($_POST['paga_iptu']) ? trim($_POST['paga_iptu']) : null;
    $nome_cadastrador = !empty($_POST['nome_cadastrador']) ? trim($_POST['nome_cadastrador']) : null;
    $data_registro    = !empty($_POST['data_registro']) ? $_POST['data_registro'] : null;

    $numero_residentes      = is_numeric($_POST['numero_residentes']) ? (int)$_POST['numero_residentes'] : 1;
    $renda_mensal_titular_1 = is_numeric(str_replace(',', '.', $_POST['renda_mensal_titular_1'])) ? (float)str_replace(',', '.', $_POST['renda_mensal_titular_1']) : 0.00;
    $renda_mensal_titular_2 = is_numeric(str_replace(',', '.', $_POST['renda_mensal_titular_2'])) ? (float)str_replace(',', '.', $_POST['renda_mensal_titular_2']) : 0.00;
    $renda_outras_fontes    = is_numeric(str_replace(',', '.', $_POST['renda_outras_fontes'])) ? (float)str_replace(',', '.', $_POST['renda_outras_fontes']) : 0.00;

    $foto_selo       = !empty($_POST['foto_selo']) ? trim($_POST['foto_selo']) : null;
    $r1_foto_rg      = !empty($_POST['r1_foto_rg']) ? trim($_POST['r1_foto_rg']) : null;
    $r1_foto_cpf     = !empty($_POST['r1_foto_cpf']) ? trim($_POST['r1_foto_cpf']) : null;
    $foto_comprovante_ocupacao_2022 = !empty($_POST['foto_comprovante_ocupacao_2022']) ? trim($_POST['foto_comprovante_ocupacao_2022']) : null;
    $foto_comprovante_ocupacao_2024 = !empty($_POST['foto_comprovante_ocupacao_2024']) ? trim($_POST['foto_comprovante_ocupacao_2024']) : null;
    $foto_comprovante_ocupacao_2026 = !empty($_POST['foto_comprovante_ocupacao_2026']) ? trim($_POST['foto_comprovante_ocupacao_2026']) : null;
    
    $assinou_unica_propriedade         = !empty($_POST['assinou_unica_propriedade']) ? trim($_POST['assinou_unica_propriedade']) : null;
    $assinou_ocupacao_mansa_pacifica  = !empty($_POST['assinou_ocupacao_mansa_pacifica']) ? trim($_POST['assinou_ocupacao_mansa_pacifica']) : null;
    $assinou_veracidade                = !empty($_POST['assinou_veracidade']) ? trim($_POST['assinou_veracidade']) : null;
    $assinou_lgpd                      = !empty($_POST['assinou_lgpd']) ? trim($_POST['assinou_lgpd']) : null;

    $campos = [
        "uuid = '{$uuid}'",
        "codigo_selo = '{$codigo_selo}'",
        "r1_nome = '{$r1_nome}'",
        "r1_rg = " . ($r1_rg ? "'{$r1_rg}'" : "NULL"),
        "r1_cpf = " . ($r1_cpf ? "'{$r1_cpf}'" : "NULL"),
        "r1_naturalidade = " . ($r1_naturalidade ? "'{$r1_naturalidade}'" : "NULL"),
        "r1_data_nascimento = " . ($r1_data_nascimento ? "'{$r1_data_nascimento}'" : "NULL"),
        "r1_estado_civil = " . ($r1_estado_civil ? "'{$r1_estado_civil}'" : "NULL"),
        "r1_profissao = " . ($r1_profissao ? "'{$r1_profissao}'" : "NULL"),
        "r1_escolaridade = " . ($r1_escolaridade ? "'{$r1_escolaridade}'" : "NULL"),
        "r1_pcd = " . ($r1_pcd ? "'{$r1_pcd}'" : "NULL"),
        "r1_especifiacao_pcd = " . ($r1_especifiacao_pcd ? "'{$r1_especifiacao_pcd}'" : "NULL"),
        "r1_telefone = " . ($r1_telefone ? "'{$r1_telefone}'" : "NULL"),
        "numero_residentes = {$numero_residentes}",
        "renda_mensal_titular_1 = {$renda_mensal_titular_1}",
        "renda_mensal_titular_2 = {$renda_mensal_titular_2}",
        "renda_outras_fontes = {$renda_outras_fontes}",
        "cadunico_nis = " . ($cadunico_nis ? "'{$cadunico_nis}'" : "NULL"),
        "numero_nis = " . ($numero_nis ? "'{$numero_nis}'" : "NULL"),
        "recebe_beneficio_social = " . ($recebe_beneficio_social ? "'{$recebe_beneficio_social}'" : "NULL"),
        "beneficios_detalhe = " . ($beneficios_detalhe ? "'{$beneficios_detalhe}'" : "NULL"),
        "reacao_com_imovel = " . ($reacao_com_imovel ? "'{$reacao_com_imovel}'" : "NULL"),
        "forma_aquisicao = " . ($forma_aquisicao ? "'{$forma_aquisicao}'" : "NULL"),
        "tempo_ocupacao = " . ($tempo_ocupacao ? "'{$tempo_ocupacao}'" : "NULL"),
        "paga_iptu = " . ($paga_iptu ? "'{$paga_iptu}'" : "NULL"),
        "foto_selo = " . ($foto_selo ? "'{$foto_selo}'" : "NULL"),
        "r1_foto_rg = " . ($r1_foto_rg ? "'{$r1_foto_rg}'" : "NULL"),
        "r1_foto_cpf = " . ($r1_foto_cpf ? "'{$r1_foto_cpf}'" : "NULL"),
        "foto_comprovante_ocupacao_2022 = " . ($foto_comprovante_ocupacao_2022 ? "'{$foto_comprovante_ocupacao_2022}'" : "NULL"),
        "foto_comprovante_ocupacao_2024 = " . ($foto_comprovante_ocupacao_2024 ? "'{$foto_comprovante_ocupacao_2024}'" : "NULL"),
        "foto_comprovante_ocupacao_2026 = " . ($foto_comprovante_ocupacao_2026 ? "'{$foto_comprovante_ocupacao_2026}'" : "NULL"),
        "assinou_unica_propriedade = " . ($assinou_unica_propriedade ? "'{$assinou_unica_propriedade}'" : "NULL"),
        "assinou_ocupacao_mansa_pacifica = " . ($assinou_ocupacao_mansa_pacifica ? "'{$assinou_ocupacao_mansa_pacifica}'" : "NULL"),
        "assinou_veracidade = " . ($assinou_veracidade ? "'{$assinou_veracidade}'" : "NULL"),
        "assinou_lgpd = " . ($assinou_lgpd ? "'{$assinou_lgpd}'" : "NULL"),
        "nome_cadastrador = " . ($nome_cadastrador ? "'{$nome_cadastrador}'" : "NULL"),
        "data_registro = " . ($data_registro ? "'{$data_registro}'" : "NULL")
    ];

    //print_r($campos); // Debug: Exibe os campos que serão persistidos
    // 🔥 ALTERADO: Baseia a decisão do método de persistência no sinalizador 'is_edit' do form
    if ($isEdit === 1) {

        $sql = "UPDATE cadastro_sociojuridico SET " . implode(', ', $campos) . " WHERE id_submissao = " . $id_submissao;
        //echo $sql;
        //exit();
        $resultado = $manterRelatorio->db->Execute($sql);
        $mensagem = "Cadastro Sóciojurídico atualizado com sucesso!";
    } 
    /*
    else {
        $colunas = ['id_submissao'];
        $valores = [$id_submissao];
        
        foreach ($campos as $campo) {
            $partes = explode(' = ', $campo);
            $colunas[] = $partes[0];
            $valores[] = $partes[1];
        }
        
        $sql = "INSERT INTO cadastro_sociojuridico (" . implode(', ', $colunas) . ") VALUES (" . implode(', ', $valores) . ")";
        $resultado = $manterRelatorio->db->Execute($sql);
        $mensagem = "Nova ficha sóciojurídica vinculada com sucesso ao Selo!";
    }
    */
    
    if ($resultado) {
        echo "<script>alert('{$mensagem}'); window.location.href = 'sociojuridicos.php';</script>";
        exit();
    } else {
        echo "<script>alert('Erro ao persistir dados na tabela Sóciojurídico.'); window.history.back();</script>";
        exit();
    }
    
} else {
    header('Location: sociojuridicos.php');
    exit();
}