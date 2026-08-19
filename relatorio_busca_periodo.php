<?php

require_once('./verifica_login.php');
include_once('actions/ManterRelatorio.php');

$manterRelatorio = new ManterRelatorio();

$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'selagem';

$inicio = isset($_POST['inicio']) ? $_POST['inicio'] : '';
$termino = isset($_POST['termino']) ? $_POST['termino'] : '';

$where = '';
$lista = array();

switch ($tipo) {
    case 'selagem':
        $where = " WHERE data_formulario >= '{$inicio}' AND data_formulario <= '{$termino}'";
        $lista = $manterRelatorio->listarSelagem($where);
        break;
    case 'domicilio':
        $where = " WHERE id_submissao_pai IN (SELECT id_submissao FROM selagem_lotes WHERE data_formulario >= '{$inicio}' AND data_formulario <= '{$termino}')";
        $lista = $manterRelatorio->listarDomicilios($where);
        break;
    case 'selagemdomicilio':
        $where = " WHERE d.id_submissao_pai = s.id_submissao AND s.data_formulario >= '{$inicio}' AND s.data_formulario <= '{$termino}'";
        $lista = $manterRelatorio->listarSelagemDomicilios($where);
        break;
    case 'socio_juridico':
        $where = " WHERE data_registro >= '{$inicio}' AND data_registro <= '{$termino}'";
        $lista = $manterRelatorio->listarSociojuridico($where);
        break;
    case 'caracterizacao':
        $where = " WHERE data_registro >= '{$inicio}' AND data_registro <= '{$termino}'";
        $lista = $manterRelatorio->listarCaracterizacao($where);
        break;
}

$total = array_merge($lista);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relatório Detalhado por Período</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="css/sb-admin-2.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.20/css/dataTables.bootstrap4.min.css">
    
    <script src="https://code.jquery.com/jquery-3.3.1.js"></script>
    <script src="js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.20/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/gh/linways/table-to-excel@v1.0.4/dist/tableToExcel.js"></script>

    <script type="text/javascript">
        $(document).ready(function () {
            $("#btnExport").click(function () {
                let table = $("#registros");
                TableToExcel.convert(table[0], {
                    name: 'relatorio_completo_<?= $tipo ?>.xlsx',
                    sheet: { name: 'Dados Exportados' }
                });
            });
        });
    </script>
    <style>
        body { font-size: 11px; }
        .table th { font-size: 11px; padding: 8px; vertical-align: middle !important; }
        .table td { font-size: 11px; padding: 6px; vertical-align: middle !important; }
    </style>
</head>
<body id="page-top">

    <div class="container-fluid mt-3">
        <?php if (count($total) > 0) { ?>
            <div role="main" style="width:100%">
                <h3 class="text-center mb-3">Relatório Consolidado - <?= strtoupper(str_replace('_', ' ', $tipo)) ?></h3>
                <div class="text-right mb-2">
                    <img src="img/iconexcel.png" width="28" height="28" style="cursor:pointer;" class="d-print-none" id="btnExport" title="Exportar para Excel" />
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover" id="registros">
                        <thead class="thead-dark text-nowrap text-center">
                            <tr>
                                <?php if ($tipo == 'selagem') { ?>
                                    <th>ID SUBMISSÃO</th><th>UUID</th><th>RUA/SETOR</th><th>Nº LOTE</th><th>ENDEREÇO COMPLETO</th><th>TIPO OCUPAÇÃO</th><th>QTD DOMICÍLIOS TOTAL</th><th>SELADOR</th><th>DATA FORMULÁRIO</th><th>DATA/HORA SUBMISSÃO</th><th>VERSÃO</th>
                                
                                <?php } elseif ($tipo == 'domicilio') { ?>
                                    <th>Nº SELO</th><th>ID SUBMISSÃO PAI</th><th>INDEX KOBO</th><th>NOME ENTREVISTADO</th><th>PRINCIPAL MORADOR</th><th>TELEFONE</th><th>CPF</th><th>ESTADO CIVIL</th><th>USO PREDOMINANTE</th><th>TIPO OCUPAÇÃO</th><th>Nº PAVIMENTOS</th><th>LOCALIZAÇÃO</th><th>ACESSO INDEP.</th><th>ÁREA LOTE (M²)</th><th>COMPROV_END</th><th>FOTO COMPROV_END</th><th>FOTO FACHADA</th><th>FOTO SELO</th><th>FOTO OCUPAÇÃO</th><th>LATITUDE</th><th>LONGITUDE</th><th>ALTITUDE</th><th>PRECISÃO</th><th>GEOLOCALIZAÇÃO</th>

                                <?php } elseif ($tipo == 'selagemdomicilio') { ?>
                                <th>ID SUBMISSÃO</th><th>UUID</th><th>RUA/SETOR</th><th>Nº LOTE</th><th>ENDEREÇO COMPLETO</th><th>TIPO OCUPAÇÃO</th><th>QTD DOMICÍLIOS TOTAL</th><th>SELADOR</th><th>DATA FORMULÁRIO</th><th>DATA/HORA SUBMISSÃO</th>
                                <th>Nº SELO</th><th>ID SUBMISSÃO PAI</th><th>INDEX KOBO</th><th>NOME ENTREVISTADO</th><th>PRINCIPAL MORADOR</th><th>TELEFONE</th><th>CPF</th><th>ESTADO CIVIL</th><th>USO PREDOMINANTE</th><th>TIPO OCUPAÇÃO</th><th>Nº PAVIMENTOS</th><th>LOCALIZAÇÃO</th><th>ACESSO INDEP.</th><th>ÁREA LOTE (M²)</th><th>COMPROV_END</th><th>FOTO COMPROV_END</th><th>FOTO FACHADA</th><th>FOTO SELO</th><th>FOTO OCUPAÇÃO</th><th>LATITUDE</th><th>LONGITUDE</ th>< th>ALTITUDE</ th>< th>PRECISÃO</ th>< th>GEOLOCALIZAÇÃO</ th>

                                <?php } elseif ($tipo == 'socio_juridico') { ?>
                                    <th>ID SUBMISSÃO</th><th>UUID</th><th>CÓDIGO SELO</th><th>FOTO SELO</th><th>R1 NOME</th><th>R1 RG</th><th>R1 FOTO RG</th><th>r1_cpf</th><th>R1 FOTO CPF</th><th>R1 NATURALIDADE</th><th>R1 DATA NASC.</th><th>R1 ESTADO CIVIL</th><th>R1 PROFISSÃO</th><th>R1 ESCOLARIDADE</th><th>R1 PCD</th><th>R1 ESPECIF. PCD</th><th>R1 TELEFONE</th><th>Nº RESIDENTES</th><th>RENDA TITULAR 1</th><th>RENDA TITULAR 2</th><th>RENDA OUTRAS FONTES</th><th>CADÚNICO NIS</th><th>NÚMERO NIS</th><th>RECEBE BENEFÍCIO</th><th>BENEFÍCIOS DETALHE</th><th>RELAÇÃO IMÓVEL</th><th>FORMA AQUISIÇÃO</th><th>TEMPO OCUPAÇÃO</th><th>FOTO OCUPAÇÃO 2022</th><th>FOTO OCUPAÇÃO 2023</th><th>FOTO OCUPAÇÃO 2024</th><th>FOTO OCUPAÇÃO 2025</th><th>FOTO OCUPAÇÃO 2026</th><th>PAGA IPTU</th><th>ASSINOU ÚNICA PROP.</th><th>FOTO ÚNICA PROP.</th><th>ASSINOU MANSA/PACÍF.</th><th>FOTO MANSA/PACÍF.</th><th>ASSINOU VERACIDADE</th><th>FOTO VERACIDADE</th><th>ASSINOU LGPD</th><th>FOTO LGPD</th><th>CADASTRADOR</th><th>DATA REGISTRO</th>
                                
                                <?php } elseif ($tipo == 'caracterizacao') { ?>
                                    <th>ID SUBMISSÃO</th><th>UUID</th><th>CÓDIGO SELO</th><th>FOTO SELO</th><th>QTD CÔMODOS</th><th>CÔMODOS IMPROV.</th><th>MAIS 3 POR DORM.</th><th>FALTAM CAMAS</th><th>POSSUI BANHEIRO</th><th>Nº BANHEIROS</th><th>REVEST. BANHEIRO</th><th>LOUÇA BANHEIRO</th><th>PROBL. ESTRUTURAIS</th><th>PROBL. INFILTRAÇÃO</th><th>CÔMODO MOFO</th><th>CÔMODOS C/ MOFO</th><th>PAREDES MATERIAIS</th><th>PAREDES CONDIÇÃO</th><th>COBERTURA MATERIAIS</th><th>COBERTURA CONDIÇÃO</th><th>PISO MATERIAIS</th><th>PISO CONDIÇÃO</th><th>ENERGIA ACESSO</th><th>ENERGIA CONDIÇÃO</th><th>INSTAL. INTERNAS</th><th>ÁGUA ACESSO</th><th>MATERIAIS SANITÁRIOS</th><th>INSTAL. HIDROS. DISP.</th><th>CONDIÇÕES HIDROS.</th><th>ESGOTO</th><th>COLETA LIXO</th><th>FREQ. COLETA LIXO</th><th>EXISTE DRENAGEM</th><th>PAVIMENTAÇÃO</th><th>PCD DOMICÍLIO</th><th>QTD PCD</th><th>MOBILIDADE REDUZIDA</th><th>CADEIRANTES</th><th>DOENÇAS RESP.</th><th>QTD AFETADOS RESP.</th><th>HOUVE ACIDENTES</th><th>OCORRÊNCIA INUNDAÇÃO</th><th>FREQ. INUNDAÇÃO</th><th>ALTURA ÁGUA</th><th>QUANDO CHOVE</th><th>SENSACÃO SEGURANÇA</th><th>ILUMINAÇÃO PÚBLICA</th><th>EQUIP. LAZER</th><th>EQUIP. SAÚDE</th><th>TRANSPORTE PÚBLICO</th><th>MEIO LOCOMOÇÃO</th><th>HA ESCADA</th><th>CÔMODOS S/ JANELA</th><th>CALÇADA NA RUA</th><th>TERRENO RACHADURAS</th><th>PAREDES EMBARRIGADAS</th><th>POSTES INCLINADOS</th><th>HOUVE DESLIZAMENTO</th><th>ONDE DESLIZAMENTO</th><th>QUANDO DESLIZAMENTO</th><th>RESPONSÁVEL COLETA</th><th>DATA REGISTRO</th>
                                <?php } ?>
                            </tr>
                        </thead>
                        <tbody class="text-nowrap">
                            <?php foreach ($total as $obj) { ?>
                                <tr>
                                    <?php if ($tipo == 'selagem') { ?>
                                        <td class="text-center font-weight-bold"><?= $obj->id_submissao ?></td>
                                        <td><?= htmlspecialchars($obj->uuid) ?></td>
                                        <td><?= htmlspecialchars($obj->rua_zona_setor) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->numero_lote) ?></td>
                                        <td><?= htmlspecialchars($obj->endereco_oficial_completo) ?></td>
                                        <td><?= htmlspecialchars($obj->tipo_ocupacao_lote) ?></td>
                                        <td class="text-center"><?= $obj->qtd_domicilios_total ?></td>
                                        <td><?= htmlspecialchars($obj->nome_selador) ?></td>
                                        <td class="text-center"><?= $obj->data_formulario ?></td>
                                        <td class="text-center"><?= $obj->data_hora_submissao ?></td>
                                        <td><?= htmlspecialchars($obj->versao) ?></td>
                                    
                                    <?php } elseif ($tipo == 'domicilio') { ?>
                                        <td class="font-weight-bold text-center bg-light"><?= $obj->numero_selo ?></td>
                                        <td class="text-center"><?= $obj->id_submissao_pai ?></td>
                                        <td class="text-center"><?= $obj->index_kobo ?></td>
                                        <td><?= htmlspecialchars($obj->nome_entrevistado) ?></td>
                                        <td><?= htmlspecialchars($obj->nome_principal_morador) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->telefone) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->cpf) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->casado_uniao_estavel) ?></td>
                                        <td><?= htmlspecialchars($obj->uso_predominante) ?></td>
                                        <td><?= htmlspecialchars($obj->tipo_ocupacao_imovel) ?></td>
                                        <td class="text-center"><?= $obj->numero_pavimentos ?></td>
                                        <td><?= htmlspecialchars($obj->localizacao_domicilio) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->acesso_independente) ?></td>
                                        <td class="text-right"><?= number_format($obj->area_lote_m2, 2, ',', '.') ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->comprovante_endereco) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_comprovante_endereco) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_fachada) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_selo) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_ocupacao) ?></td>
                                        <td class="text-right"><?= $obj->latitude ?></td>
                                        <td class="text-right"><?= $obj->longitude ?></td>
                                        <td class="text-right"><?= $obj->altitude ?></td>
                                        <td class="text-right"><?= $obj->precisao ?></td>
                                        <td class="text-right"><?= $obj->latitude . " " . $obj->longitude . " " . $obj->altitude . " " . $obj->precisao ?></td>

                                    <?php if ($tipo == 'selagemdomicilio') { ?>
                                        <td class="text-center font-weight-bold"><?= $obj->id_submissao ?></td>
                                        <td><?= htmlspecialchars($obj->uuid) ?></td>
                                        <td><?= htmlspecialchars($obj->rua_zona_setor) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->numero_lote) ?></td>
                                        <td><?= htmlspecialchars($obj->endereco_oficial_completo) ?></td>
                                        <td><?= htmlspecialchars($obj->tipo_ocupacao_lote) ?></td>
                                        <td class="text-center"><?= $obj->qtd_domicilios_total ?></td>
                                        <td><?= htmlspecialchars($obj->nome_selador) ?></td>
                                        <td class="text-center"><?= $obj->data_formulario ?></td>
                                        <td class="text-center"><?= $obj->data_hora_submissao ?></td>
                                        <!-- Additional fields from domicilios_import -->                                        
                                        <td class="font-weight-bold text-center bg-light"><?= $obj->numero_selo ?></td>
                                        <td class="text-center"><?= $obj->id_submissao_pai ?></td>
                                        <td class="text-center"><?= $obj->index_kobo ?></td>
                                        <td><?= htmlspecialchars($obj->nome_entrevistado) ?></td>
                                        <td><?= htmlspecialchars($obj->nome_principal_morador) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->telefone) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->cpf) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->casado_uniao_estavel) ?></td>
                                        <td><?= htmlspecialchars($obj->uso_predominante) ?></td>
                                        <td><?= htmlspecialchars($obj->tipo_ocupacao_imovel) ?></td>
                                        <td class="text-center"><?= $obj->numero_pavimentos ?></td>
                                        <td><?= htmlspecialchars($obj->localizacao_domicilio) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->acesso_independente) ?></td>
                                        <td class="text-right"><?= number_format($obj->area_lote_m2, 2, ',', '.') ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->comprovante_endereco) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_comprovante_endereco) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_fachada) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_selo) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_ocupacao) ?></td>
                                        <td class="text-right"><?= $obj->latitude ?></td>
                                        <td class="text-right"><?= $obj->longitude ?></td>
                                        <td class="text-right"><?= $obj->altitude ?></td>
                                        <td class="text-right"><?= $obj->precisao ?></td>
                                        <td class="text-right"><?= $obj->latitude . " " . $obj->longitude . " " . $obj->altitude . " " . $obj->precisao ?></td>

                                    <?php } elseif ($tipo == 'socio_juridico') { ?>
                                        <td class="text-center font-weight-bold"><?= $obj->id_submissao ?></td>
                                        <td><?= htmlspecialchars($obj->uuid) ?></td>
                                        <td class="font-weight-bold text-center bg-light"><?= $obj->codigo_selo ?></td>
                                        <td><?= htmlspecialchars($obj->foto_selo) ?></td>
                                        <td><?= htmlspecialchars($obj->r1_nome) ?></td>
                                        <td><?= htmlspecialchars($obj->r1_rg) ?></td>
                                        <td><?= htmlspecialchars($obj->r1_foto_rg) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->r1_cpf) ?></td>
                                        <td><?= htmlspecialchars($obj->r1_foto_cpf) ?></td>
                                        <td><?= htmlspecialchars($obj->r1_naturalidade) ?></td>
                                        <td class="text-center"><?= $obj->r1_data_nascimento ?></td>
                                        <td><?= htmlspecialchars($obj->r1_estado_civil) ?></td>
                                        <td><?= htmlspecialchars($obj->r1_profissao) ?></td>
                                        <td><?= htmlspecialchars($obj->r1_escolaridade) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->r1_pcd) ?></td>
                                        <td><?= htmlspecialchars($obj->r1_especifiacao_pcd) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->r1_telefone) ?></td>
                                        <td class="text-center"><?= $obj->numero_residentes ?></td>
                                        <td class="text-right"><?= number_format($obj->renda_mensal_titular_1, 2, ',', '.') ?></td>
                                        <td class="text-right"><?= number_format($obj->renda_mensal_titular_2, 2, ',', '.') ?></td>
                                        <td class="text-right"><?= number_format($obj->renda_outras_fontes, 2, ',', '.') ?></td>
                                        <td><?= htmlspecialchars($obj->cadunico_nis) ?></td>
                                        <td><?= htmlspecialchars($obj->numero_nis) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->recebe_beneficio_social) ?></td>
                                        <td><?= htmlspecialchars($obj->beneficios_detalhe) ?></td>
                                        <td><?= htmlspecialchars($obj->reacao_com_imovel) ?></td>
                                        <td><?= htmlspecialchars($obj->forma_aquisicao) ?></td>
                                        <td><?= htmlspecialchars($obj->tempo_ocupacao) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_comprovante_ocupacao_2022) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_comprovante_ocupacao_2023) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_comprovante_ocupacao_2024) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_comprovante_ocupacao_2025) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_comprovante_ocupacao_2026) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->paga_iptu) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->assinou_unica_propriedade) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_declaracao_unica_propriedade) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->assinou_ocupacao_mansa_pacifica) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_declaracao_ocupacao_mansa_pacifica) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->assinou_veracidade) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_declaracao_veracidade) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->assinou_lgpd) ?></td>
                                        <td><?= htmlspecialchars($obj->foto_declaracao_lgpd) ?></td>
                                        <td><?= htmlspecialchars($obj->nome_cadastrador) ?></td>
                                        <td class="text-center"><?= $obj->data_registro ?></td>

                                    <?php } elseif ($tipo == 'caracterizacao') { ?>
                                        <td class="text-center font-weight-bold"><?= $obj->id_submissao ?></td>
                                        <td><?= htmlspecialchars($obj->uuid) ?></td>
                                        <td class="font-weight-bold text-center bg-light"><?= $obj->codigo_selo ?></td>
                                        <td><?= htmlspecialchars($obj->foto_selo) ?></td>
                                        <td class="text-center"><?= $obj->quantidade_comodos ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->comodos_improvisados_dormitorio) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->mais_de_3_por_dormitorio) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->faltam_camas) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->possui_banheiro) ?></td>
                                        <td class="text-center"><?= $obj->numero_banheiros ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->revestimento_ceramico_banheiro) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->louca_sanitaria_banheiro) ?></td>
                                        <td><?= htmlspecialchars($obj->problemas_estruturais_observados) ?></td>
                                        <td><?= htmlspecialchars($obj->problemas_infiltracao) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->existe_comodo_com_mofo) ?></td>
                                        <td><?= htmlspecialchars($obj->comodos_com_mofo) ?></td>
                                        <td><?= htmlspecialchars($obj->paredes_materiais) ?></td>
                                        <td><?= htmlspecialchars($obj->paredes_condicao) ?></td>
                                        <td><?= htmlspecialchars($obj->cobertura_materiais) ?></td>
                                        <td><?= htmlspecialchars($obj->cobertura_condicao) ?></td>
                                        <td><?= htmlspecialchars($obj->piso_materiais) ?></td>
                                        <td><?= htmlspecialchars($obj->piso_condicao) ?></td>
                                        <td><?= htmlspecialchars($obj->energia_acesso) ?></td>
                                        <td><?= htmlspecialchars($obj->energia_condicao_instalacao) ?></td>
                                        <td><?= htmlspecialchars($obj->energia_condicao_instalacao_internas) ?></td>
                                        <td><?= htmlspecialchars($obj->agua_acesso) ?></td>
                                        <td><?= htmlspecialchars($obj->materiais_componencatorios_observados) ?></td>
                                        <td><?= htmlspecialchars($obj->instalacoes_hidrosanitarias_disponives) ?></td>
                                        <td><?= htmlspecialchars($obj->condicoes_hidrosanitarias) ?></td>
                                        <td><?= htmlspecialchars($obj->esgotamento_sanitario) ?></td>
                                        <td><?= htmlspecialchars($obj->coleta_lixo) ?></td>
                                        <td><?= htmlspecialchars($obj->frequencia_coleta_lixo) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->existe_drenagem) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->pavimentacao_rua) ?></td>
                                        <td><?= htmlspecialchars($obj->pcd_no_domicilio) ?></td>
                                        <td class="text-center"><?= $obj->quantidade_pcd ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->pessoas_mobilidade_reduzida) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->pessoas_cadeirantes) ?></td>
                                        <td><?= htmlspecialchars($obj->pessoas_doencas_respiratorias) ?></td>
                                        <td class="text-center"><?= $obj->quantidade_afetados_respiratorio ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->hove_acidentes_domesticos) ?></td>
                                        <td><?= htmlspecialchars($obj->ocorrencia_inundacao) ?></td>
                                        <td><?= htmlspecialchars($obj->frequencia_inundacao) ?></td>
                                        <td><?= htmlspecialchars($obj->altura_agua_inundacao) ?></td>
                                        <td><?= htmlspecialchars($obj->quando_chove) ?></td>
                                        <td class="text-center"><?= $obj->sensacao_seguranca_rua ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->possui_iluminacao_publica) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->possui_equipamentos_lazer) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->possui_equipamentos_saude) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->possui_transporte_publico) ?></td>
                                        <td><?= htmlspecialchars($obj->principal_meio_locomocao) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->ha_escada) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->ha_comodos_sem_janela) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->ha_calcada_na_rua) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->imovel_terreno_tem_rachaduras) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->paredes_embarrigadas) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->ha_postes_arvores_inclinados) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->houve_deslizamentos) ?></td>
                                        <td><?= htmlspecialchars($obj->onde_deslizamento) ?></td>
                                        <td><?= htmlspecialchars($obj->quando_deslizamento) ?></td>
                                        <td><?= htmlspecialchars($obj->responsavel_coleta) ?></td>
                                        <td class="text-center"><?= $obj->data_registro ?></td>
                                    <?php } ?>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } else { 
            $_SESSION['msg'] = 'Erro';
            header('Location: gerar_busca_periodo.php?msg=1');
            exit();
        } ?>
    </div>
</body>
</html>