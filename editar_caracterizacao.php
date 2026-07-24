<?php

require_once('./verifica_login.php');
include_once('actions/ManterRelatorio.php');

$manterRelatorio = new ManterRelatorio();

// Captura o Código do Selo enviado via URL
$codigo_selo_url = isset($_GET['selo']) ? trim($_GET['selo']) : '';

// Busca os dados de caracterização vinculados a este selo
$caract = $manterRelatorio->getCaracterizacaoPorCodigoSelo($codigo_selo_url);

// Determina se o registro já existe no banco de dados
$isEdit = isset($caract->id_submissao) && (int)$caract->id_submissao > 0;

// Se for um novo cadastro, pré-vincula o código do selo recebido
if (!$isEdit) {
    $caract = new stdClass();
    $caract->id_submissao = '';
    $caract->uuid = '';
    $caract->codigo_selo = $codigo_selo_url;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Caracterização Física e Vulnerabilidade - REURB</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.css">
</head>
<body id="page-top">

    <div id="wrapper">
        <?php include './menu.php'; ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './top_bar.php'; ?>

                <div class="container-fluid">
                    <div class="card mb-4 border-primary" style="max-width: 1000px; margin: 0 auto;">
                        <div class="card-header py-3 bg-gradient-primary d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-white">
                                <i class="fa fa-building mr-2"></i> Ficha de Caracterização Física e Vulnerabilidade
                            </h6>
                            <a class="btn btn-outline-light btn-sm" href="#" onclick="window.history.back();">
                                <i class="fa fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                        
                        <div class="card-body bg-light">
                            <form action="save_caracterizacao.php" method="POST">
                                
                                <input type="hidden" name="is_edit" value="<?= $isEdit ? '1' : '0' ?>">

                                <!-- Bloco 1: Vínculos e Metadados -->
                                <div class="card card-body mb-3 shadow-sm border-left-primary">
                                    <h5 class="text-primary font-weight-bold mb-3">1. Identificação do Registro</h5>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label class="font-weight-bold text-dark">ID Submissão (Chave Primária):</label>
                                            <input type="text" class="form-control" name="id_submissao" value="<?= $caract->id_submissao ?>" <?= $isEdit ? 'readonly' : 'required placeholder="ID da submissão do Kobo"' ?>>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="uuid" class="font-weight-bold text-dark">UUID único:</label>
                                            <input type="text" class="form-control" id="uuid" name="uuid" value="<?= isset($caract->uuid) ? htmlspecialchars($caract->uuid) : '' ?>" required>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="codigo_selo" class="font-weight-bold text-dark">Código do Selo Vinculado:</label>
                                            <input type="text" class="form-control font-weight-bold text-danger" id="codigo_selo" name="codigo_selo" value="<?= htmlspecialchars($caract->codigo_selo) ?>" readonly>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bloco 2: Habitabilidade e Cômodos -->
                                <div class="card card-body mb-3 shadow-sm border-left-success">
                                    <h5 class="text-success font-weight-bold mb-3">2. Condições de Habitabilidade Interna</h5>
                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <label for="quantidade_comodos" class="font-weight-bold text-dark">Quantidade de Cômodos:</label>
                                            <input type="number" class="form-control" id="quantidade_comodos" name="quantidade_comodos" value="<?= isset($caract->quantidade_comodos) ? $caract->quantidade_comodos : 0 ?>">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="comodos_improvisados_dormitorio" class="font-weight-bold text-dark">Cômodos Improvisados?</label>
                                            <select class="form-control" id="comodos_improvisados_dormitorio" name="comodos_improvisados_dormitorio">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($caract->comodos_improvisados_dormitorio) && $caract->comodos_improvisados_dormitorio == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($caract->comodos_improvisados_dormitorio) && $caract->comodos_improvisados_dormitorio == 'Não') ? 'selected' : '' ?>>Não</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="mais_de_3_por_dormitorio" class="font-weight-bold text-dark">Adensamento (+3 p/ quarto)?</label>
                                            <select class="form-control" id="mais_de_3_por_dormitorio" name="mais_de_3_por_dormitorio">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($caract->mais_de_3_por_dormitorio) && $caract->mais_de_3_por_dormitorio == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($caract->mais_de_3_por_dormitorio) && $caract->mais_de_3_por_dormitorio == 'Não') ? 'selected' : '' ?>>Não</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="faltam_camas" class="font-weight-bold text-dark">Faltam Camas?</label>
                                            <select class="form-control" id="faltam_camas" name="faltam_camas">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($caract->faltam_camas) && $caract->faltam_camas == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($caract->faltam_camas) && $caract->faltam_camas == 'Não') ? 'selected' : '' ?>>Não</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <label for="possui_banheiro" class="font-weight-bold text-dark">Possui Banheiro?</label>
                                            <select class="form-control" id="possui_banheiro" name="possui_banheiro">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($caract->possui_banheiro) && $caract->possui_banheiro == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($caract->possui_banheiro) && $caract->possui_banheiro == 'Não') ? 'selected' : '' ?>>Não</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="numero_banheiros" class="font-weight-bold text-dark">Número de Banheiros:</label>
                                            <input type="number" class="form-control" id="numero_banheiros" name="numero_banheiros" value="<?= isset($caract->numero_banheiros) ? $caract->numero_banheiros : 0 ?>">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="revestimento_ceramico_banheiro" class="font-weight-bold text-dark">Revestimento Cerâmico?</label>
                                            <select class="form-control" id="revestimento_ceramico_banheiro" name="revestimento_ceramico_banheiro">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($caract->revestimento_ceramico_banheiro) && $caract->revestimento_ceramico_banheiro == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($caract->revestimento_ceramico_banheiro) && $caract->revestimento_ceramico_banheiro == 'Não') ? 'selected' : '' ?>>Não</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="louca_sanitaria_banheiro" class="font-weight-bold text-dark">Possui Louça Sanitária?</label>
                                            <select class="form-control" id="louca_sanitaria_banheiro" name="louca_sanitaria_banheiro">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($caract->louca_sanitaria_banheiro) && $caract->louca_sanitaria_banheiro == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($caract->louca_sanitaria_banheiro) && $caract->louca_sanitaria_banheiro == 'Não') ? 'selected' : '' ?>>Não</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bloco 3: Materiais e Estrutura -->
                                <div class="card card-body mb-3 shadow-sm border-left-warning">
                                    <h5 class="text-warning font-weight-bold mb-3">3. Materiais Construtivos e Patologias</h5>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="paredes_materiais" class="font-weight-bold text-dark">Material das Paredes:</label>
                                            <select class="form-control" id="paredes_materiais" name="paredes_materiais">
                                                <option value="">-- Selecione --</option>
                                                <?php $m_paredes = ["Alvenaria c/ reboco", "Alvenaria sem reboco", "Madeira tratada", "Madeira aproveitada/Taipa", "Material improvisado"];
                                                foreach($m_paredes as $m) { $s = (isset($caract->paredes_materiais) && $caract->paredes_materiais == $m) ? 'selected' : ''; echo "<option value='{$m}' {$s}>{$m}</option>"; } ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="cobertura_materiais" class="font-weight-bold text-dark">Material da Cobertura:</label>
                                            <select class="form-control" id="cobertura_materiais" name="cobertura_materiais">
                                                <option value="">-- Selecione --</option>
                                                <?php $m_cobertura = ["Telha cerâmica", "Telha amianto/fibrocimento", "Laje de concreto", "Zinco/Metal", "Lona/Improvisado"];
                                                foreach($m_cobertura as $m) { $s = (isset($caract->cobertura_materiais) && $caract->cobertura_materiais == $m) ? 'selected' : ''; echo "<option value='{$m}' {$s}>{$m}</option>"; } ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="piso_materiais" class="font-weight-bold text-dark">Material do Piso:</label>
                                            <select class="form-control" id="piso_materiais" name="piso_materiais">
                                                <option value="">-- Selecione --</option>
                                                <?php $m_piso = ["Cerâmica/Porcelanato", "Cimento queimado", "Cimento bruto", "Madeira", "Terra batida"];
                                                foreach($m_piso as $m) { $s = (isset($caract->piso_materiais) && $caract->piso_materiais == $m) ? 'selected' : ''; echo "<option value='{$m}' {$s}>{$m}</option>"; } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="problemas_estruturais_observados" class="font-weight-bold text-dark">Problemas Estruturais:</label>
                                            <input type="text" class="form-control" id="problemas_estruturais_observados" name="problemas_estruturais_observados" value="<?= isset($caract->problemas_estruturais_observados) ? htmlspecialchars($caract->problemas_estruturais_observados) : '' ?>">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="problemas_infiltracao" class="font-weight-bold text-dark">Problemas de Infiltração:</label>
                                            <input type="text" class="form-control" id="problemas_infiltracao" name="problemas_infiltracao" value="<?= isset($caract->problemas_infiltracao) ? htmlspecialchars($caract->problemas_infiltracao) : '' ?>">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="existe_comodo_com_mofo" class="font-weight-bold text-dark">Existe cômodo com mofo?</label>
                                            <select class="form-control" id="existe_comodo_com_mofo" name="existe_comodo_com_mofo">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($caract->existe_comodo_com_mofo) && $caract->existe_comodo_com_mofo == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($caract->existe_comodo_com_mofo) && $caract->existe_comodo_com_mofo == 'Não') ? 'selected' : '' ?>>Não</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bloco 4: Infraestrutura Urbana e Serviços -->
                                <div class="card card-body mb-3 shadow-sm border-left-info">
                                    <h5 class="text-info font-weight-bold mb-3">4. Infraestrutura Urbana e Serviços Públicos</h5>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="energia_acesso" class="font-weight-bold text-dark">Acesso à Energia:</label>
                                            <select class="form-control" id="energia_acesso" name="energia_acesso">
                                                <option value="">-- Selecione --</option>
                                                <option value="Oficial da Concessionária" <?= (isset($caract->energia_acesso) && $caract->energia_acesso == 'Oficial da Concessionária') ? 'selected' : '' ?>>Oficial da Concessionária</option>
                                                <option value="Ligação Clandestina (Gato)" <?= (isset($caract->energia_acesso) && $caract->energia_acesso == 'Ligação Clandestina (Gato)') ? 'selected' : '' ?>>Ligação Clandestina (Gato)</option>
                                                <option value="Não Possui" <?= (isset($caract->energia_acesso) && $caract->energia_acesso == 'Não Possui') ? 'selected' : '' ?>>Não Possui</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="agua_acesso" class="font-weight-bold text-dark">Acesso à Água:</label>
                                            <select class="form-control" id="agua_acesso" name="agua_acesso">
                                                <option value="">-- Selecione --</option>
                                                <option value="Rede Oficial" <?= (isset($caract->agua_acesso) && $caract->agua_acesso == 'Rede Oficial') ? 'selected' : '' ?>>Rede Oficial</option>
                                                <option value="Poço Artesiano" <?= (isset($caract->agua_acesso) && $caract->agua_acesso == 'Poço Artesiano') ? 'selected' : '' ?>>Poço Artesiano</option>
                                                <option value="Caminhão Pipa" <?= (isset($caract->agua_acesso) && $caract->agua_acesso == 'Caminhão Pipa') ? 'selected' : '' ?>>Caminhão Pipa</option>
                                                <option value="Ligação Irregular" <?= (isset($caract->agua_acesso) && $caract->agua_acesso == 'Ligação Irregular') ? 'selected' : '' ?>>Ligação Irregular</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="esgotamento_sanitario" class="font-weight-bold text-dark">Esgotamento Sanitário:</label>
                                            <select class="form-control" id="esgotamento_sanitario" name="esgotamento_sanitario">
                                                <option value="">-- Selecione --</option>
                                                <option value="Rede Pública" <?= (isset($caract->esgotamento_sanitario) && $caract->esgotamento_sanitario == 'Rede Pública') ? 'selected' : '' ?>>Rede Pública</option>
                                                <option value="Fossa Séptica" <?= (isset($caract->esgotamento_sanitario) && $caract->esgotamento_sanitario == 'Fossa Séptica') ? 'selected' : '' ?>>Fossa Séptica</option>
                                                <option value="Fossa Negra/Céu Aberto" <?= (isset($caract->esgotamento_sanitario) && $caract->esgotamento_sanitario == 'Fossa Negra/Céu Aberto') ? 'selected' : '' ?>>Fossa Negra/Céu Aberto</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="coleta_lixo" class="font-weight-bold text-dark">Coleta de Lixo:</label>
                                            <select class="form-control" id="coleta_lixo" name="coleta_lixo">
                                                <option value="">-- Selecione --</option>
                                                <option value="Regular Porta a Porta" <?= (isset($caract->coleta_lixo) && $caract->coleta_lixo == 'Regular Porta a Porta') ? 'selected' : '' ?>>Regular Porta a Porta</option>
                                                <option value="Em Caçamba Comunitária" <?= (isset($caract->coleta_lixo) && $caract->coleta_lixo == 'Em Caçamba Comunitária') ? 'selected' : '' ?>>Em Caçamba Comunitária</option>
                                                <option value="Não há coleta" <?= (isset($caract->coleta_lixo) && $caract->coleta_lixo == 'Não há coleta') ? 'selected' : '' ?>>Não há coleta</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="pavimentacao_rua" class="font-weight-bold text-dark">Pavimentação da Rua:</label>
                                            <select class="form-control" id="pavimentacao_rua" name="pavimentacao_rua">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($caract->pavimentacao_rua) && $caract->pavimentacao_rua == 'Sim') ? 'selected' : '' ?>>Sim (Asfalto/Bloquete)</option>
                                                <option value="Não" <?= (isset($caract->pavimentacao_rua) && $caract->pavimentacao_rua == 'Não') ? 'selected' : '' ?>>Não (Terra/Chão batido)</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="responsavel_coleta" class="font-weight-bold text-dark">Técnico de Campo (Vistoria):</label>
                                            <input type="text" class="form-control" id="responsavel_coleta" name="responsavel_coleta" value="<?= isset($caract->responsavel_coleta) ? htmlspecialchars($caract->responsavel_coleta) : '' ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="text-right mt-4">
                                    <button type="submit" class="btn btn-primary shadow-sm">
                                        <i class="fa fa-save"></i> Salvar Alterações
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php include './rodape.php'; ?>
        </div>
    </div>
</body>
</html>