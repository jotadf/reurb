<?php

require_once('./verifica_login.php');
include_once('actions/ManterRelatorio.php');

$manterRelatorio = new ManterRelatorio();

// 🔥 ALTERADO: Agora captura o ID de submissão da Selagem Pai vindo da URL
$id_selagem = isset($_GET['id_selagem']) ? (int)$_GET['id_selagem'] : 0;

// 🔥 ALTERADO: Busca o domicílio vinculado a este lote pai específico
$domicilio = $manterRelatorio->getDomicilioPorSubmissaoPai($id_selagem);

// Busca todas as selagens de lotes para preencher o Select de vínculo pai
$listaLotes = $manterRelatorio->listarSelagem();

// Determina se o domicílio já existe no banco (Edição) ou se será uma nova inserção vinculada ao lote
$isEdit = !empty($domicilio->numero_selo);

// Se for um novo domicílio para este lote, pré-vincula o ID pai no objeto para o formulário já vir preenchido
if (!$isEdit) {
    echo "<script>alert('Não existe domicílio para este lote.'); window.location.href = 'selagens.php';</script>";
    exit();
}
?>
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

    <div id="wrapper">
        <?php include './menu.php'; ?>
        
        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <?php include './top_bar.php'; ?>

                <div class="container-fluid">
                    <div class="card mb-4 border-primary" style="max-width: 950px; margin: 0 auto;">
                        <div class="card-header py-3 bg-gradient-primary d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-white">
                                <i class="fa fa-home mr-2"></i> Cadastro de Domicílio por Lote Selado
                            </h6>
                            <a class="btn btn-outline-light btn-sm" href="#" onclick="history.back();">
                                <i class="fa fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                        
                        <div class="card-body">
                            <form action="save_domicilio.php" method="POST">
                                
                                <input type="hidden" name="is_edit" value="<?= $isEdit ? '1' : '0' ?>">

                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="numero_selo" class="font-weight-bold text-dark">Número do Selo:</label>
                                        <!-- 🔥 ALTERADO: Se for edição, fica em readonly. Se for novo domicílio mapeado no lote, fica aberto para digitação -->
                                        <input type="text" class="form-control font-weight-bold text-danger" id="numero_selo" name="numero_selo" value="<?= isset($domicilio->numero_selo) ? htmlspecialchars($domicilio->numero_selo) : '' ?>" <?= $isEdit ? 'readonly' : 'required' ?>>
                                    </div>

                                    <div class="form-group col-md-5">
                                        <label for="id_submissao_pai" class="font-weight-bold text-dark">Lote Vinculado (Selagem Pai):</label>
                                        <select class="form-control" id="id_submissao_pai" name="id_submissao_pai" required>
                                            <option value="">-- Selecione o Lote Territorial --</option>
                                            <?php foreach ($listaLotes as $lote) { 
                                                // Deixa o lote atual pré-selecionado automaticamente baseado no ID recebido da URL
                                                $selected = ($domicilio->id_submissao_pai == $lote->id_submissao) ? 'selected' : '';
                                                echo "<option value='{$lote->id_submissao}' {$selected}>Lote Nº {$lote->numero_lote} - {$lote->rua_zona_setor} (ID: {$lote->id_submissao})</option>";
                                            } ?>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-3">
                                        <label for="index_kobo" class="font-weight-bold text-dark">Index Interno Kobo:</label>
                                        <input type="number" class="form-control" id="index_kobo" name="index_kobo" value="<?= isset($domicilio->index_kobo) ? $domicilio->index_kobo : '' ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="nome_entrevistado" class="font-weight-bold text-dark">Nome do Entrevistado:</label>
                                        <input type="text" class="form-control" id="nome_entrevistado" name="nome_entrevistado" value="<?= isset($domicilio->nome_entrevistado) ? htmlspecialchars($domicilio->nome_entrevistado) : '' ?>">
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label for="nome_principal_morador" class="font-weight-bold text-dark">Nome do Principal Morador:</label>
                                        <input type="text" class="form-control" id="nome_principal_morador" name="nome_principal_morador" value="<?= isset($domicilio->nome_principal_morador) ? htmlspecialchars($domicilio->nome_principal_morador) : '' ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="cpf" class="font-weight-bold text-dark">CPF do Morador Principal:</label>
                                        <input type="text" class="form-control" id="cpf" name="cpf" placeholder="Apenas números" value="<?= isset($domicilio->cpf) ? htmlspecialchars($domicilio->cpf) : '' ?>">
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="telefone" class="font-weight-bold text-dark">Telefone de Contato:</label>
                                        <input type="text" class="form-control" id="telefone" name="telefone" value="<?= isset($domicilio->telefone) ? htmlspecialchars($domicilio->telefone) : '' ?>">
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="casado_uniao_estavel" class="font-weight-bold text-dark">Casado ou União Estável?</label>
                                        <select class="form-control" id="casado_uniao_estavel" name="casado_uniao_estavel">
                                            <option value="">-- Selecione --</option>
                                            <option value="Sim" <?= (isset($domicilio->casado_uniao_estavel) && $domicilio->casado_uniao_estavel == 'Sim') ? 'selected' : '' ?>>Sim</option>
                                            <option value="Não" <?= (isset($domicilio->casado_uniao_estavel) && $domicilio->casado_uniao_estavel == 'Não') ? 'selected' : '' ?>>Não</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="uso_predominante" class="font-weight-bold text-dark">Uso Predominante:</label>
                                        <input type="text" class="form-control" id="uso_predominante" name="uso_predominante" value="<?= isset($domicilio->uso_predominante) ? htmlspecialchars($domicilio->uso_predominante) : '' ?>">
                                    </div>

                                    <div class="form-group col-md-5">
                                        <label for="tipo_ocupacao_imovel" class="font-weight-bold text-dark">Tipo de Ocupação do Imóvel:</label>
                                        <input type="text" class="form-control" id="tipo_ocupacao_imovel" name="tipo_ocupacao_imovel" value="<?= isset($domicilio->tipo_ocupacao_imovel) ? htmlspecialchars($domicilio->tipo_ocupacao_imovel) : '' ?>">
                                    </div>

                                    <div class="form-group col-md-3">
                                        <label for="numero_pavimentos" class="font-weight-bold text-dark">Nº Pavimentos:</label>
                                        <input type="number" class="form-control" id="numero_pavimentos" name="numero_pavimentos" value="<?= isset($domicilio->numero_pavimentos) ? $domicilio->numero_pavimentos : 1 ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-4">
                                        <label for="localizacao_domicilio" class="font-weight-bold text-dark">Localização:</label>
                                        <input type="text" class="form-control" id="localizacao_domicilio" name="localizacao_domicilio" value="<?= isset($domicilio->localizacao_domicilio) ? htmlspecialchars($domicilio->localizacao_domicilio) : '' ?>">
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="acesso_independente" class="font-weight-bold text-dark">Acesso Independente?</label>
                                        <input type="text" class="form-control" id="acesso_independente" name="acesso_independente" value="<?= isset($domicilio->acesso_independente) ? htmlspecialchars($domicilio->acesso_independente) : '' ?>">
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="area_lote_m2" class="font-weight-bold text-dark">Área Estimada Lote (m²):</label>
                                        <input type="text" class="form-control" id="area_lote_m2" name="area_lote_m2" value="<?= isset($domicilio->area_lote_m2) ? htmlspecialchars($domicilio->area_lote_m2) : '' ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="foto_fachada" class="font-weight-bold text-dark">Caminho/URL Foto Fachada:</label>
                                        <input type="text" class="form-control" id="foto_fachada" name="foto_fachada" value="<?= isset($domicilio->foto_fachada) ? htmlspecialchars($domicilio->foto_fachada) : '' ?>">
                                        <a href="arquivos/domicilio/<?= isset($domicilio->foto_fachada) ? htmlspecialchars($domicilio->foto_fachada) : '' ?>" target="_blank">
                                                <img src="arquivos/domicilio/<?= isset($domicilio->foto_fachada) ? htmlspecialchars($domicilio->foto_fachada) : '' ?>" alt="Foto Fachada" class="img-fluid mt-2" style="max-height: 80px;">
                                            </a>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="foto_selo" class="font-weight-bold text-dark">Caminho/URL Foto Selo Fixado:</label>
                                        <input type="text" class="form-control" id="foto_selo" name="foto_selo" value="<?= isset($domicilio->foto_selo) ? htmlspecialchars($domicilio->foto_selo) : '' ?>">
                                        <a href="arquivos/domicilio/<?= isset($domicilio->foto_selo) ? htmlspecialchars($domicilio->foto_selo) : '' ?>" target="_blank">
                                                <img src="arquivos/domicilio/<?= isset($domicilio->foto_selo) ? htmlspecialchars($domicilio->foto_selo) : '' ?>" alt="Foto Selo" class="img-fluid mt-2" style="max-height: 80px;">
                                            </a>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-6">
                                        <label for="foto_comprovante_endereco" class="font-weight-bold text-dark">Caminho/URL Foto Comprovante de Endereço:</label>
                                        <input type="text" class="form-control" id="foto_comprovante_endereco" name="foto_comprovante_endereco" value="<?= isset($domicilio->foto_comprovante_endereco) ? htmlspecialchars($domicilio->foto_comprovante_endereco) : '' ?>">
                                        <a href="arquivos/domicilio/<?= isset($domicilio->foto_comprovante_endereco) ? htmlspecialchars($domicilio->foto_comprovante_endereco) : '' ?>" target="_blank">
                                                <img src="arquivos/domicilio/<?= isset($domicilio->foto_comprovante_endereco) ? htmlspecialchars($domicilio->foto_comprovante_endereco) : '' ?>" alt="Foto Comprovante de Endereço" class="img-fluid mt-2" style="max-height: 80px;">
                                            </a>
                                    </div>
                                    <div class="form-group col-md-6">
                                        <label for="foto_ocupacao" class="font-weight-bold text-dark">Caminho/URL Foto de Ocupação:</label>
                                        <input type="text" class="form-control" id="foto_ocupacao" name="foto_ocupacao" value="<?= isset($domicilio->foto_ocupacao) ? htmlspecialchars($domicilio->foto_ocupacao) : '' ?>">
                                        <a href="arquivos/domicilio/<?= isset($domicilio->foto_ocupacao) ? htmlspecialchars($domicilio->foto_ocupacao) : '' ?>" target="_blank">
                                                <img src="arquivos/domicilio/<?= isset($domicilio->foto_ocupacao) ? htmlspecialchars($domicilio->foto_ocupacao) : '' ?>" alt="Foto de Ocupação" class="img-fluid mt-2" style="max-height: 80px;">
                                            </a>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="form-group col-md-3">
                                        <label for="latitude" class="font-weight-bold text-dark">Latitude:</label>
                                        <input type="text" class="form-control" id="latitude" name="latitude" value="<?= isset($domicilio->latitude) ? $domicilio->latitude : '' ?>">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="longitude" class="font-weight-bold text-dark">Longitude:</label>
                                        <input type="text" class="form-control" id="longitude" name="longitude" value="<?= isset($domicilio->longitude) ? $domicilio->longitude : '' ?>">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="altitude" class="font-weight-bold text-dark">Altitude:</label>
                                        <input type="text" class="form-control" id="altitude" name="altitude" value="<?= isset($domicilio->altitude) ? $domicilio->altitude : '' ?>">
                                    </div>
                                    <div class="form-group col-md-3">
                                        <label for="precisao" class="font-weight-bold text-dark">Precisão do GPS:</label>
                                        <input type="text" class="form-control" id="precisao" name="precisao" value="<?= isset($domicilio->precisao) ? $domicilio->precisao : '' ?>">
                                    </div>
                                </div>

                                <hr class="border-secondary">
                                <div class="text-right">
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