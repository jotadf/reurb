<?php
/**
 * Action: Receber dados do formulário e salvar na tabela Domicilios
 * Ano: 2026
 */

$mod = 10;
require_once('../verifica_login.php');
include_once('ManterRelatorio.php');

$manterRelatorio = new ManterRelatorio();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $isEdit      = isset($_POST['is_edit']) ? (int)$_POST['is_edit'] : 0;
    $numero_selo = isset($_POST['numero_selo']) ? trim($_POST['numero_selo']) : '';

    if (empty($numero_selo)) {
        echo "<script>alert('O número do selo é obrigatório!'); window.history.back();</script>";
        exit();
    }

    // Coleta e Higienização dos dados do Formulário
    $id_submissao_pai          = (int)$_POST['id_submissao_pai'];
    $index_kobo                = (isset($_POST['index_kobo']) && is_numeric($_POST['index_kobo'])) ? (int)$_POST['index_kobo'] : null;
    $nome_entrevistado         = !empty($_POST['nome_entrevistado']) ? trim($_POST['nome_entrevistado']) : null;
    $nome_principal_morador    = !empty($_POST['nome_principal_morador']) ? trim($_POST['nome_principal_morador']) : null;
    $telefone                  = !empty($_POST['telefone']) ? trim($_POST['telefone']) : null;
    $cpf                       = !empty($_POST['cpf']) ? str_replace(['.', '-'], '', trim($_POST['cpf'])) : null; // Limpa máscaras
    $casado_uniao_estavel      = !empty($_POST['casado_uniao_estavel']) ? trim($_POST['casado_uniao_estavel']) : null;
    $uso_predominante          = !empty($_POST['uso_predominante']) ? trim($_POST['uso_predominante']) : null;
    $tipo_ocupacao_imovel      = !empty($_POST['tipo_ocupacao_imovel']) ? trim($_POST['tipo_ocupacao_imovel']) : null;
    $numero_pavimentos         = is_numeric($_POST['numero_pavimentos']) ? (int)$_POST['numero_pavimentos'] : 1;
    $localizacao_domicilio     = !empty($_POST['localizacao_domicilio']) ? trim($_POST['localizacao_domicilio']) : null;
    $acesso_independente       = !empty($_POST['acesso_independente']) ? trim($_POST['acesso_independente']) : null;
    
    // Tratamento de tipos de dados numéricos precisos do Schema (Float/Decimal)
    $area_lote_m2              = is_numeric(str_replace(',', '.', $_POST['area_lote_m2'])) ? (float)str_replace(',', '.', $_POST['area_lote_m2']) : 0.00;
    $latitude                  = (isset($_POST['latitude']) && trim($_POST['latitude']) !== '') ? (float)$_POST['latitude'] : null;
    $longitude                 = (isset($_POST['longitude']) && trim($_POST['longitude']) !== '') ? (float)$_POST['longitude'] : null;
    $altitude                  = (isset($_POST['altitude']) && trim($_POST['altitude']) !== '') ? (float)$_POST['altitude'] : null;
    $precisao                  = (isset($_POST['precisao']) && trim($_POST['precisao']) !== '') ? (float)$_POST['precisao'] : null;
    
    $comprovante_endereco      = !empty($_POST['comprovante_endereco']) ? trim($_POST['comprovante_endereco']) : null;
    $foto_comprovante_endereco = !empty($_POST['foto_comprovante_endereco']) ? trim($_POST['foto_comprovante_endereco']) : null;
    $foto_fachada              = !empty($_POST['foto_fachada']) ? trim($_POST['foto_fachada']) : null;
    $foto_selo                 = !empty($_POST['foto_selo']) ? trim($_POST['foto_selo']) : null;
    $foto_ocupacao             = !empty($_POST['foto_ocupacao']) ? trim($_POST['foto_ocupacao']) : null;

    // Estrutura do Dicionário de Carga Mapeado
    $campos = [
        "id_submissao_pai = {$id_submissao_pai}",
        "index_kobo = " . (is_null($index_kobo) ? "NULL" : $index_kobo),
        "nome_entrevistado = " . ($nome_entrevistado ? "'{$nome_entrevistado}'" : "NULL"),
        "nome_principal_morador = " . ($nome_principal_morador ? "'{$nome_principal_morador}'" : "NULL"),
        "telefone = " . ($telefone ? "'{$telefone}'" : "NULL"),
        "cpf = " . ($cpf ? "'{$cpf}'" : "NULL"),
        "casado_uniao_estavel = " . ($casado_uniao_estavel ? "'{$casado_uniao_estavel}'" : "NULL"),
        "uso_predominante = " . ($uso_predominante ? "'{$uso_predominante}'" : "NULL"),
        "tipo_ocupacao_imovel = " . ($tipo_ocupacao_imovel ? "'{$tipo_ocupacao_imovel}'" : "NULL"),
        "numero_pavimentos = {$numero_pavimentos}",
        "localizacao_domicilio = " . ($localizacao_domicilio ? "'{$localizacao_domicilio}'" : "NULL"),
        "acesso_independente = " . ($acesso_independente ? "'{$acesso_independente}'" : "NULL"),
        "area_lote_m2 = {$area_lote_m2}",
        "comprovante_endereco = " . ($comprovante_endereco ? "'{$comprovante_endereco}'" : "NULL"),
        "foto_comprovante_endereco = " . ($foto_comprovante_endereco ? "'{$foto_comprovante_endereco}'" : "NULL"),
        "foto_fachada = " . ($foto_fachada ? "'{$foto_fachada}'" : "NULL"),
        "foto_selo = " . ($foto_selo ? "'{$foto_selo}'" : "NULL"),
        "foto_ocupacao = " . ($foto_ocupacao ? "'{$foto_ocupacao}'" : "NULL"),
        "latitude = " . (is_null($latitude) ? "NULL" : $latitude),
        "longitude = " . (is_null($longitude) ? "NULL" : $longitude),
        "altitude = " . (is_null($altitude) ? "NULL" : $altitude),
        "precisao = " . (is_null($precisao) ? "NULL" : $precisao)
    ];

    if ($isEdit === 1) {
        // Fluxo de Atualização (UPDATE)
        $sql = "UPDATE domicilios SET " . implode(', ', $campos) . " WHERE numero_selo = '{$numero_selo}'";
        $resultado = $manterRelatorio->db->Execute($sql);
        $mensagem = "Domicílio atualizado com sucesso!";
    } else {
        // Fluxo de Inserção (INSERT)
        /*
        $colunas = ['numero_selo'];
        $valores = ["'{$numero_selo}'"];
        
        foreach ($campos as $campo) {
            $partes = explode(' = ', $campo);
            $colunas[] = $partes[0];
            $valores[] = $partes[1];
        }
        
        $sql = "INSERT INTO domicilios (" . implode(', ', $colunas) . ") VALUES (" . implode(', ', $valores) . ")";
        $resultado = $manterRelatorio->db->Execute($sql);
        */
        $mensagem = "Domicílio não cadastrado!";
    }

    if ($resultado) {
        echo "<script>alert('{$mensagem}'); window.location.href = 'selagens.php';</script>";
        exit();
    } else {
        echo "<script>alert('Erro ao executar persistência na tabela Domicílios.'); window.history.back();</script>";
        exit();
    }
} else {
    header('Location: selagens.php');
    exit();
}