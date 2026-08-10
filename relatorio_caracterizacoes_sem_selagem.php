<?php

require_once('./verifica_login.php');
include_once('actions/ManterRelatorio.php');

$manterRelatorio = new ManterRelatorio();

$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'selagem';

$inicio = isset($_POST['inicio']) ? $_POST['inicio'] : '';
$termino = isset($_POST['termino']) ? $_POST['termino'] : '';

$where = '';
$lista = array();
$lista = $manterRelatorio->listarCaracterizacaoSemSelagem();
$total = array_merge($lista);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relatório de Caracterizações sem Selagem</title>
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
                <h3 class="text-center mb-3">Relatório de Caracterizações sem Selagem</h3>
                <div class="text-right mb-2">
                    <img src="img/iconexcel.png" width="28" height="28" style="cursor:pointer;" class="d-print-none" id="btnExport" title="Exportar para Excel" />
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover" id="registros">
                        <thead class="thead-dark text-nowrap text-center">
                            <tr>
                                <th>ID SUBMISSÃO</th><th>UUID</th><th>CÓDIGO SELO</th><th>FOTO SELO</th><th>QTD CÔMODOS</th><th>CÔMODOS IMPROV.</th><th>MAIS 3 POR DORM.</th><th>FALTAM CAMAS</th><th>POSSUI BANHEIRO</th><th>Nº BANHEIROS</th><th>REVEST. BANHEIRO</th><th>LOUÇA BANHEIRO</th><th>PROBL. ESTRUTURAIS</th><th>PROBL. INFILTRAÇÃO</th><th>CÔMODO MOFO</th><th>CÔMODOS C/ MOFO</th><th>PAREDES MATERIAIS</th><th>PAREDES CONDIÇÃO</th><th>COBERTURA MATERIAIS</th><th>COBERTURA CONDIÇÃO</th><th>PISO MATERIAIS</th><th>PISO CONDIÇÃO</th><th>ENERGIA ACESSO</th><th>ENERGIA CONDIÇÃO</th><th>INSTAL. INTERNAS</th><th>ÁGUA ACESSO</th><th>MATERIAIS SANITÁRIOS</th><th>INSTAL. HIDROS. DISP.</th><th>CONDIÇÕES HIDROS.</th><th>ESGOTO</th><th>COLETA LIXO</th><th>FREQ. COLETA LIXO</th><th>EXISTE DRENAGEM</th><th>PAVIMENTAÇÃO</th><th>PCD DOMICÍLIO</th><th>QTD PCD</th><th>MOBILIDADE REDUZIDA</th><th>CADEIRANTES</th><th>DOENÇAS RESP.</th><th>QTD AFETADOS RESP.</th><th>HOUVE ACIDENTES</th><th>OCORRÊNCIA INUNDAÇÃO</th><th>FREQ. INUNDAÇÃO</th><th>ALTURA ÁGUA</th><th>QUANDO CHOVE</th><th>SENSACÃO SEGURANÇA</th><th>ILUMINAÇÃO PÚBLICA</th><th>EQUIP. LAZER</th><th>EQUIP. SAÚDE</th><th>TRANSPORTE PÚBLICO</th><th>MEIO LOCOMOÇÃO</th><th>HA ESCADA</th><th>CÔMODOS S/ JANELA</th><th>CALÇADA NA RUA</th><th>TERRENO RACHADURAS</th><th>PAREDES EMBARRIGADAS</th><th>POSTES INCLINADOS</th><th>HOUVE DESLIZAMENTO</th><th>ONDE DESLIZAMENTO</th><th>QUANDO DESLIZAMENTO</th><th>RESPONSÁVEL COLETA</th><th>DATA REGISTRO</th>                            
                            </tr>
                        </thead>
                        <tbody class="text-nowrap">
                            <?php foreach ($total as $obj) { ?>
                                <tr>
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
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } ?>
    </div>
</body>
</html>