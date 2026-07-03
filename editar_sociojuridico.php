<?php
require_once('./verifica_login.php');
include_once('actions/ManterRelatorio.php');

$manterRelatorio = new ManterRelatorio();

$codigo_selo_url = isset($_GET['selo']) ? trim($_GET['selo']) : '';
$socio = $manterRelatorio->getSociojuridicoPorCodigoSelo($codigo_selo_url);
$listaDomicilios = $manterRelatorio->listarDomicilios();

$isEdit = isset($socio->id_submissao) && (int)$socio->id_submissao > 0;

if (!$isEdit) {
    echo "<script>alert('Não existe sócio jurídico para este código de selo.'); window.location.href = 'selagens.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Cadastro Sóciojurídico - REURB</title>
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
                                <i class="fa fa-balance-scale mr-2"></i> Ficha Sóciojurídica por Código de Selo
                            </h6>
                            <a class="btn btn-outline-light btn-sm" href="#" onclick="window.history.back();">
                                <i class="fa fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                        
                        <div class="card-body bg-light">
                            <form action="save_sociojuridico.php" method="POST">
                                
                                <input type="hidden" name="is_edit" value="<?= $isEdit ? '1' : '0' ?>">

                                <!-- Bloco 1: Identificadores de Amarração -->
                                <div class="card card-body mb-3 shadow-sm border-left-primary">
                                    <h5 class="text-primary font-weight-bold mb-3">1. Vínculos do Sistema</h5>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label class="font-weight-bold text-dark">ID Submissão (Chave Primária):</label>
                                            <input type="text" class="form-control" name="id_submissao" value="<?= isset($socio->id_submissao) ? $socio->id_submissao : '' ?>" <?= $isEdit ? 'readonly' : 'required placeholder="ID da submissão do Kobo"' ?>>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="uuid" class="font-weight-bold text-dark">UUID único:</label>
                                            <input type="text" class="form-control" id="uuid" name="uuid" value="<?= isset($socio->uuid) ? htmlspecialchars($socio->uuid) : '' ?>" required>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="codigo_selo" class="font-weight-bold text-dark">Código do Selo Vinculado:</label>
                                            <input type="text" class="form-control font-weight-bold text-danger" id="codigo_selo" name="codigo_selo" value="<?= isset($socio->codigo_selo) ? htmlspecialchars($socio->codigo_selo) : '' ?>" <?= $isEdit ? 'readonly' : 'required' ?>>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bloco 2: Responsável Principal (R1) -->
                                <div class="card card-body mb-3 shadow-sm border-left-success">
                                    <h5 class="text-success font-weight-bold mb-3">2. Dados do Responsável Principal</h5>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="r1_nome" class="font-weight-bold text-dark">Nome Completo:</label>
                                            <input type="text" class="form-control" id="r1_nome" name="r1_nome" value="<?= isset($socio->r1_nome) ? htmlspecialchars($socio->r1_nome) : '' ?>" required>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="r1_cpf" class="font-weight-bold text-dark">CPF:</label>
                                            <input type="text" class="form-control" id="r1_cpf" name="r1_cpf" value="<?= isset($socio->r1_cpf) ? htmlspecialchars($socio->r1_cpf) : '' ?>">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="r1_rg" class="font-weight-bold text-dark">RG:</label>
                                            <input type="text" class="form-control" id="r1_rg" name="r1_rg" value="<?= isset($socio->r1_rg) ? htmlspecialchars($socio->r1_rg) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <label for="r1_data_nascimento" class="font-weight-bold text-dark">Data de Nascimento:</label>
                                            <input type="date" class="form-control" id="r1_data_nascimento" name="r1_data_nascimento" value="<?= isset($socio->r1_data_nascimento) ? $socio->r1_data_nascimento : '' ?>">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="r1_estado_civil" class="font-weight-bold text-dark">Estado Civil:</label>
                                            <!-- 🔥 Alterado para SELECT -->
                                            <select class="form-control" id="r1_estado_civil" name="r1_estado_civil">
                                                <option value="">-- Selecione --</option>
                                                <?php
                                                $estados = ["Solteira(o)", "Casada(o)", "União Estável", "Divorciada(o)", "Viúva(o)"];
                                                foreach($estados as $e) {
                                                    $sel = (isset($socio->r1_estado_civil) && $socio->r1_estado_civil == $e) ? 'selected' : '';
                                                    echo "<option value='{$e}' {$sel}>{$e}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="r1_profissao" class="font-weight-bold text-dark">Profissão:</label>
                                            <input type="text" class="form-control" id="r1_profissao" name="r1_profissao" value="<?= isset($socio->r1_profissao) ? htmlspecialchars($socio->r1_profissao) : '' ?>">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="r1_telefone" class="font-weight-bold text-dark">Telefone R1:</label>
                                            <input type="text" class="form-control" id="r1_telefone" name="r1_telefone" value="<?= isset($socio->r1_telefone) ? htmlspecialchars($socio->r1_telefone) : '' ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="r1_naturalidade" class="font-weight-bold text-dark">Naturalidade / UF:</label>
                                            <input type="text" class="form-control" id="r1_naturalidade" name="r1_naturalidade" value="<?= isset($socio->r1_naturalidade) ? htmlspecialchars($socio->r1_naturalidade) : '' ?>">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="r1_escolaridade" class="font-weight-bold text-dark">Escolaridade:</label>
                                            <input type="text" class="form-control" id="r1_escolaridade" name="r1_escolaridade" value="<?= isset($socio->r1_escolaridade) ? htmlspecialchars($socio->r1_escolaridade) : '' ?>">
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="r1_pcd" class="font-weight-bold text-dark">é PCD?</label>
                                            <!-- 🔥 Alterado para SELECT -->
                                            <select class="form-control" id="r1_pcd" name="r1_pcd">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($socio->r1_pcd) && $socio->r1_pcd == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($socio->r1_pcd) && $socio->r1_pcd == 'Não') ? 'selected' : '' ?>>Não</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-2">
                                            <label for="r1_especifiacao_pcd" class="font-weight-bold text-dark">CID / PCD:</label>
                                            <input type="text" class="form-control" id="r1_especifiacao_pcd" name="r1_especifiacao_pcd" value="<?= isset($socio->r1_especifiacao_pcd) ? htmlspecialchars($socio->r1_especifiacao_pcd) : '' ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Bloco 3: Economia Familiar e Benefícios -->
                                <div class="card card-body mb-3 shadow-sm border-left-info">
                                    <h5 class="text-info font-weight-bold mb-3">3. Composição Familiar e Finanças</h5>
                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <label for="numero_residentes" class="font-weight-bold text-dark">Nº Moradores:</label>
                                            <input type="number" class="form-control" id="numero_residentes" name="numero_residentes" value="<?= isset($socio->numero_residentes) ? $socio->numero_residentes : 1 ?>">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="renda_mensal_titular_1" class="font-weight-bold text-dark">Renda Titular 1:</label>
                                            <input type="text" class="form-control" id="renda_mensal_titular_1" name="renda_mensal_titular_1" value="<?= isset($socio->renda_mensal_titular_1) ? $socio->renda_mensal_titular_1 : '0.00' ?>">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="renda_mensal_titular_2" class="font-weight-bold text-dark">Renda Titular 2:</label>
                                            <input type="text" class="form-control" id="renda_mensal_titular_2" name="renda_mensal_titular_2" value="<?= isset($socio->renda_mensal_titular_2) ? $socio->renda_mensal_titular_2 : '0.00' ?>">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="renda_outras_fontes" class="font-weight-bold text-dark">Outras Rendas:</label>
                                            <input type="text" class="form-control" id="renda_outras_fontes" name="renda_outras_fontes" value="<?= isset($socio->renda_outras_fontes) ? $socio->renda_outras_fontes : '0.00' ?>">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <label for="cadunico_nis" class="font-weight-bold text-dark">Possui NIS / CadÚnico?</label>
                                            <!-- 🔥 Alterado para SELECT -->
                                            <select class="form-control" id="cadunico_nis" name="cadunico_nis">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($socio->cadunico_nis) && $socio->cadunico_nis == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($socio->cadunico_nis) && $socio->cadunico_nis == 'Não') ? 'selected' : '' ?>>Não</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="numero_nis" class="font-weight-bold text-dark">Número NIS:</label>
                                            <input type="text" class="form-control" id="numero_nis" name="numero_nis" value="<?= isset($socio->numero_nis) ? htmlspecialchars($socio->numero_nis) : '' ?>">
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="recebe_beneficio_social" class="font-weight-bold text-dark">Recebe Auxílio Social?</label>
                                            <!-- 🔥 Alterado para SELECT -->
                                            <select class="form-control" id="recebe_beneficio_social" name="recebe_beneficio_social">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($socio->recebe_beneficio_social) && $socio->recebe_beneficio_social == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($socio->recebe_beneficio_social) && $socio->recebe_beneficio_social == 'Não') ? 'selected' : '' ?>>Não</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="paga_iptu" class="font-weight-bold text-dark">Paga IPTU?</label>
                                            <!-- 🔥 Alterado para SELECT -->
                                            <select class="form-control" id="paga_iptu" name="paga_iptu">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($socio->paga_iptu) && $socio->paga_iptu == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($socio->paga_iptu) && $socio->paga_iptu == 'Não') ? 'selected' : '' ?>>Não</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <label for="beneficios_detalhe" class="font-weight-bold text-dark">Detalhamento dos Benefícios:</label>
                                        <textarea class="form-control" id="beneficios_detalhe" name="beneficios_detalhe" rows="2"><?= isset($socio->beneficios_detalhe) ? htmlspecialchars($socio->beneficios_detalhe) : '' ?></textarea>
                                    </div>
                                </div>

                                <!-- Bloco 4: Relação da Posse Terreal -->
                                <div class="card card-body mb-3 shadow-sm border-left-warning">
                                    <h5 class="text-warning font-weight-bold mb-3">4. Posse Ocupacional Histórica</h5>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="reacao_com_imovel" class="font-weight-bold text-dark">Relação com Imóvel:</label>
                                            <!-- 🔥 Alterado para SELECT -->
                                            <select class="form-control" id="reacao_com_imovel" name="reacao_com_imovel">
                                                <option value="">-- Selecione --</option>
                                                <?php
                                                $relacoes = ["Próprio", "Alugado", "Cedido", "Posse/Ocupação"];
                                                foreach($relacoes as $r) {
                                                    $sel = (isset($socio->reacao_com_imovel) && $socio->reacao_com_imovel == $r) ? 'selected' : '';
                                                    echo "<option value='{$r}' {$sel}>{$r}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="forma_aquisicao" class="font-weight-bold text-dark">Forma de Aquisição:</label>
                                            <!-- 🔥 Alterado para SELECT -->
                                            <select class="form-control" id="forma_aquisicao" name="forma_aquisicao">
                                                <option value="">-- Selecione --</option>
                                                <?php
                                                $formas = ["Ocupação", "Compra (Contrato de Gaveta)", "Herança/Doação", "Cedido por Terceiros"];
                                                foreach($formas as $f) {
                                                    $sel = (isset($socio->forma_aquisicao) && $socio->forma_aquisicao == $f) ? 'selected' : '';
                                                    echo "<option value='{$f}' {$sel}>{$f}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="tempo_ocupacao" class="font-weight-bold text-dark">Tempo Ocupação:</label>
                                            <!-- 🔥 Alterado para SELECT -->
                                            <select class="form-control" id="tempo_ocupacao" name="tempo_ocupacao">
                                                <option value="">-- Selecione --</option>
                                                <?php
                                                $tempos = ["Menos de 1 ano", "1 a 2 anos", "2 a 5 anos", "5 anos ou mais"];
                                                foreach($tempos as $t) {
                                                    $sel = (isset($socio->tempo_ocupacao) && $socio->tempo_ocupacao == $t) ? 'selected' : '';
                                                    echo "<option value='{$t}' {$sel}>{$t}</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Bloco 5: Acervo Multimídia -->
                                <div class="card card-body mb-3 shadow-sm border-left-secondary">
                                    <h5 class="text-secondary font-weight-bold mb-3">5. Documentoscopia e Termos REURB</h5>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="foto_selo" class="font-weight-bold text-dark">Foto Selo:</label>
                                            <input type="text" class="form-control form-control-sm" id="foto_selo" name="foto_selo" value="<?= isset($socio->foto_selo) ? htmlspecialchars($socio->foto_selo) : '' ?>">
                                            <a href="arquivos/socio_juridico/<?= isset($socio->foto_selo) ? htmlspecialchars($socio->foto_selo) : '' ?>" target="_blank">
                                                <img src="arquivos/socio_juridico/<?= isset($socio->foto_selo) ? htmlspecialchars($socio->foto_selo) : '' ?>" alt="Foto Selo" class="img-fluid mt-2" style="max-height: 80px;">
                                            </a>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="r1_foto_rg" class="font-weight-bold text-dark">Foto RG:</label>
                                            <input type="text" class="form-control form-control-sm" id="r1_foto_rg" name="r1_foto_rg" value="<?= isset($socio->r1_foto_rg) ? htmlspecialchars($socio->r1_foto_rg) : '' ?>">
                                            <a href="arquivos/socio_juridico/<?= isset($socio->r1_foto_rg) ? htmlspecialchars($socio->r1_foto_rg) : '' ?>" target="_blank">
                                                <img src="arquivos/socio_juridico/<?= isset($socio->r1_foto_rg) ? htmlspecialchars($socio->r1_foto_rg) : '' ?>" alt="Foto RG R1" class="img-fluid mt-2" style="max-height: 80px;">
                                            </a>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="r1_foto_cpf" class="font-weight-bold text-dark">Foto CPF:</label>
                                            <input type="text" class="form-control form-control-sm" id="r1_foto_cpf" name="r1_foto_cpf" value="<?= isset($socio->r1_foto_cpf) ? htmlspecialchars($socio->r1_foto_cpf) : '' ?>">
                                            <a href="arquivos/socio_juridico/<?= isset($socio->r1_foto_cpf) ? htmlspecialchars($socio->r1_foto_cpf) : '' ?>" target="_blank">
                                                <img src="arquivos/socio_juridico/<?= isset($socio->r1_foto_cpf) ? htmlspecialchars($socio->r1_foto_cpf) : '' ?>" alt="Foto CPF R1" class="img-fluid mt-2" style="max-height: 80px;">
                                            </a>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="foto_comprovante_ocupacao_2022" class="font-weight-bold text-dark">Comprovante 2022:</label>
                                            <input type="text" class="form-control form-control-sm" id="foto_comprovante_ocupacao_2022" name="foto_comprovante_ocupacao_2022" value="<?= isset($socio->foto_comprovante_ocupacao_2022) ? htmlspecialchars($socio->foto_comprovante_ocupacao_2022) : '' ?>">
                                            <a href="arquivos/socio_juridico/<?= isset($socio->foto_comprovante_ocupacao_2022) ? htmlspecialchars($socio->foto_comprovante_ocupacao_2022) : '' ?>" target="_blank">
                                                <img src="arquivos/socio_juridico/<?= isset($socio->foto_comprovante_ocupacao_2022) ? htmlspecialchars($socio->foto_comprovante_ocupacao_2022) : '' ?>" alt="Comprovante 2022" class="img-fluid mt-2" style="max-height: 80px;">
                                            </a>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="foto_comprovante_ocupacao_2024" class="font-weight-bold text-dark">Comprovante 2024:</label>
                                            <input type="text" class="form-control form-control-sm" id="foto_comprovante_ocupacao_2024" name="foto_comprovante_ocupacao_2024" value="<?= isset($socio->foto_comprovante_ocupacao_2024) ? htmlspecialchars($socio->foto_comprovante_ocupacao_2024) : '' ?>">
                                            <a href="arquivos/socio_juridico/<?= isset($socio->foto_comprovante_ocupacao_2024) ? htmlspecialchars($socio->foto_comprovante_ocupacao_2024) : '' ?>" target="_blank">
                                                <img src="arquivos/socio_juridico/<?= isset($socio->foto_comprovante_ocupacao_2024) ? htmlspecialchars($socio->foto_comprovante_ocupacao_2024) : '' ?>" alt="Comprovante 2024" class="img-fluid mt-2" style="max-height: 80px;">
                                            </a>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="foto_comprovante_ocupacao_2026" class="font-weight-bold text-dark">Comprovante 2026:</label>
                                            <input type="text" class="form-control form-control-sm" id="foto_comprovante_ocupacao_2026" name="foto_comprovante_ocupacao_2026" value="<?= isset($socio->foto_comprovante_ocupacao_2026) ? htmlspecialchars($socio->foto_comprovante_ocupacao_2026) : '' ?>">
                                            <a href="arquivos/socio_juridico/<?= isset($socio->foto_comprovante_ocupacao_2026) ? htmlspecialchars($socio->foto_comprovante_ocupacao_2026) : '' ?>" target="_blank">
                                                <img src="arquivos/socio_juridico/<?= isset($socio->foto_comprovante_ocupacao_2026) ? htmlspecialchars($socio->foto_comprovante_ocupacao_2026) : '' ?>" alt="Comprovante 2026" class="img-fluid mt-2" style="max-height: 80px;">
                                            </a>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-3">
                                            <label for="assinou_unica_propriedade" class="font-weight-bold text-dark">Única Propriedade:</label>
                                            <!-- 🔥 Alterado para SELECT -->
                                            <select class="form-control form-control-sm" id="assinou_unica_propriedade" name="assinou_unica_propriedade">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($socio->assinou_unica_propriedade) && $socio->assinou_unica_propriedade == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($socio->assinou_unica_propriedade) && $socio->assinou_unica_propriedade == 'Não') ? 'selected' : '' ?>>Não</option>
                                                <option value="Recusou" <?= (isset($socio->assinou_unica_propriedade) && $socio->assinou_unica_propriedade == 'Recusou') ? 'selected' : '' ?>>Recusou</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="assinou_ocupacao_mansa_pacifica" class="font-weight-bold text-dark">Mansa/Pacífica:</label>
                                            <!-- 🔥 Alterado para SELECT -->
                                            <select class="form-control form-control-sm" id="assinou_ocupacao_mansa_pacifica" name="assinou_ocupacao_mansa_pacifica">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($socio->assinou_ocupacao_mansa_pacifica) && $socio->assinou_ocupacao_mansa_pacifica == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($socio->assinou_ocupacao_mansa_pacifica) && $socio->assinou_ocupacao_mansa_pacifica == 'Não') ? 'selected' : '' ?>>Não</option>
                                                <option value="Recusou" <?= (isset($socio->assinou_ocupacao_mansa_pacifica) && $socio->assinou_ocupacao_mansa_pacifica == 'Recusou') ? 'selected' : '' ?>>Recusou</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="assinou_veracidade" class="font-weight-bold text-dark">Veracidade:</label>
                                            <!-- 🔥 Alterado para SELECT -->
                                            <select class="form-control form-control-sm" id="assinou_veracidade" name="assinou_veracidade">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($socio->assinou_veracidade) && $socio->assinou_veracidade == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($socio->assinou_veracidade) && $socio->assinou_veracidade == 'Não') ? 'selected' : '' ?>>Não</option>
                                                <option value="Recusou" <?= (isset($socio->assinou_veracidade) && $socio->assinou_veracidade == 'Recusou') ? 'selected' : '' ?>>Recusou</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="assinou_lgpd" class="font-weight-bold text-dark">LGPD:</label>
                                            <!-- 🔥 Alterado para SELECT -->
                                            <select class="form-control form-control-sm" id="assinou_lgpd" name="assinou_lgpd">
                                                <option value="">-- Selecione --</option>
                                                <option value="Sim" <?= (isset($socio->assinou_lgpd) && $socio->assinou_lgpd == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                                <option value="Não" <?= (isset($socio->assinou_lgpd) && $socio->assinou_lgpd == 'Não') ? 'selected' : '' ?>>Não</option>
                                                <option value="Recusou" <?= (isset($socio->assinou_lgpd) && $socio->assinou_lgpd == 'Recusou') ? 'selected' : '' ?>>Recusou</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="form-group col-md-6">
                                            <label for="nome_cadastrador" class="font-weight-bold text-dark">Cadastrador:</label>
                                            <input type="text" class="form-control" id="nome_cadastrador" name="nome_cadastrador" value="<?= isset($socio->nome_cadastrador) ? htmlspecialchars($socio->nome_cadastrador) : '' ?>">
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="data_registro" class="font-weight-bold text-dark">Data Registro:</label>
                                            <input type="date" class="form-control" id="data_registro" name="data_registro" value="<?= isset($socio->data_registro) ? $socio->data_registro : '' ?>">
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