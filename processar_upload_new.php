<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Formulário de Domicílio</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.css">
</head>
<body id="page-top">
<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);
/**
 * Script de Importação Manual via Upload de Arquivo Único
 * Ano: 2026
 */

// 1. Configurações de Conexão
$host     = 'mysql';
$dbname   = 'reurb';
$user     = 'reurb';
$password = 'reurb#2020';
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    responderJSON(false, "Erro na conexão com o banco de dados: " . $e->getMessage());
}

// 2. Validação da Requisição HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responderJSON(false, "Método de requisição inválido.");
    exit;
}

$resultado = ""; // Variável para acumular mensagens de resultado

// 3. Processamento dos Arquivos Enviados (Validações de Segurança e Existência)
// Validações de segurança: Verifica o arquivo de selagem, garante que foi enviado e não ocorreu erro no upload
if (!isset($_FILES['arquivo_csv_selagem']) || $_FILES['arquivo_csv_selagem']['error'] !== UPLOAD_ERR_OK) {
    //responderJSON(false, "Falha no envio do arquivo. Verifique o tamanho limite do PHP.");
    //exit;
} else {
    $caminhoTemporario_selagem = $_FILES['arquivo_csv_selagem']['tmp_name'];
    //echo "<br/>".$caminhoTemporario_selagem."<br/>"; // Separador visual entre as importações
    $resultado .= importarSelagem($caminhoTemporario_selagem, $pdo);
}

// Validações de segurança: Verifica o arquivo de domicílios, garante que foi enviado e não ocorreu erro no upload
if (!isset($_FILES['arquivo_csv_domicilios']) || $_FILES['arquivo_csv_domicilios']['error'] !== UPLOAD_ERR_OK) {
    //responderJSON(false, "Falha no envio do arquivo. Verifique o tamanho limite do PHP.");
    //exit;
} else {
    $caminhoTemporario_domicilios = $_FILES['arquivo_csv_domicilios']['tmp_name'];
    //echo "<br/>".$caminhoTemporario_domicilios."<br/>"; // Separador visual entre as importações
    $resultado .= importarDomicilios($caminhoTemporario_domicilios, $pdo);
}

// Validações de segurança: Verifica o arquivo de sócio jurídico, garante que foi enviado e não ocorreu erro no upload
if (!isset($_FILES['arquivo_csv_socio_juridico']) || $_FILES['arquivo_csv_socio_juridico']['error'] !== UPLOAD_ERR_OK) {
    //responderJSON(false, "Falha no envio do arquivo. Verifique o tamanho limite do PHP.");
} else {
    $caminhoTemporario_socio_juridico = $_FILES['arquivo_csv_socio_juridico']['tmp_name'];
    //echo "<br/>".$caminhoTemporario_socio_juridico."<br/>"; // Separador visual entre as importações
    $resultado .= importarSociojuridico($caminhoTemporario_socio_juridico, $pdo);
}

// Validações de segurança: Verifica o arquivo de caracterização, garante que foi enviado e não ocorreu erro no upload
if (!isset($_FILES['arquivo_csv_caracterizacao']) || $_FILES['arquivo_csv_caracterizacao']['error'] !== UPLOAD_ERR_OK) {
    //responderJSON(false, "Falha no envio do arquivo. Verifique o tamanho limite do PHP.");
} else {
    $caminhoTemporario_caracterizacao = $_FILES['arquivo_csv_caracterizacao']['tmp_name'];
    //echo "<br/>".$caminhoTemporario_caracterizacao."<br/>"; // Separador visual entre as importações
    $resultado .= importarCaracterizacao($caminhoTemporario_caracterizacao, $pdo);
}

// =========================================================================
// FUNÇÕES DE PROCESSAMENTO INDIVIDUAL (Executam isoladamente por arquivo)
// =========================================================================

function importarSelagem($arquivo, $pdo) {
    if (($handle_selagem = fopen($arquivo, "r")) !== FALSE) {
        
        // 1. Limpa o BOM (Byte Order Mark) se existir no início do arquivo
        $bom = fread($handle_selagem, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle_selagem);
        }

        // 2. Pula a linha do cabeçalho
        fgetcsv($handle_selagem, 4000, ";");
        
        $sql = "INSERT INTO selagem_lotes_import 
                (id_submissao, uuid, rua_zona_setor, numero_lote, comprovante_endereco, foto_comprovante_endereco, endereco_oficial_completo, 
                 tipo_ocupacao_lote, qtd_domicilios_lote, qtd_domicilios_total, nome_selador, data_formulario, 
                 observacoes, data_hora_submissao, versao)
                VALUES 
                (:id_submissao, :uuid, :rua_zona_setor, :numero_lote, :comprovante_endereco, :foto_comprovante_endereco, :endereco_oficial_completo, 
                 :tipo_ocupacao_lote, :qtd_domicilios_lote, :qtd_domicilios_total, :nome_selador, :data_formulario, 
                 :observacoes, :data_hora_submissao, :versao)
                ON DUPLICATE KEY UPDATE uuid=VALUES(uuid);";
                
        $stmt = $pdo->prepare($sql);
        $linhas = 0;

        while (($data = fgetcsv($handle_selagem, 4000, ";")) !== FALSE) {
            
            // 3. Validação de Segurança: Ignora se o ID estiver vazio, nulo ou não for numérico
            if (!isset($data[11]) || trim($data[11]) === '' || !is_numeric(trim($data[11]))) {
                continue; 
            }

            $id_submissao              = trim($data[11]);  
            $uuid                      = $data[10]  ?? null; 
            $rua_zona_setor            = $data[0]   ?? null; 
            $numero_lote               = $data[1]   ?? null; 
            $comprovante_endereco      = $data[2] ?? null;  // Sim / Não
            $foto_comprovante_endereco = $data[3] ?? null;  // URL da foto do comprovante de endereço
            $endereco_oficial_completo = $data[4]   ?? null; 
            $tipo_ocupacao_lote        = $data[5]   ?? null; 
            
            $qtd_domicilios_lote       = (empty($data[6]) || !is_numeric($data[6])) ? 1 : (int)$data[6];
            $qtd_domicilios_total      = (empty($data[7]) || !is_numeric($data[7])) ? 1 : (int)$data[7];
            
            $nome_selador              = $data[8]   ?? null; 
            $observacoes               = $data[9]   ?? null; 

            // Tratamento seguro da Data do Formulário
            $data_raw = isset($data[10]) ? trim($data[10]) : '';
            if (!empty($data_raw) && $data_raw !== '0000-00-00') {
                $timestamp = strtotime(str_replace('/', '-', $data_raw));
                $data_formulario = ($timestamp !== false) ? date('Y-m-d', $timestamp) : null;
            } else {
                $data_formulario = null; 
            }
            
            // Tratamento seguro do Data/Hora de Submissão
            $raw_dh = isset($data[13]) ? trim($data[13]) : '';
            if (!empty($raw_dh)) {
                $clean_dh = str_replace('T', ' ', $raw_dh);
                $data_hora_submissao = substr($clean_dh, 0, 19);
            } else {
                $data_hora_submissao = null;
            }
            
            $versao = isset($data[18]) ? substr(trim($data[18]), 0, 100) : null;

            $stmt->execute([
                ':id_submissao'              => $id_submissao,
                ':uuid'                      => $uuid,
                ':rua_zona_setor'            => $rua_zona_setor,
                ':numero_lote'               => $numero_lote,
                ':comprovante_endereco'      => $comprovante_endereco,
                ':foto_comprovante_endereco' => $foto_comprovante_endereco,
                ':endereco_oficial_completo' => $endereco_oficial_completo,
                ':tipo_ocupacao_lote'        => $tipo_ocupacao_lote,
                ':qtd_domicilios_lote'       => $qtd_domicilios_lote,
                ':qtd_domicilios_total'      => $qtd_domicilios_total,
                ':nome_selador'              => $nome_selador,
                ':observacoes'               => $observacoes,
                ':data_formulario'           => $data_formulario,
                ':data_hora_submissao'       => $data_hora_submissao,
                ':versao'                    => $versao
            ]);

            $linhas++;
        }

        fclose($handle_selagem);
        return "Sucesso! Foram importados/atualizados <strong>{$linhas}</strong> registros de Selagem.<hr/> <br/>";
    } else {
        return "Não foi possível abrir o arquivo de Selagem enviado.<hr/> <br/>";
    }
}

function importarDomicilios($arquivo, $pdo) {
    if (($handle_domicilios = fopen($arquivo, "r")) !== FALSE) {
        
        // 1. Limpa o BOM (Byte Order Mark) se existir no início do arquivo
        $bom = fread($handle_domicilios, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle_domicilios);
        }

        // 2. Ignora as duas linhas de cabeçalho comuns nessa aba do KoboToolbox (com parâmetros explícitos)
        fgetcsv($handle_domicilios, 2000, ";", '"', "\\");
        fgetcsv($handle_domicilios, 2000, ";", '"', "\\");

        // Utilizando parâmetros nomeados (:numero_selo) para eliminar a contagem cega de "?"
        $sql = "INSERT INTO domicilios_import
                (numero_selo, id_submissao_pai, index_kobo, nome_entrevistado, nome_principal_morador, 
                 telefone, cpf, casado_uniao_estavel, uso_predominante, tipo_ocupacao_imovel,
                 numero_pavimentos, localizacao_domicilio, acesso_independente, area_lote_m2, 
                  foto_fachada, foto_selo, foto_ocupacao, latitude, longitude, altitude, precisao)
                VALUES 
                (:numero_selo, :id_submissao_pai, :index_kobo, :nome_entrevistado, :nome_principal_morador, 
                 :telefone, :cpf, :casado_uniao_estavel, :uso_predominante, :tipo_ocupacao_imovel,
                 :numero_pavimentos, :localizacao_domicilio, :acesso_independente, :area_lote_m2, 
                  :foto_fachada, :foto_selo, :foto_ocupacao, :latitude, :longitude, :altitude, :precisao)
                ON DUPLICATE KEY UPDATE numero_selo=numero_selo;";
                
        $stmt = $pdo->prepare($sql);
        $linhas = 0;

        while (($data = fgetcsv($handle_domicilios, 2000, ";", '"', "\\")) !== FALSE) {
             //for ($i = 0; $i < count($data); $i++) {
             //    echo "[$i] " . $data[$i] . "<br/>";
             //}
             
            // 3. Validação de segurança: se o número do selo ou index estiver vazio, pula a linha
            if (!isset($data[19]) || trim($data[19]) === '' || empty($data[19])) {
                continue; 
            }

            // =========================================================================
            // DOCUMENTAÇÃO E DE-PARA DOS CAMPOS (Índices validados conforme o LOG real)
            // =========================================================================
            $numero_selo         = trim($data[19]); // Chave Primária Lógica (ex: INV-Y-0001-0002)
            $id_submissao_pai    = trim($data[23]); // ID de ligação com a tabela pai (selagem_lotes)
            $index_kobo          = (int)$data[20];  // Índice interno do Kobo
            
            // Dados de Identificação do Morador
            $nome_entrevistado   = $data[0] ?? null;
            $nome_principal_morador = $data[1] ?? null;
            $telefone            = $data[2] ?? null;
            $cpf                 = isset($data[3]) ? str_replace(['.', '-'], '', $data[3]) : null; // CPF limpo
            $casado_uniao_estavel = $data[4] ?? null;
            
            // Características do Domicílio
            $uso_predominante    = $data[5] ?? null; // ex: Exclusivamente Residencial
            $tipo_ocupacao_imovel = $data[6] ?? null; // ex: Edificação consolidada ocupada
            $numero_pavimentos   = is_numeric($data[7]) ? (int)$data[7] : 1;
            $localizacao_domicilio = $data[8] ?? null; // ex: Térreo
            $acesso_independente = $data[9] ?? null;  // Sim / Não
            $area_lote_m2        = is_numeric($data[10]) ? (float)$data[10] : 0.00;
            //$comprovante_endereco = $data[11] ?? null; // Sim / Não

            //$declaracao_ciencia = $data[13] ?? null; // Sim / Não
            //$termo_lgpd         = $data[14] ?? null; // Sim / Não
            
            // URLs de Mídias e Comprovantes
            //$foto_comprovante_endereco = $data[12] ?? null; // URL da foto do comprovante de endereço
            $foto_fachada    = $data[17] ?? null; // URL da foto da fachada do imóvel
            $foto_selo       = $data[18] ?? null; // URL da foto comprovando selo fixado
            $foto_ocupacao   = $data[11] ?? null; // URL da foto da ocupação do imóvel

            // Informações de Geolocalização por GPS
            $latitude            = (trim($data[13]) !== '') ? (float)$data[13] : null;
            $longitude           = (trim($data[14]) !== '') ? (float)$data[14] : null;
            $altitude            = (trim($data[15]) !== '') ? (float)$data[15] : null;
            $precisao            = (trim($data[16]) !== '') ? (float)$data[16] : null;

            // Execução limpa, segura e estruturada
            $stmt->execute([
                ':numero_selo'               => $numero_selo,
                ':id_submissao_pai'          => $id_submissao_pai,
                ':index_kobo'                => $index_kobo,
                ':nome_entrevistado'         => $nome_entrevistado,
                ':nome_principal_morador'    => $nome_principal_morador,
                ':telefone'                  => $telefone,
                ':cpf'                       => $cpf,
                ':casado_uniao_estavel'      => $casado_uniao_estavel,
                ':uso_predominante'          => $uso_predominante,
                ':tipo_ocupacao_imovel'      => $tipo_ocupacao_imovel,
                ':numero_pavimentos'         => $numero_pavimentos,
                ':localizacao_domicilio'     => $localizacao_domicilio,
                ':acesso_independente'       => $acesso_independente,
                ':area_lote_m2'              => $area_lote_m2,
                ':foto_fachada'              => $foto_fachada,
                ':foto_selo'                 => $foto_selo,
                ':foto_ocupacao'             => $foto_ocupacao,
                ':latitude'                  => $latitude,
                ':longitude'                 => $longitude,
                ':altitude'                  => $altitude,
                ':precisao'                  => $precisao
            ]);
            //echo "Executou Importação Domicílios: $numero_selo, $id_submissao_pai, $index_kobo<br/>";
            $linhas++;
        }
        fclose($handle_domicilios);
        //responderJSON(true, "Sucesso! Foram importados/atualizados <strong>{$linhas}</strong> Domicílios vinculados.");
        return "Sucesso! Foram importados/atualizados <strong>{$linhas}</strong> Domicílios vinculados. <hr/> <br/>";
    } else {
        //responderJSON(false, "Não foi possível abrir o arquivo enviado.");
        return "Não foi possível abrir o arquivo Domicílios enviado. <hr/> <br/>";
    }
}

function importarCaracterizacao($arquivo, $pdo) {
    if (($handle_caracterizacao = fopen($arquivo, "r")) !== FALSE) {
        
        // Limpa o BOM (Byte Order Mark) se existir no início do arquivo
        $bom = fread($handle_caracterizacao, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle_caracterizacao);
        }

        // Pula a primeira linha física do cabeçalho
        fgetcsv($handle_caracterizacao, 4000, ";", '"', "\\");
       
        // Utilizando parâmetros nomeados (:id_submissao) para garantir legibilidade absoluta
        $sql = "INSERT INTO caracterizacao_vulnerabilidade_import 
                (id_submissao, uuid, codigo_selo, foto_selo, quantidade_comodos, comodos_improvisados_dormitorio, 
                 mais_de_3_por_dormitorio, faltam_camas, possui_banheiro, numero_banheiros, 
                 revestimento_ceramico_banheiro, louca_sanitaria_banheiro, problemas_estruturais_observados, 
                 problemas_infiltracao, existe_comodo_com_mofo, comodos_com_mofo, paredes_materiais, 
                 paredes_condicao, cobertura_materiais, cobertura_condicao, piso_materiais, 
                 piso_condicao, energia_acesso, energia_condicao_instalacao, energia_condicao_instalacao_internas, agua_acesso, 
                 materiais_componencatorios_observados, instalacoes_hidrosanitarias_disponives, condicoes_hidrosanitarias,
                 esgotamento_sanitario, condicoes_esgotamento_sanitario, coleta_lixo, frequencia_coleta_lixo, existe_drenagem, pavimentacao_rua, pcd_no_domicilio, 
                 quantidade_pcd, pessoas_mobilidade_reduzida, pessoas_cadeirantes, pessoas_doencas_respiratorias, quantidade_afetados_respiratorio, ha_condicoes_mobilidade, 
                 hove_acidentes_domesticos, ocorrencia_inundacao, frequencia_inundacao, altura_agua_inundacao, 
                 quando_chove, sensacao_seguranca_rua, possui_iluminacao_publica, possui_equipamentos_lazer, onde_equipamento_lazer, possui_equipamentos_saude, onde_equipamento_saude,
                 possui_transporte_publico, onde_transporte_publico, principal_meio_locomocao, posicao_portao_definitivo, possibilidade_cuidar_canteiro, tipo_plantas_canteiro, observacoes_gerais,
                 ha_escada, ha_comodos_sem_janela, qtd_comodos_sem_janela, ha_calcada_na_rua, imovel_terreno_tem_rachaduras, paredes_embarrigadas,
                 ha_postes_arvores_inclinados, houve_deslizamentos, onde_deslizamento, quando_deslizamento,
                 responsavel_coleta, data_registro)
                VALUES 
                (:id_submissao, :uuid, :codigo_selo, :foto_selo, :quantidade_comodos, :comodos_improvisados_dormitorio, 
                 :mais_de_3_por_dormitorio, :faltam_camas, :possui_banheiro, :numero_banheiros, 
                 :revestimento_ceramico_banheiro, :louca_sanitaria_banheiro, :problemas_estruturais_observados,
                 :problemas_infiltracao, :existe_comodo_com_mofo, :comodos_com_mofo, :paredes_materiais, 
                 :paredes_condicao, :cobertura_materiais, :cobertura_condicao, :piso_materiais, 
                 :piso_condicao, :energia_acesso, :energia_condicao_instalacao, :energia_condicao_instalacao_internas, :agua_acesso, 
                 :materiais_componencatorios_observados, :instalacoes_hidrosanitarias_disponives, :condicoes_hidrosanitarias,
                 :esgotamento_sanitario, :condicoes_esgotamento_sanitario, :coleta_lixo, :frequencia_coleta_lixo, :existe_drenagem, :pavimentacao_rua, :pcd_no_domicilio, 
                 :quantidade_pcd, :pessoas_mobilidade_reduzida, :pessoas_cadeirantes, :pessoas_doencas_respiratorias, :quantidade_afetados_respiratorio, :ha_condicoes_mobilidade, 
                 :hove_acidentes_domesticos, :ocorrencia_inundacao, :frequencia_inundacao, :altura_agua_inundacao, 
                 :quando_chove, :sensacao_seguranca_rua, :possui_iluminacao_publica, :possui_equipamentos_lazer, :onde_equipamento_lazer, :possui_equipamentos_saude, :onde_equipamento_saude,
                 :possui_transporte_publico, :onde_transporte_publico, :principal_meio_locomocao, :posicao_portao_definitivo, :possibilidade_cuidar_canteiro, :tipo_plantas_canteiro, :observacoes_gerais,
                 :ha_escada, :ha_comodos_sem_janela, :qtd_comodos_sem_janela, :ha_calcada_na_rua, :imovel_terreno_tem_rachaduras, :paredes_embarrigadas,
                 :ha_postes_arvores_inclinados, :houve_deslizamentos, :onde_deslizamento, :quando_deslizamento,
                 :responsavel_coleta, :data_registro)
                ON DUPLICATE KEY UPDATE id_submissao=id_submissao;";
                
        $stmt = $pdo->prepare($sql);
        $linhas = 0;

        while (($data = fgetcsv($handle_caracterizacao, 4000, ";", '"', "\\")) !== FALSE) {
            // for ($i = 0; $i < count($data); $i++) {
            //     echo "[$i] " . $data[$i] . "<br/>";
            // }
            // Validação de Segurança: Garante que a linha atual possui o ID numérico do KoboToolbox
            if (!isset($data[163]) || trim($data[163]) === '' || !is_numeric(trim($data[163]))) {
                continue; // Ignora cabeçalhos residuais ou linhas em branco no fim do arquivo
            }

            // =========================================================================
            // DOCUMENTAÇÃO E DE-PARA DOS CAMPOS (Índices validados conforme o LOG real)
            // =========================================================================
            $id_submissao                    = trim($data[163]);
            $uuid                             = $data[164] ?? null;
            $codigo_selo                      = $data[2]   ?? null; // cod_selo
            $foto_selo                        = $data[4]   ?? null; // foto_selo

            // Dados de Habitabilidade Interna
            $quantidade_comodos               = is_numeric($data[3]) ? (int)$data[3] : 0;
            $comodos_improvisados_dormitorio  = $data[4]   ?? null;
            $mais_de_3_por_dormitorio         = $data[5]   ?? null;
            $faltam_camas                     = $data[6]   ?? null;
            $possui_banheiro                  = $data[7]  ?? null;
            $numero_banheiros                 = is_numeric($data[8]) ? (int)$data[8] : 0;
            $revestimento_ceramico_banheiro   = $data[9]  ?? null;
            $louca_sanitaria_banheiro         = $data[10]  ?? null;
            $problemas_estruturais_observados = $data[11]  ?? null; // Campo consolidado de observações e problemas aparentes
            $problemas_infiltracao            = $data[16]  ?? null; // Campo específico para infiltração, se houver
            $existe_comodo_com_mofo           = $data[26]  ?? null; // Campo específico para mofo, se houver
            $comodos_com_mofo                 = $data[27]  ?? null; // Campo específico para mofo, se houver

            // Materiais das Paredes, Cobertura e Piso
            $paredes_materiais                = $data[28]  ?? null;
            $paredes_condicao                 = $data[34]  ?? null; // Condições gerais aparentes
            $cobertura_materiais              = $data[35]  ?? null;
            $cobertura_condicao               = $data[43]  ?? null;
            $piso_materiais                   = $data[44]  ?? null;
            $piso_condicao                    = $data[52]  ?? null;
            
            // Infraestrutura Urbana / Serviços Públicos
            $energia_acesso                   = $data[53]  ?? null;
            $energia_condicao_instalacao      = $data[62]  ?? null;
            $energia_condicao_instalacao_internas = $data[54]  ?? null;
            $agua_acesso                      = $data[63]  ?? null;
            $materiais_componencatorios_observados = $data[65]  ?? null; // Campo específico para materiais componentes observados
            $instalacoes_hidrosanitarias_disponives = $data[70]  ?? null; // Campo específico para instalações hidrossanitárias disponíveis
            $condicoes_hidrosanitarias        = $data[79]  ?? null; // Campo específico para condições hidrossanitárias
            $esgotamento_sanitario            = $data[80]  ?? null; // ex: fossa_negra
            $condicoes_esgotamento_sanitario  = $data[82]  ?? null; // ex: esgoto_a_cielo_aberto
            $coleta_lixo                      = $data[83]  ?? null; // ex: sem_coleta...
            $frequencia_coleta_lixo           = $data[84]  ?? null; // ex: coleta_3_vezes_por_semana
            $existe_drenagem                  = $data[85]  ?? null; // ex: sim_nao
            $pavimentacao_rua                 = $data[86]  ?? null; // A_rua_pavimentada
            
            // Dados de Saúde e Vulnerabilidade Familiar
            $pcd_no_domicilio                 = $data[87]  ?? null;
            $quantidade_pcd                   = is_numeric($data[94]) ? (int)$data[94] : 0;
            $pessoas_mobilidade_reduzida      = $data[95]  ?? null;
            $pessoas_cadeirantes              = $data[96]  ?? null;
            $ha_condicoes_mobilidade = $data[97] ?? null; // H_a_condi_oes_de_mobilidade
            $pessoas_doencas_respiratorias    = $data[107] ?? null;   
            $quantidade_afetados_respiratorio = is_numeric($data[111]) ? (int)$data[111] : 0; // Quantidade_de_pessoas_afetadas_por_doencas_respiratorias        

            $ha_escada                        = $data[112] ?? null; // H_a_escada
            $ha_comodos_sem_janela            = $data[113] ?? null; // H_a_comodos_sem_janela
            $qtd_comodos_sem_janela           = is_numeric($data[114]) ? (int)$data[114] : 0; // Quantidade_de_comodos_sem_janela
            $ha_calcada_na_rua                = $data[115] ?? null; // H_a_calcada_na_rua
            $imovel_terreno_tem_rachaduras    = $data[116] ?? null; // O_imovel_ou_terreno_tem_rachaduras
            $paredes_embarrigadas             = $data[117] ?? null; // Paredes_embarrigadas
            $ha_postes_arvores_inclinados     = $data[118] ?? null; // H_a_postes_ou_arvores_inclinados
            $houve_deslizamentos              = $data[119] ?? null; // H_ouve_deslizamentos
            $onde_deslizamento               = $data[120] ?? null; // Onde_ocorreu_deslizamento
            $quando_deslizamento             = $data[121] ?? null; // Quando_ocorreu_deslizamento

            // Riscos Ambientais e Percepção
            $hove_acidentes_domesticos        = $data[98] ?? null; // H_ouve_acidentes_dom_sticos

            $ocorrencia_inundacao             = $data[122] ?? null; // J_houve_ocorr_ncia_de_inunda_
            $frequencia_inundacao            = $data[123] ?? null; // J_frequencia_inunda_ao
            $altura_agua_inundacao           = $data[124] ?? null; // J_altura_da_gua_durante_inunda_
            $quando_chove                    = $data[125] ?? null; // J_quando_chove_inunda


            $sensacao_seguranca_rua           = is_numeric($data[134]) ? (int)$data[134] : 3;
            $possui_iluminacao_publica       = $data[133] ?? null; // H_possui_ilumina_ao_p_blica
            $possui_equipamentos_lazer       = $data[135] ?? null; // H_possui_equipamentos_de_lazer
            $onde_equipamento_lazer          = $data[136] ?? null; // H_onde_est_o_os_equipamentos_de_lazer
            $possui_equipamentos_saude       = $data[137] ?? null; // H_possui_equipamentos_de_sa_de
            $onde_equipamento_saude          = $data[138] ?? null; // H_onde_est_o_os_equipamentos_de_sa_de
            $possui_transporte_publico       = $data[139] ?? null; // H_possui_acesso_a_transporte_p_blico
            $onde_transporte_publico          = $data[140] ?? null; // H_onde_est_o_os_equipamentos_de_sa_de
            $principal_meio_locomocao        = $data[141] ?? null; // H_principal_meio_de_locomoc_o

            $posicao_portao_definitivo = $data[143] ?? null; // H_posi_ao_do_port_o_de_entrada_do_domic_lio    
            $possibilidade_cuidar_canteiro = $data[144] ?? null; // H_possibilidade_de_cuidar_do_canteiro
            $tipo_plantas_canteiro = $data[152] ?? null; // H_tipo_de_plantas_do_canteiro
            $observacoes_gerais = $data[161] ?? null; // H_observa_oes_gerais

            $responsavel_coleta              = $data[160] ?? null; // Respons_vel_pela_coleta_de_lixo
            $data_registro    = (!empty($data[162]) && $data[162] !== '0000-00-00') ? $data[162] : null;

            // Executa passando um array associativo limpo e muito mais fácil de ler
            $stmt->execute([
                ':id_submissao'                    => $id_submissao,
                ':uuid'                            => $uuid,
                ':codigo_selo'                     => $codigo_selo,
                ':foto_selo'                       => $foto_selo,
                ':quantidade_comodos'              => $quantidade_comodos,
                ':comodos_improvisados_dormitorio' => $comodos_improvisados_dormitorio,
                ':mais_de_3_por_dormitorio'        => $mais_de_3_por_dormitorio,
                ':faltam_camas'                    => $faltam_camas,
                ':possui_banheiro'                 => $possui_banheiro,
                ':numero_banheiros'                => $numero_banheiros,
                ':revestimento_ceramico_banheiro'  => $revestimento_ceramico_banheiro,
                ':louca_sanitaria_banheiro'        => $louca_sanitaria_banheiro,
                ':problemas_estruturais_observados' => $problemas_estruturais_observados,
                ':problemas_infiltracao'           => $problemas_infiltracao,
                ':existe_comodo_com_mofo'           => $existe_comodo_com_mofo,
                ':comodos_com_mofo'                => $comodos_com_mofo,
                ':paredes_materiais'               => $paredes_materiais,
                ':paredes_condicao'                => $paredes_condicao,
                ':cobertura_materiais'             => $cobertura_materiais,
                ':cobertura_condicao'              => $cobertura_condicao,
                ':piso_materiais'                  => $piso_materiais,
                ':piso_condicao'                   => $piso_condicao,
                ':energia_acesso'                  => $energia_acesso,
                ':energia_condicao_instalacao'     => $energia_condicao_instalacao,
                ':energia_condicao_instalacao_internas' => $energia_condicao_instalacao_internas,
                ':agua_acesso'                     => $agua_acesso,
                ':materiais_componencatorios_observados' => $materiais_componencatorios_observados,
                ':instalacoes_hidrosanitarias_disponives' => $instalacoes_hidrosanitarias_disponives,
                ':condicoes_hidrosanitarias'       => $condicoes_hidrosanitarias,
                ':esgotamento_sanitario'           => $esgotamento_sanitario,
                ':condicoes_esgotamento_sanitario' => $condicoes_esgotamento_sanitario,
                ':coleta_lixo'                     => $coleta_lixo,
                ':frequencia_coleta_lixo'          => $frequencia_coleta_lixo,
                ':existe_drenagem'                 => $existe_drenagem,
                ':pavimentacao_rua'                => $pavimentacao_rua,
                ':pcd_no_domicilio'                => $pcd_no_domicilio,
                ':quantidade_pcd'                  => $quantidade_pcd,
                ':pessoas_mobilidade_reduzida'     => $pessoas_mobilidade_reduzida,
                ':pessoas_cadeirantes'             => $pessoas_cadeirantes,
                ':ha_condicoes_mobilidade'         => $ha_condicoes_mobilidade,
                ':pessoas_doencas_respiratorias'   => $pessoas_doencas_respiratorias,
                ':quantidade_afetados_respiratorio' => $quantidade_afetados_respiratorio,
                ':hove_acidentes_domesticos'       => $hove_acidentes_domesticos,
                ':ocorrencia_inundacao'            => $ocorrencia_inundacao,
                ':frequencia_inundacao'           => $frequencia_inundacao,
                ':altura_agua_inundacao'          => $altura_agua_inundacao,
                ':quando_chove'                   => $quando_chove,
                ':sensacao_seguranca_rua'          => $sensacao_seguranca_rua,
                ':possui_iluminacao_publica'      => $possui_iluminacao_publica,
                ':possui_equipamentos_lazer'      => $possui_equipamentos_lazer,
                ':onde_equipamento_lazer'         => $onde_equipamento_lazer,
                ':possui_equipamentos_saude'      => $possui_equipamentos_saude,
                ':onde_equipamento_saude'         => $onde_equipamento_saude,
                ':possui_transporte_publico'      => $possui_transporte_publico,
                ':onde_transporte_publico'       => $onde_transporte_publico,
                ':principal_meio_locomocao'       => $principal_meio_locomocao,
                ':posicao_portao_definitivo'      => $posicao_portao_definitivo,
                ':possibilidade_cuidar_canteiro'  => $possibilidade_cuidar_canteiro,
                ':tipo_plantas_canteiro'          => $tipo_plantas_canteiro,
                ':observacoes_gerais'             => $observacoes_gerais,
                ':ha_escada'                     => $ha_escada,
                ':ha_comodos_sem_janela'         => $ha_comodos_sem_janela,
                ':qtd_comodos_sem_janela'        => $qtd_comodos_sem_janela,
                ':ha_calcada_na_rua'             => $ha_calcada_na_rua,
                ':imovel_terreno_tem_rachaduras' => $imovel_terreno_tem_rachaduras,
                ':paredes_embarrigadas'          => $paredes_embarrigadas,
                ':ha_postes_arvores_inclinados'  => $ha_postes_arvores_inclinados,
                ':houve_deslizamentos'           => $houve_deslizamentos,
                ':onde_deslizamento'            => $onde_deslizamento,
                ':quando_deslizamento'          => $quando_deslizamento,
                ':responsavel_coleta'           => $responsavel_coleta,
                ':data_registro'                   => $data_registro
            ]); 
            
            $linhas++;
        }
        fclose($handle_caracterizacao);
        //responderJSON(true, "Sucesso! Caracterização Complementar de {$linhas} imóveis adicionada.");
        return "Sucesso! Caracterização Complementar de <strong>{$linhas}</strong> imóveis adicionada.<hr/> <br/>";
    } else {
        //responderJSON(false, "Não foi possível abrir o arquivo enviado.");
        return "Não foi possível abrir o arquivo enviado.<hr/> <br/>";
    }
}

function importarSociojuridico($arquivo, $pdo) {
    if (($handle_joridico = fopen($arquivo, "r")) !== FALSE) {
        
        // 1. Limpa o BOM (Byte Order Mark) se existir no início do arquivo
        $bom = fread($handle_joridico, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle_joridico);
        }

        // 2. Consome a primeira linha física do cabeçalho
        fgetcsv($handle_joridico, 4000, ";", '"', "\\");

        // Query SQL com placeholders totalmente espelhados
        $sql = "INSERT INTO cadastro_sociojuridico_import 
                (id_submissao, uuid, codigo_selo, foto_selo, r1_nome, r1_rg, r1_foto_rg, r1_cpf, r1_foto_cpf, r1_naturalidade, r1_data_nascimento, 
                 r1_estado_civil, foto_estado_civil, regime_bens_partes, r1_profissao, r1_escolaridade, r1_pcd, r1_especifiacao_pcd, r1_telefone, numero_residentes, 
                 renda_mensal_titular_1, renda_mensal_titular_2, renda_outras_fontes, foto_comprovante_renda1, foto_comprovante_renda2, foto_comprovante_renda3, cadunico_nis, numero_nis,
                 recebe_beneficio_social, beneficios_detalhe, relacao_com_imovel, forma_aquisicao, foto_comprovante_aquisicao, assinou_requerimento_regularizacao, foto_requerimento_regularizacao,
                 tempo_ocupacao, foto_comprovante_ocupacao_2022, foto_comprovante_ocupacao_2023, foto_comprovante_ocupacao_2024, foto_comprovante_ocupacao_2025, foto_comprovante_ocupacao_2026,
                 paga_iptu, assinou_unica_propriedade, foto_declaracao_unica_propriedade, assinou_ocupacao_mansa_pacifica, foto_declaracao_ocupacao_mansa_pacifica,
                 assinou_veracidade, foto_declaracao_veracidade, assinou_lgpd, foto_declaracao_lgpd, nome_cadastrador, data_registro)
                VALUES 
                (:id_submissao, :uuid, :codigo_selo, :foto_selo, :r1_nome, :r1_rg, :r1_foto_rg, :r1_cpf, :r1_foto_cpf, :r1_naturalidade, :r1_data_nascimento, 
                 :r1_estado_civil, :r1_foto_estado_civil, :regime_bens_partes, :r1_profissao, :r1_escolaridade, :r1_pcd, :r1_especifiacao_pcd, :r1_telefone, :numero_residentes, 
                 :renda_mensal_titular_1, :renda_mensal_titular_2, :renda_outras_fontes, :foto_comprovante_renda1, :foto_comprovante_renda2, :foto_comprovante_renda3, :cadunico_nis, :numero_nis,
                 :recebe_beneficio_social, :beneficios_detalhe, :relacao_com_imovel, :forma_aquisicao, :foto_comprovante_aquisicao, :assinou_requerimento_regularizacao, :foto_requerimento_regularizacao, 
                 :tempo_ocupacao, :foto_comprovante_ocupacao_2022, :foto_comprovante_ocupacao_2023, :foto_comprovante_ocupacao_2024, :foto_comprovante_ocupacao_2025, :foto_comprovante_ocupacao_2026,
                 :paga_iptu, :assinou_unica_propriedade, :foto_declaracao_unica_propriedade, :assinou_ocupacao_mansa_pacifica, :foto_declaracao_ocupacao_mansa_pacifica,
                 :assinou_veracidade, :foto_declaracao_veracidade, :assinou_lgpd, :foto_declaracao_lgpd, :nome_cadastrador, :data_registro)
                ON DUPLICATE KEY UPDATE id_submissao=id_submissao;";
                
        $stmt = $pdo->prepare($sql);
        $linhas = 0;

        while (($data = fgetcsv($handle_joridico, 4000, ";", '"', "\\")) !== FALSE) {
            
            // Validação de segurança: Valida o ID de submissão no índice [68]
            if (!isset($data[71]) || trim($data[71]) === '' || !is_numeric(trim($data[71]))) {
                continue; 
            }

            // Mapeamento corrigido baseado nos índices reais do seu log
            $id_submissao     = trim($data[71]);                 // 766470405
            $uuid             = $data[72] ?? null;               // b5c274f3-3096-4286-a8c0-7961ab6edba1 (Ajustado)
            $codigo_selo      = $data[3]  ?? null;               // BIS-0-016A-0028
            $foto_selo        = $data[4]  ?? null;               // 1780146176940.jpg

            // Dados Titular R1
            $r1_nome          = $data[5]  ?? null;               // Lucianne Mendes da Silva
            $r1_rg            = $data[7]  ?? null;               // 2.592.458
            $r1_foto_rg       = $data[8]  ?? null;               // 1780146421415.jpg
            $r1_cpf           = isset($data[9]) ? str_replace(['.', '-'], '', $data[9]) : null;
            $r1_foto_cpf      = $data[10]  ?? null;               // 1780146501569.jpg
            $r1_naturalidade  = $data[11] ?? null;               // PINDARÉ MIRIN-MA
            $r1_data_nascimento = (!empty($data[12]) && $data[12] !== '0000-00-00') ? $data[12] : null;
            $r1_estado_civil  = $data[13] ?? null;               // Solteira(o)
            $r1_foto_estado_civil = $data[14] ?? null;
            $r1_regime_bens_partes = $data[15] ?? null;
            $r1_profissao     = $data[16] ?? null;               // SECRETÁRIA DO LAR
            $r1_escolaridade  = $data[17] ?? null;               // Ensino Fundamental Incompleto
            $r1_pcd           = $data[18] ?? null;               // Não
            $r1_especifiacao_pcd = $data[19] ?? null;
            $r1_telefone      = $data[6] ?? null;               // (61)99944-5303
            
            // Financeiro e Residentes
            $numero_residentes      = is_numeric($data[24] ?? null) ? (int)$data[24] : 1;
            $renda_mensal_titular_1 = isset($data[25]) ? (float)str_replace(',', '.', $data[25]) : 0.00;
            $renda_mensal_titular_2 = isset($data[26]) ? (float)str_replace(',', '.', $data[26]) : 0.00;
            $renda_outras_fontes    = isset($data[27]) ? (float)str_replace(',', '.', $data[27]) : 0.00;
            $foto_comprovante_renda1 = $data[28] ?? null;
            $foto_comprovante_renda2 = $data[29] ?? null;
            $foto_comprovante_renda3 = $data[30] ?? null;
            
            // Benefícios
            $cadunico_nis            = $data[31] ?? null;
            $numero_nis              = $data[32] ?? null;
            $recebe_beneficio_social = $data[33] ?? null;
            $beneficios_detalhe      = $data[34] ?? null;
            
            // Imóvel
            $relacao_com_imovel          = $data[46] ?? null;
            $forma_aquisicao             = $data[47] ?? null;
            $foto_comprovante_aquisicao  = $data[48] ?? null;
            $tempo_ocupacao              = $data[52] ?? null;
            $foto_comprovante_ocupacao_2022 = $data[57] ?? null;
            $foto_comprovante_ocupacao_2023 = $data[56] ?? null;
            $foto_comprovante_ocupacao_2024 = $data[55] ?? null;
            $foto_comprovante_ocupacao_2025 = $data[54] ?? null;
            $foto_comprovante_ocupacao_2026 = $data[53] ?? null;
            $paga_iptu                   = $data[58] ?? null;
                        
            // Declarações
            $assinou_unica_propriedade          = $data[59] ?? null;
            $foto_declaracao_unica_propriedade  = $data[60] ?? null;

            $assinou_requerimento_regularizacao  = $data[61] ?? null;
            $foto_requerimento_regularizacao    = $data[62] ?? null;
            $assinou_ocupacao_mansa_pacifica    = $data[63] ?? null;
            $foto_declaracao_ocupacao_mansa_pacifica = $data[64] ?? null;
            $assinou_veracidade                 = $data[65] ?? null;
            $foto_declaracao_veracidade         = $data[66] ?? null;

            $assinou_lgpd                       = $data[67] ?? null;
            $foto_declaracao_lgpd               = $data[68] ?? null;
            
            // Metadados
            $nome_cadastrador = $data[69] ?? null;
            $data_registro    = (!empty($data[70]) && $data[70] !== '0000-00-00') ? $data[70] : null;

            // Execução com os 49 parâmetros rigorosamente pareados
            $stmt->execute([
                ':id_submissao'                         => $id_submissao,
                ':uuid'                                 => $uuid,
                ':codigo_selo'                          => $codigo_selo,
                ':foto_selo'                            => $foto_selo,
                ':r1_nome'                              => $r1_nome,
                ':r1_rg'                                => $r1_rg,
                ':r1_foto_rg'                           => $r1_foto_rg,
                ':r1_cpf'                               => $r1_cpf,
                ':r1_foto_cpf'                          => $r1_foto_cpf,
                ':r1_naturalidade'                      => $r1_naturalidade,
                ':r1_data_nascimento'                   => $r1_data_nascimento,
                ':r1_estado_civil'                      => $r1_estado_civil,
                ':r1_foto_estado_civil'                 => $r1_foto_estado_civil,
                ':regime_bens_partes'                   => $r1_regime_bens_partes,
                ':r1_profissao'                         => $r1_profissao,
                ':r1_escolaridade'                      => $r1_escolaridade,
                ':r1_pcd'                               => $r1_pcd,
                ':r1_especifiacao_pcd'                  => $r1_especifiacao_pcd,
                ':r1_telefone'                          => $r1_telefone,
                ':numero_residentes'                    => $numero_residentes,
                ':renda_mensal_titular_1'               => $renda_mensal_titular_1,
                ':renda_mensal_titular_2'               => $renda_mensal_titular_2,
                ':renda_outras_fontes'                  => $renda_outras_fontes,
                ':foto_comprovante_renda1'              => $foto_comprovante_renda1,
                ':foto_comprovante_renda2'              => $foto_comprovante_renda2,
                ':foto_comprovante_renda3'              => $foto_comprovante_renda3,
                ':cadunico_nis'                         => $cadunico_nis,
                ':numero_nis'                           => $numero_nis,
                ':recebe_beneficio_social'              => $recebe_beneficio_social,
                ':beneficios_detalhe'                   => $beneficios_detalhe,
                ':relacao_com_imovel'                   => $relacao_com_imovel,
                ':forma_aquisicao'                      => $forma_aquisicao,
                ':foto_comprovante_aquisicao'           => $foto_comprovante_aquisicao,
                ':assinou_requerimento_regularizacao'   => $assinou_requerimento_regularizacao,
                ':foto_requerimento_regularizacao'     => $foto_requerimento_regularizacao,
                ':tempo_ocupacao'                       => $tempo_ocupacao,
                ':foto_comprovante_ocupacao_2022'       => $foto_comprovante_ocupacao_2022,
                ':foto_comprovante_ocupacao_2023'       => $foto_comprovante_ocupacao_2023,
                ':foto_comprovante_ocupacao_2024'       => $foto_comprovante_ocupacao_2024,
                ':foto_comprovante_ocupacao_2025'       => $foto_comprovante_ocupacao_2025,
                ':foto_comprovante_ocupacao_2026'       => $foto_comprovante_ocupacao_2026,
                ':paga_iptu'                            => $paga_iptu,
                ':assinou_unica_propriedade'            => $assinou_unica_propriedade,
                ':foto_declaracao_unica_propriedade'    => $foto_declaracao_unica_propriedade,
                ':assinou_ocupacao_mansa_pacifica'      => $assinou_ocupacao_mansa_pacifica,
                ':foto_declaracao_ocupacao_mansa_pacifica' => $foto_declaracao_ocupacao_mansa_pacifica,
                ':assinou_veracidade'                   => $assinou_veracidade,
                ':foto_declaracao_veracidade'           => $foto_declaracao_veracidade,
                ':assinou_lgpd'                         => $assinou_lgpd,
                ':foto_declaracao_lgpd'                 => $foto_declaracao_lgpd,
                ':nome_cadastrador'                     => $nome_cadastrador,
                ':data_registro'                        => $data_registro
            ]);
            
            $linhas++;
        }
        fclose($handle_joridico);
        return "Sucesso! Cadastro Sociojurídico de <strong>{$linhas}</strong> famílias importado. <hr/> <br/>"; 
    } else {
        return "Não foi possível abrir o arquivo Cadastro Sociojurídico enviado. <hr/> <br/>";
    }
}

// Helper para padronizar o retorno da API para a interface do usuário
function responderJSON($sucesso, $mensagem) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'sucesso' => $sucesso,
        'mensagem' => $mensagem
    ], JSON_UNESCAPED_UNICODE);
    //exit;
}

?>

<div class="container-fluid">
    <div class="card mb-4 border-primary" style="max-width: 950px; margin: 0 auto;">
        <div class="card-header py-3 bg-gradient-primary d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-white">
                <i class="fa fa-home mr-2"></i> importação de arquivos CSV para o banco de dados
            </h6>
            <a class="btn btn-outline-light btn-sm" href="#" onclick="history.back();">
                <i class="fa fa-arrow-left"></i> Voltar
            </a>
        </div>
        
        <div class="card-body">
            <?= $resultado ?>
        </div>
    </div>
</div>

</body>
</html>