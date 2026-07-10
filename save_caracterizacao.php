<?php
/**
 * Action: Receber dados do formulário e salvar no banco de dados (Caracterização e Vulnerabilidade)
 * Ano: 2026
 */
require_once('../verifica_login.php');
include_once('ManterRelatorio.php'); // Certifique-se de que o caminho aponta para a sua classe de persistência

// Inicializa a classe responsável pelas transações com o banco
$manterRelatorio = new ManterRelatorio();

// Verifica se a requisição de fato veio pelo método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Captura o estado do formulário e chaves primárias/estrangeiras
    $isEdit       = isset($_POST['is_edit']) ? (int)$_POST['is_edit'] : 0;
    $id_submissao = isset($_POST['id_submissao']) ? (int)$_POST['id_submissao'] : 0;
    $codigo_selo  = isset($_POST['codigo_selo']) ? trim($_POST['codigo_selo']) : '';

    if ($id_submissao <= 0 || empty($codigo_selo)) {
        echo "<script>alert('ID de Submissão e Código do Selo são obrigatórios!'); window.history.back();</script>";
        exit();
    }

    // Instancia um objeto genérico e limpa/higieniza todas as variáveis do formulário
    $caract = new stdClass();
    $caract->id_submissao = $id_submissao;
    $caract->uuid         = isset($_POST['uuid']) ? trim($_POST['uuid']) : '';
    $caract->codigo_selo  = $codigo_selo;
    
    // Dados Numéricos e de Habitabilidade
    $caract->quantidade_comodos              = is_numeric($_POST['quantidade_comodos']) ? (int)$_POST['quantidade_comodos'] : 0;
    $caract->comodos_improvisados_dormitorio = !empty($_POST['comodos_improvisados_dormitorio']) ? trim($_POST['comodos_improvisados_dormitorio']) : null;
    $caract->mais_de_3_por_dormitorio        = !empty($_POST['mais_de_3_por_dormitorio']) ? trim($_POST['mais_de_3_por_dormitorio']) : null;
    $caract->faltam_camas                    = !empty($_POST['faltam_camas']) ? trim($_POST['faltam_camas']) : null;
    $caract->possui_banheiro                 = !empty($_POST['possui_banheiro']) ? trim($_POST['possui_banheiro']) : null;
    $caract->numero_banheiros                = is_numeric($_POST['numero_banheiros']) ? (int)$_POST['numero_banheiros'] : 0;
    $caract->revestimento_ceramico_banheiro  = !empty($_POST['revestimento_ceramico_banheiro']) ? trim($_POST['revestimento_ceramico_banheiro']) : null;
    $caract->louca_sanitaria_banheiro        = !empty($_POST['louca_sanitaria_banheiro']) ? trim($_POST['louca_sanitaria_banheiro']) : null;
    
    // Patologias e Estrutura
    $caract->problemas_estruturais_observados = !empty($_POST['problemas_estruturais_observados']) ? trim($_POST['problemas_estruturais_observados']) : null;
    $caract->problemas_infiltracao            = !empty($_POST['problemas_infiltracao']) ? trim($_POST['problemas_infiltracao']) : null;
    $caract->existe_comodo_com_mofo          = !empty($_POST['existe_comodo_com_mofo']) ? trim($_POST['existe_comodo_com_mofo']) : null;
    
    // Materiais, Infraestrutura e Serviços Públicos
    $caract->paredes_materiais     = !empty($_POST['paredes_materiais']) ? trim($_POST['paredes_materiais']) : null;
    $caract->cobertura_materiais   = !empty($_POST['cobertura_materiais']) ? trim($_POST['cobertura_materiais']) : null;
    $caract->piso_materiais        = !empty($_POST['piso_materiais']) ? trim($_POST['piso_materiais']) : null;
    $caract->energia_acesso        = !empty($_POST['energia_acesso']) ? trim($_POST['energia_acesso']) : null;
    $caract->agua_acesso           = !empty($_POST['agua_acesso']) ? trim($_POST['agua_acesso']) : null;
    $caract->esgotamento_sanitario = !empty($_POST['esgotamento_sanitario']) ? trim($_POST['esgotamento_sanitario']) : null;
    $caract->coleta_lixo           = !empty($_POST['coleta_lixo']) ? trim($_POST['coleta_lixo']) : null;
    $caract->pavimentacao_rua      = !empty($_POST['pavimentacao_rua']) ? trim($_POST['pavimentacao_rua']) : null;
    $caract->responsavel_coleta    = !empty($_POST['responsavel_coleta']) ? trim($_POST['responsavel_coleta']) : null;
    $caract->data_registro         = date('Y-m-d');

    // =========================================================================
    // REGRA DE PERSISTÊNCIA: PADRÃO LINEAR (UPGRADE IGUAL AO SALVAR_SELAGEM)
    // =========================================================================
    
    // Array comum de mapeamento chave = valor para facilidade de manutenção
    $mapeamento_campos = [
        "uuid = '" . $caract->uuid . "'",
        "codigo_selo = '" . $caract->codigo_selo . "'",
        "quantidade_comodos = " . $caract->quantidade_comodos,
        "comodos_improvisados_dormitorio = " . ($caract->comodos_improvisados_dormitorio ? "'" . $caract->comodos_improvisados_dormitorio . "'" : "NULL"),
        "mais_de_3_por_dormitorio = " . ($caract->mais_de_3_por_dormitorio ? "'" . $caract->mais_de_3_por_dormitorio . "'" : "NULL"),
        "faltam_camas = " . ($caract->faltam_camas ? "'" . $caract->faltam_camas . "'" : "NULL"),
        "possui_banheiro = " . ($caract->possui_banheiro ? "'" . $caract->possui_banheiro . "'" : "NULL"),
        "numero_banheiros = " . $caract->numero_banheiros,
        "revestimento_ceramico_banheiro = " . ($caract->revestimento_ceramico_banheiro ? "'" . $caract->revestimento_ceramico_banheiro . "'" : "NULL"),
        "louca_sanitaria_banheiro = " . ($caract->louca_sanitaria_banheiro ? "'" . $caract->louca_sanitaria_banheiro . "'" : "NULL"),
        "problemas_estruturais_observados = " . ($caract->problemas_estruturais_observados ? "'" . $caract->problemas_estruturais_observados . "'" : "NULL"),
        "problemas_infiltracao = " . ($caract->problemas_infiltracao ? "'" . $caract->problemas_infiltracao . "'" : "NULL"),
        "existe_comodo_com_mofo = " . ($caract->existe_comodo_com_mofo ? "'" . $caract->existe_comodo_com_mofo . "'" : "NULL"),
        "paredes_materiais = " . ($caract->paredes_materiais ? "'" . $caract->paredes_materiais . "'" : "NULL"),
        "cobertura_materiais = " . ($caract->cobertura_materiais ? "'" . $caract->cobertura_materiais . "'" : "NULL"),
        "piso_materiais = " . ($caract->piso_materiais ? "'" . $caract->piso_materiais . "'" : "NULL"),
        "energia_acesso = " . ($caract->energia_acesso ? "'" . $caract->energia_acesso . "'" : "NULL"),
        "agua_acesso = " . ($caract->agua_acesso ? "'" . $caract->agua_acesso . "'" : "NULL"),
        "esgotamento_sanitario = " . ($caract->esgotamento_sanitario ? "'" . $caract->esgotamento_sanitario . "'" : "NULL"),
        "coleta_lixo = " . ($caract->coleta_lixo ? "'" . $caract->coleta_lixo . "'" : "NULL"),
        "pavimentacao_rua = " . ($caract->pavimentacao_rua ? "'" . $caract->pavimentacao_rua . "'" : "NULL"),
        "responsavel_coleta = " . ($caract->responsavel_coleta ? "'" . $caract->responsavel_coleta . "'" : "NULL"),
        "data_registro = '" . $caract->data_registro . "'"
    ];

    if ($isEdit === 1) {
        // Fluxo de Edição: UPDATE tradicional por string implodida
        $sql = "UPDATE caracterizacao_vulnerabilidade SET " . implode(', ', $mapeamento_campos) . " WHERE id_submissao = " . $caract->id_submissao;
        $resultado = $manterRelatorio->db->Execute($sql);
        $mensagem_sucesso = "Ficha de Caracterização Física e Vulnerabilidade atualizada!";
    } else {
        // Fluxo de Inserção: Novo INSERT montado dinamicamente a partir do dicionário
        $colunas = ['id_submissao'];
        $valores = [$caract->id_submissao];
        
        foreach ($mapeamento_campos as $linha_campo) {
            $partes = explode(' = ', $linha_campo);
            $colunas[] = $partes[0];
            $valores[] = $partes[1];
        }
        
        $sql = "INSERT INTO caracterizacao_vulnerabilidade (" . implode(', ', $colunas) . ") VALUES (" . implode(', ', $valores) . ")";
        $resultado = $manterRelatorio->db->Execute($sql);
        $mensagem_sucesso = "Nova Ficha de Caracterização registrada com sucesso!";
    }

    // Retorno de interface e redirecionamento de tela
    if ($resultado) {
        echo "<script>
                alert('{$mensagem_sucesso}'); 
                window.location.href = 'caracterizacoes.php'; 
              </script>";
        exit();
    } else {
        echo "<script>
                alert('Erro crítico ao salvar os dados de Caracterização no banco.'); 
                window.history.back();
              </script>";
        exit();
    }

} else {
    header('Location: caracterizacoes.php');
    exit();
}