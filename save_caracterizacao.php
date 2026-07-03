<?php
/**
 * Action: Receber dados e salvar na tabela caracterizacao_vulnerabilidade
 * Ano: 2026
 */

require_once('../verifica_login.php');
include_once('ManterRelatorio.php');

$manterRelatorio = new ManterRelatorio();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $isEdit = isset($_POST['is_edit']) ? (int)$_POST['is_edit'] : 0;
    $id_submissao = isset($_POST['id_submissao']) ? (int)$_POST['id_submissao'] : 0;
    $codigo_selo = isset($_POST['codigo_selo']) ? trim($_POST['codigo_selo']) : '';

    if ($id_submissao <= 0 || empty($codigo_selo)) {
        echo "<script>alert('ID de Submissão e Código do Selo são obrigatórios.'); window.history.back();</script>";
        exit();
    }

    // Instancia objeto DTO temporário para mapeamento na classe
    $dados = new stdClass();
    $dados->id_submissao = $id_submissao;
    $dados->uuid = trim($_POST['uuid']);
    $dados->codigo_selo = $codigo_selo;
    
    // Coleta e Higienização de dados estruturados
    $dados->quantidade_comodos              = is_numeric($_POST['quantidade_comodos']) ? (int)$_POST['quantidade_comodos'] : 0;
    $dados->comodos_improvisados_dormitorio = !empty($_POST['comodos_improvisados_dormitorio']) ? $_POST['comodos_improvisados_dormitorio'] : null;
    $dados->mais_de_3_por_dormitorio        = !empty($_POST['mais_de_3_por_dormitorio']) ? $_POST['mais_de_3_por_dormitorio'] : null;
    $dados->faltam_camas                    = !empty($_POST['faltam_camas']) ? $_POST['faltam_camas'] : null;
    $dados->possui_banheiro                 = !empty($_POST['possui_banheiro']) ? $_POST['possui_banheiro'] : null;
    $dados->numero_banheiros                = is_numeric($_POST['numero_banheiros']) ? (int)$_POST['numero_banheiros'] : 0;
    $dados->revestimento_ceramico_banheiro  = !empty($_POST['revestimento_ceramico_banheiro']) ? $_POST['revestimento_ceramico_banheiro'] : null;
    $dados->louca_sanitaria_banheiro        = !empty($_POST['louca_sanitaria_banheiro']) ? $_POST['louca_sanitaria_banheiro'] : null;
    
    $dados->problemas_estruturais_observados = !empty($_POST['problemas_estruturais_observados']) ? trim($_POST['problemas_estruturais_observados']) : null;
    $dados->problemas_infiltracao            = !empty($_POST['problemas_infiltracao']) ? trim($_POST['problemas_infiltracao']) : null;
    $dados->existe_comodo_com_mofo          = !empty($_POST['existe_comodo_com_mofo']) ? $_POST['existe_comodo_com_mofo'] : null;
    
    $dados->paredes_materiais     = !empty($_POST['paredes_materiais']) ? $_POST['paredes_materiais'] : null;
    $dados->cobertura_materiais   = !empty($_POST['cobertura_materiais']) ? $_POST['cobertura_materiais'] : null;
    $dados->piso_materiais        = !empty($_POST['piso_materiais']) ? $_POST['piso_materiais'] : null;
    $dados->energia_acesso        = !empty($_POST['energia_acesso']) ? $_POST['energia_acesso'] : null;
    $dados->agua_acesso           = !empty($_POST['agua_acesso']) ? $_POST['agua_acesso'] : null;
    $dados->esgotamento_sanitario = !empty($_POST['esgotamento_sanitario']) ? $_POST['esgotamento_sanitario'] : null;
    $dados->coleta_lixo           = !empty($_POST['coleta_lixo']) ? $_POST['coleta_lixo'] : null;
    $dados->pavimentacao_rua      = !empty($_POST['pavimentacao_rua']) ? $_POST['pavimentacao_rua'] : null;
    $dados->responsavel_coleta    = !empty($_POST['responsavel_coleta']) ? trim($_POST['responsavel_coleta']) : null;
    
    // Seta automaticamente a data de alteração física da ficha
    $dados->data_registro = date('Y-m-d');

    // Executa a persistência através do método robusto que já configuramos no início
    $resultado = $manterRelatorio->salvar($dados);

    if ($resultado) {
        echo "<script>alert('Ficha de Caracterização salva com sucesso!'); window.location.href = '../gerenciar_relatorio.php';</script>";
        exit();
    } else {
        echo "<script>alert('Erro ao persistir dados na tabela Caracterização.'); window.history.back();</script>";
        exit();
    }
} else {
    header('Location: ../gerenciar_relatorio.php');
    exit();
}