<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2604 - Ficha de Selagem de Domicílio - Teste Metodológico</title>
    <!-- Bootstrap 4.6 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .form-container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-top: 30px;
            margin-bottom: 30px;
        }
        .section-title {
            background-color: #e9ecef;
            padding: 10px 15px;
            border-left: 5px solid #007bff;
            margin-top: 25px;
            margin-bottom: 20px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .sub-section-title {
            color: #007bff;
            border-bottom: 2px solid #007bff;
            padding-bottom: 5px;
            margin-top: 20px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .form-group label {
            font-weight: 600;
        }
        .help-text {
            font-size: 0.85rem;
            color: #6c757d;
            font-style: italic;
        }
        .required::after {
            content: " *";
            color: red;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="form-container">
        <h2 class="text-center mb-4">2604 - FICHA DE SELAGEM DE DOMICÍLIO - TESTE METODOLÓGICO</h2>
        
        <form action="processa_ficha.php" method="POST" enctype="multipart/form-data">
            
            <!-- IDENTIFICAÇÃO DO LOTE -->
            <div class="section-title">Identificação do Lote</div>
            
            <div class="form-group">
                <label for="rua_imovel">Rua (zona/setor) do imóvel</label>
                <input type="text" class="form-control" id="rua_imovel" name="rua_imovel">
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="numero_lote">Número do Lote</label>
                        <input type="text" class="form-control" id="numero_lote" name="numero_lote">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="qtd_domicilios">Quantidade de domicílios no lote</label>
                        <input type="number" class="form-control" id="qtd_domicilios" name="qtd_domicilios">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="endereco_oficial">Endereço Oficial Completo</label>
                <textarea class="form-control" id="endereco_oficial" name="endereco_oficial" rows="2"></textarea>
            </div>

            <div class="form-group">
                <label class="d-block">Tipo de Ocupação do Lote</label>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="ocupacao_unifamiliar" name="tipo_ocupacao_lote" class="custom-control-input" value="Unifamiliar">
                    <label class="custom-control-label" for="ocupacao_unifamiliar">Unifamiliar</label>
                </div>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="ocupacao_multifamiliar" name="tipo_ocupacao_lote" class="custom-control-input" value="Multifamiliar">
                    <label class="custom-control-label" for="ocupacao_multifamiliar">Multifamiliar</label>
                </div>
            </div>

            <!-- INFORMAÇÕES POR DOMICÍLIO -->
            <div class="section-title">Informações por Domicílio</div>
            
            <div class="sub-section-title">» DADOS PRELIMINARES DO OCUPANTE</div>

            <div class="form-group">
                <label for="nome_entrevistado" class="required">Nome do entrevistado</label>
                <input type="text" class="form-control" id="nome_entrevistado" name="nome_entrevistado" required>
                <small class="help-text">Nome completo sem abreviações.</small>
            </div>

            <div class="form-group">
                <label for="nome_morador" class="required">Nome completo do principal morador/dono pelo imóvel</label>
                <input type="text" class="form-control" id="nome_morador" name="nome_morador" required>
                <small class="help-text">Nome completo sem abreviações.</small>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="telefone_responsavel" class="required">Telefone da pessoa responsável pelo imóvel</label>
                        <input type="text" class="form-control" id="telefone_responsavel" name="telefone_responsavel" placeholder="(00) 00000-0000" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="cpf_responsavel">CPF da pessoa responsável pelo imóvel (se disponível)</label>
                        <input type="text" class="form-control" id="cpf_responsavel" name="cpf_responsavel" placeholder="000.000.000-00">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="d-block required">Casado ou união estável?</label>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="casado_sim" name="casado_uniao" class="custom-control-input" value="Sim" required>
                    <label class="custom-control-label" for="casado_sim">Sim</label>
                </div>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="casado_nao" name="casado_uniao" class="custom-control-input" value="Não" required>
                    <label class="custom-control-label" for="casado_nao">Não</label>
                </div>
            </div>

            <div class="sub-section-title">» CARACTERÍSTICAS DO DOMICÍLIO/IMÓVEL</div>

            <div class="form-group">
                <label class="d-block required">Uso Predominante do Imóvel</label>
                <div class="custom-control custom-radio">
                    <input type="radio" id="uso_residencial" name="uso_predominante" class="custom-control-input" value="Exclusivamente Residencial" required>
                    <label class="custom-control-label" for="uso_residencial">Exclusivamente Residencial</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="uso_misto" name="uso_predominante" class="custom-control-input" value="Misto (residencial + pequeno comércio)">
                    <label class="custom-control-label" for="uso_misto">Misto (residencial + pequeno comércio)</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="uso_comercial" name="uso_predominante" class="custom-control-input" value="Comercial">
                    <label class="custom-control-label" for="uso_comercial">Comercial</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="uso_institucional" name="uso_predominante" class="custom-control-input" value="Institucional">
                    <label class="custom-control-label" for="uso_institucional">Institucional</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="uso_outro" name="uso_predominante" class="custom-control-input" value="Outro">
                    <label class="custom-control-label" for="uso_outro">Outro</label>
                </div>
            </div>

            <div class="form-group">
                <label class="d-block required">Tipo de Ocupação do Imóvel</label>
                <div class="custom-control custom-radio">
                    <input type="radio" id="tipo_consolidada_ocupada" name="tipo_ocupacao_imovel" class="custom-control-input" value="Edificação consolidada ocupada" required>
                    <label class="custom-control-label" for="tipo_consolidada_ocupada">Edificação consolidada ocupada</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="tipo_consolidada_nao_ocupada" name="tipo_ocupacao_imovel" class="custom-control-input" value="Edificação consolidada não ocupada">
                    <label class="custom-control-label" for="tipo_consolidada_nao_ocupada">Edificação consolidada não ocupada</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="tipo_construcao" name="tipo_ocupacao_imovel" class="custom-control-input" value="Edificação em construção">
                    <label class="custom-control-label" for="tipo_construcao">Edificação em construção</label>
                </div>
                <div class="custom-control custom-radio">
                    <input type="radio" id="tipo_lote_vazio" name="tipo_ocupacao_imovel" class="custom-control-input" value="Lote vazio">
                    <label class="custom-control-label" for="tipo_lote_vazio">Lote vazio</label>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="d-block required">Número de Pavimentos</label>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="pav_1" name="num_pavimentos" class="custom-control-input" value="1" required>
                            <label class="custom-control-label" for="pav_1">1</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="pav_2" name="num_pavimentos" class="custom-control-input" value="2">
                            <label class="custom-control-label" for="pav_2">2</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="pav_3" name="num_pavimentos" class="custom-control-input" value="3">
                            <label class="custom-control-label" for="pav_3">3</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="pav_4" name="num_pavimentos" class="custom-control-input" value="4">
                            <label class="custom-control-label" for="pav_4">4</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="d-block required">Localização do domicílio</label>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="loc_terreo" name="localizacao_domicilio" class="custom-control-input" value="Térreo" required>
                            <label class="custom-control-label" for="loc_terreo">Térreo</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="loc_1pav" name="localizacao_domicilio" class="custom-control-input" value="1º Pavimento">
                            <label class="custom-control-label" for="loc_1pav">1º Pav</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="loc_2pav" name="localizacao_domicilio" class="custom-control-input" value="2º Pavimento">
                            <label class="custom-control-label" for="loc_2pav">2º Pav</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="loc_3pav" name="localizacao_domicilio" class="custom-control-input" value="3º Pavimento">
                            <label class="custom-control-label" for="loc_3pav">3º Pav</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="d-block required">Acesso independente?</label>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="acesso_sim" name="acesso_independente" class="custom-control-input" value="Sim" required>
                            <label class="custom-control-label" for="acesso_sim">Sim</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="acesso_nao" name="acesso_independente" class="custom-control-input" value="Não" required>
                            <label class="custom-control-label" for="acesso_nao">Não</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="area_lote" class="required">Área do lote em metros quadrados</label>
                        <input type="number" step="0.01" class="form-control" id="area_lote" name="area_lote" required>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="d-block required">Comprovante de endereço?</label>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="comp_sim" name="comprovante_endereco" class="custom-control-input" value="Sim" required>
                    <label class="custom-control-label" for="comp_sim">Sim</label>
                </div>
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="comp_nao" name="comprovante_endereco" class="custom-control-input" value="Não" required>
                    <label class="custom-control-label" for="comp_nao">Não</label>
                </div>
            </div>

            <div class="form-group">
                <label for="foto_comprovante" class="required">Foto do comprovante de endereço</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" id="foto_comprovante" name="foto_comprovante" accept="image/*" required>
                    <label class="custom-file-label" for="foto_comprovante">Clique aqui para fazer o upload do arquivo. (<10MB)</label>
                </div>
            </div>

            <div class="sub-section-title">» DECLARAÇÕES</div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="d-block required">Declaração de Ciência e Adesão – REURB-S</label>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="decl_sim" name="decl_reurb" class="custom-control-input" value="Sim" required>
                            <label class="custom-control-label" for="decl_sim">Sim</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="decl_nao" name="decl_reurb" class="custom-control-input" value="Não" required>
                            <label class="custom-control-label" for="decl_nao">Não</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="foto_decl" class="required">Foto da Declaração de Ciência e Adesão</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="foto_decl" name="foto_decl" accept="image/*" required>
                            <label class="custom-file-label" for="foto_decl">Upload arquivo (10MB)</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label class="d-block required">Termo LGPD?</label>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="lgpd_sim" name="termo_lgpd" class="custom-control-input" value="Sim" required>
                            <label class="custom-control-label" for="lgpd_sim">Sim</label>
                        </div>
                        <div class="custom-control custom-radio custom-control-inline">
                            <input type="radio" id="lgpd_nao" name="termo_lgpd" class="custom-control-input" value="Não" required>
                            <label class="custom-control-label" for="lgpd_nao">Não</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="foto_lgpd" class="required">Foto do termo LGPD</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="foto_lgpd" name="foto_lgpd" accept="image/*" required>
                            <label class="custom-file-label" for="foto_lgpd">Upload arquivo (10MB)</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sub-section-title">» LOCALIZAÇÃO E IDENTIFICAÇÃO</div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="latitude" class="required">Latitude (x.y °)</label>
                        <input type="text" class="form-control" id="latitude" name="latitude" required>
                    </div>
                    <div class="form-group">
                        <label for="longitude" class="required">Longitude (x.y °)</label>
                        <input type="text" class="form-control" id="longitude" name="longitude" required>
                    </div>
                    <div class="form-group">
                        <label for="altitude">Altitude (m)</label>
                        <input type="text" class="form-control" id="altitude" name="altitude">
                    </div>
                    <div class="form-group">
                        <label for="precisao">Precisão (m)</label>
                        <input type="text" class="form-control" id="precisao" name="precisao">
                    </div>
                </div>
                <div class="col-md-6 text-center">
                    <div class="border rounded p-3 bg-light" style="height: 100%; min-height: 200px; display: flex; align-items: center; justify-content: center; flex-direction: column;">
                        <p class="text-muted mb-0">Visualização do Mapa</p>
                        <small>(Integração com API de Mapas aqui)</small>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="foto_fachada" class="required">Foto da Fachada do Imóvel</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="foto_fachada" name="foto_fachada" accept="image/*" required>
                            <label class="custom-file-label" for="foto_fachada">Upload arquivo (10MB)</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="foto_selo" class="required">Foto da Fachada com Selo Fixado</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="foto_selo" name="foto_selo" accept="image/*" required>
                            <label class="custom-file-label" for="foto_selo">Upload arquivo (10MB)</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="foto_ocupacao" class="required">Foto Comprovando Ocupação</label>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="foto_ocupacao" name="foto_ocupacao" accept="image/*" required>
                            <label class="custom-file-label" for="foto_ocupacao">Upload arquivo (10MB)</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="num_selo" class="required">Número do Selo Fixado</label>
                        <input type="text" class="form-control" id="num_selo" name="num_selo" placeholder="AAAA-0000 ou AAAA-000A" required>
                    </div>
                </div>
            </div>

            <!-- EQUIPE DE CAMPO -->
            <div class="section-title">Equipe de Campo</div>
            
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="selador_responsavel">Nome do(a) selador(a) responsável</label>
                        <input type="text" class="form-control" id="selador_responsavel" name="selador_responsavel">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="data_formulario">Data do formulário</label>
                        <input type="date" class="form-control" id="data_formulario" name="data_formulario">
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="text-center">
                <button type="submit" class="btn btn-primary btn-lg px-5">Enviar Formulário</button>
                <button type="reset" class="btn btn-secondary btn-lg px-5 ml-2">Limpar</button>
            </div>

        </form>
    </div>
</div>

<!-- Scripts: jQuery, Popper.js, and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.min.js"></script>

<script>
    // Script para atualizar o nome do arquivo no label do custom-file-input
    $(".custom-file-input").on("change", function() {
        var fileName = $(this).val().split("\\").pop();
        $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
    });
</script>

</body>
</html>