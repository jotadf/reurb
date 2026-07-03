<?php
/**
 * Action: Receber dados do formulário e salvar no banco de dados (Selagem de Lotes)
 * Ano: 2026
 */

require_once('verifica_login.php');
include_once('./actions/ManterRelatorio.php'); // Certifique-se de que o caminho aponta para a sua classe de persistência

// Inicializa a classe responsável pelas transações com a tabela selagem_lotes
$manterRelatorio = new ManterRelatorio();

// Verifica se a requisição de fato veio pelo método POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Instancia um objeto genérico para transportar os dados recebidos do formulário
    $lote = new stdClass();
    
    // Captura os dados limpando espaços em branco e aplicando valores padrões
    $lote->id_submissao              = isset($_POST['id_submissao']) ? trim($_POST['id_submissao']) : '';
    $lote->uuid                      = isset($_POST['uuid']) ? trim($_POST['uuid']) : null;
    $lote->rua_zona_setor            = isset($_POST['rua_zona_setor']) ? trim($_POST['rua_zona_setor']) : null;
    $lote->numero_lote               = isset($_POST['numero_lote']) ? trim($_POST['numero_lote']) : null;
    $lote->endereco_oficial_completo = isset($_POST['endereco_oficial_completo']) ? trim($_POST['endereco_oficial_completo']) : null;
    $lote->tipo_ocupacao_lote        = isset($_POST['tipo_ocupacao_lote']) ? trim($_POST['tipo_ocupacao_lote']) : null;
    $lote->qtd_domicilios_total      = (isset($_POST['qtd_domicilios_total']) && is_numeric($_POST['qtd_domicilios_total'])) ? (int)$_POST['qtd_domicilios_total'] : 1;
    $lote->nome_selador              = isset($_POST['nome_selador']) ? trim($_POST['nome_selador']) : null;
    $lote->data_formulario           = (isset($_POST['data_formulario']) && !empty($_POST['data_formulario'])) ? $_POST['data_formulario'] : null;
    $lote->versao                    = isset($_POST['versao']) ? substr(trim($_POST['versao']), 0, 50) : null; // Limite de 50 caracteres do schema

    // Validação de Segurança Básica: UUID e ID são fundamentais
    if (empty($lote->uuid)) {
        echo "<script>alert('O campo UUID é obrigatório!'); window.history.back();</script>";
        exit();
    }

    // =========================================================================
    // REGRA DE PERSISTÊNCIA: UPDATE OU INSERT
    // =========================================================================
    
    // Se o id_submissao for numérico e maior que zero, trata-se de uma ATUALIZAÇÃO (Update)
    if (is_numeric($lote->id_submissao) && (int)$lote->id_submissao > 0) {
        
        $id_submissao = (int)$lote->id_submissao;
        
        // Monta as partes do comando UPDATE escapando as strings para evitar quebra de SQL
        $campos_update = [
            "uuid = '" . $lote->uuid . "'",
            "rua_zona_setor = " . ($lote->rua_zona_setor ? "'" . $lote->rua_zona_setor . "'" : "NULL"),
            "numero_lote = " . ($lote->numero_lote ? "'" . $lote->numero_lote . "'" : "NULL"),
            "endereco_oficial_completo = " . ($lote->endereco_oficial_completo ? "'" . $lote->endereco_oficial_completo . "'" : "NULL"),
            "tipo_ocupacao_lote = " . ($lote->tipo_ocupacao_lote ? "'" . $lote->tipo_ocupacao_lote . "'" : "NULL"),
            "qtd_domicilios_total = " . $lote->qtd_domicilios_total,
            "nome_selador = " . ($lote->nome_selador ? "'" . $lote->nome_selador . "'" : "NULL"),
            "data_formulario = " . ($lote->data_formulario ? "'" . $lote->data_formulario . "'" : "NULL"),
            "versao = " . ($lote->versao ? "'" . $lote->versao . "'" : "NULL")
        ];

        $sql = "UPDATE selagem_lotes SET " . implode(', ', $campos_update) . " WHERE id_submissao = " . $id_submissao;
        
        // Executa a query utilizando o objeto herdado da sua classe Model ($this->db)
        $resultado = $manterRelatorio->db->Execute($sql);
        $mensagem_sucesso = "Registro de Selagem atualizado com sucesso!";

    } else {
        // Caso contrário, trata-se de um NOVO REGISTRO (Insert)
        // Como o ID de submissão do Kobo não veio gerado, criamos um ID único temporário usando o timestamp atual
        $lote->id_submissao = time(); 
        $lote->data_hora_submissao = date('Y-m-d H:i:s'); // Seta o momento do cadastro manual

        $colunas = [
            'id_submissao', 'uuid', 'rua_zona_setor', 'numero_lote', 'endereco_oficial_completo',
            'tipo_ocupacao_lote', 'qtd_domicilios_total', 'nome_selador', 'data_formulario', 'data_hora_submissao', 'versao'
        ];

        $valores = [
            $lote->id_submissao,
            "'" . $lote->uuid . "'",
            $lote->rua_zona_setor ? "'" . $lote->rua_zona_setor . "'" : "NULL",
            $lote->numero_lote ? "'" . $lote->numero_lote . "'" : "NULL",
            $lote->endereco_oficial_completo ? "'" . $lote->endereco_oficial_completo . "'" : "NULL",
            $lote->tipo_ocupacao_lote ? "'" . $lote->tipo_ocupacao_lote . "'" : "NULL",
            $lote->qtd_domicilios_total,
            $lote->nome_selador ? "'" . $lote->nome_selador . "'" : "NULL",
            $lote->data_formulario ? "'" . $lote->data_formulario . "'" : "NULL",
            "'" . $lote->data_hora_submissao . "'",
            $lote->versao ? "'" . $lote->versao . "'" : "NULL"
        ];

        $sql = "INSERT INTO selagem_lotes (" . implode(', ', $colunas) . ") VALUES (" . implode(', ', $valores) . ")";
        
        $resultado = $manterRelatorio->db->Execute($sql);
        $mensagem_sucesso = "Novo lote inserido com sucesso na base territorial!";
    }

    // Retorno visual e redirecionamento para o usuário
    if ($resultado) {
        echo "<script>
                alert('{$mensagem_sucesso}'); 
                window.location.href = 'selagens.php'; 
              </script>";
        exit();
    } else {
        echo "<script>
                alert('Erro crítico ao salvar as informações no banco de dados.'); 
                window.history.back();
              </script>";
        exit();
    }

} else {
    // Se tentarem acessar este arquivo diretamente via URL sem submeter o formulário
    header('Location: selagens.php');
    exit();
}