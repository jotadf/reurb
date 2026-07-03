<?php
require_once('./verifica_login.php');
include_once('actions/ManterRelatorio.php'); // Ou a classe onde você inseriu o método getSelagemPorId

$manterRelatorio = new ManterRelatorio();

// Captura o ID da submissão enviado via URL (ex: form_editar_selagem.php?id=735593428)
$id_submissao = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Busca o objeto com todos os campos da tabela 'selagem_lotes'
$lote = $manterRelatorio->getSelagemPorId($id_submissao);

// Se o usuário passou um ID mas ele não foi encontrado no banco, redireciona ou exibe aviso
if ($id_submissao > 0 && !isset($lote->id_submissao)) {
    echo "<script>alert('Lote não encontrado!'); window.location.href='gerenciar_arquivos_importacao.php';</script>";
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Formulário de Selagem de Lote</title>

    <!-- Custom styles para o template SB Admin 2 / Bootstrap 4 -->
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
                    <!-- Card do Formulário -->
                    <div class="card mb-4 border-primary" style="max-width: 900px; margin: 0 auto;">
                        <div class="card-header py-3 bg-gradient-primary d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-white">
                                <i class="fa fa-edit mr-2"></i> Ficha de Selagem de Lote - Ocupação Dorothy Stang
                            </h6>
                            <a class="btn btn-outline-light btn-sm" href="selagens.php">
                                <i class="fa fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                        
                        <div class="card-body">
                            <!-- O formulário envia para a sua action de salvar -->
                            <form action="save_selagem.php" method="POST">
                                
                                <!-- Campo Oculto para manter a Chave Primária (Fundamental para o UPDATE) -->
                                <input type="hidden" name="id_submissao" value="<?= isset($lote->id_submissao) ? $lote->id_submissao : '' ?>">
                                
                                <div class="row">
                                    <!-- Identificadores KoboToolbox -->
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold text-dark">ID da Submissão (KoboToolbox):</label>
                                        <input type="text" class="form-control bg-light" value="<?= isset($lote->id_submissao) ? $lote->id_submissao : 'Novo Registro' ?>" readonly>
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label for="uuid" class="font-weight-bold text-dark">UUID do Registro:</label>
                                        <input type="text" class="form-control" id="uuid" name="uuid" value="<?= isset($lote->uuid) ? htmlspecialchars($lote->uuid) : '' ?>" required>
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Dados Territoriais Físicos -->
                                    <div class="form-group col-md-8">
                                        <label for="rua_zona_setor" class="font-weight-bold text-dark">Rua / Zona / Setor:</label>
                                        <input type="text" class="form-control" id="rua_zona_setor" name="rua_zona_setor" value="<?= isset($lote->rua_zona_setor) ? htmlspecialchars($lote->rua_zona_setor) : '' ?>">
                                    </div>

                                    <div class="form-group col-md-4">
                                        <label for="numero_lote" class="font-weight-bold text-dark">Número do Lote:</label>
                                        <input type="text" class="form-control" id="numero_lote" name="numero_lote" value="<?= isset($lote->numero_lote) ? htmlspecialchars($lote->numero_lote) : '' ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="endereco_oficial_completo" class="font-weight-bold text-dark">Endereço Oficial Completo:</label>
                                    <textarea class="form-control" id="endereco_oficial_completo" name="endereco_oficial_completo" rows="2"><?= isset($lote->endereco_oficial_completo) ? htmlspecialchars($lote->endereco_oficial_completo) : '' ?></textarea>
                                </div>

                                <div class="row">
                                    <!-- Classificações Operacionais -->
                                    <div class="form-group col-md-6">
                                        <label for="tipo_ocupacao_lote" class="font-weight-bold text-dark">Tipo de Ocupação do Lote:</label>
                                        <select class="form-control" id="tipo_ocupacao_lote" name="tipo_ocupacao_lote">
                                            <option value="">-- Selecione --</option>
                                            <option value="Unifamiliar" <?= (isset($lote->tipo_ocupacao_lote) && $lote->tipo_ocupacao_lote == 'Unifamiliar') ? 'selected' : '' ?>>Unifamiliar</option>
                                            <option value="Multifamiliar" <?= (isset($lote->tipo_ocupacao_lote) && $lote->tipo_ocupacao_lote == 'Multifamiliar') ? 'selected' : '' ?>>Multifamiliar</option>
                                            <option value="Comercial" <?= (isset($lote->tipo_ocupacao_lote) && $lote->tipo_ocupacao_lote == 'Comercial') ? 'selected' : '' ?>>Comercial</option>
                                            <option value="Misto" <?= (isset($lote->tipo_ocupacao_lote) && $lote->tipo_ocupacao_lote == 'Misto') ? 'selected' : '' ?>>Misto / Residencial e Comercial</option>
                                        </select>
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label for="qtd_domicilios_total" class="font-weight-bold text-dark">Quantidade de Domicílios no Lote:</label>
                                        <input type="number" class="form-control" id="qtd_domicilios_total" name="qtd_domicilios_total" value="<?= isset($lote->qtd_domicilios_total) ? (int)$lote->qtd_domicilios_total : 1 ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Dados da Coleta de Campo -->
                                    <div class="form-group col-md-6">
                                        <label for="nome_selador" class="font-weight-bold text-dark">Nome do Selador:</label>
                                        <input type="text" class="form-control" id="nome_selador" name="nome_selador" value="<?= isset($lote->nome_selador) ? htmlspecialchars($lote->nome_selador) : '' ?>">
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label for="data_formulario" class="font-weight-bold text-dark">Data de Coleta do Formulário:</label>
                                        <input type="date" class="form-control" id="data_formulario" name="data_formulario" value="<?= isset($lote->data_formulario) ? $lote->data_formulario : '' ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <!-- Metadados de Sistema -->
                                    <div class="form-group col-md-6">
                                        <label class="font-weight-bold text-dark">Data/Hora de Envio (Sincronismo):</label>
                                        <input type="text" class="form-control bg-light" value="<?= isset($lote->data_hora_submissao) ? $lote->data_hora_submissao : '-' ?>" readonly>
                                    </div>

                                    <div class="form-group col-md-6">
                                        <label for="versao" class="font-weight-bold text-dark">Versão do Formulário Kobo:</label>
                                        <input type="text" class="form-control" id="versao" name="versao" value="<?= isset($lote->versao) ? htmlspecialchars($lote->versao) : '' ?>">
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

    <!-- Scripts padrão do Bootstrap -->
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>