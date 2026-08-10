<?php

require_once('./verifica_login.php');
include_once('actions/ManterRelatorio.php');

$manterRelatorio = new ManterRelatorio();

$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'selagem';

$inicio = isset($_POST['inicio']) ? $_POST['inicio'] : '';
$termino = isset($_POST['termino']) ? $_POST['termino'] : '';

$where = '';
$lista = array();
$lista = $manterRelatorio->listarSelagemSemSocioJuridico();
$total = array_merge($lista);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Relatório de Selagens sem Cadastro de Sociojurídico</title>
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
                <h3 class="text-center mb-3">Relatório de Selagens sem Cadastro de Sociojurídico</h3>
                <div class="text-right mb-2">
                    <img src="img/iconexcel.png" width="28" height="28" style="cursor:pointer;" class="d-print-none" id="btnExport" title="Exportar para Excel" />
                </div>
                
                <div class="table-responsive">
                    <table class="table table-striped table-bordered table-hover" id="registros">
                        <thead class="thead-dark text-nowrap text-center">
                            <tr>
                                <th>ID SUBMISSÃO</th><th>Nº SELO</th><th>UUID</th><th>RUA/SETOR</th><th>Nº LOTE</th><th>ENDEREÇO COMPLETO</th><th>TIPO OCUPAÇÃO</th><th>QTD DOMICÍLIOS TOTAL</th><th>SELADOR</th><th>DATA FORMULÁRIO</th><th>DATA/HORA SUBMISSÃO</th>
                                <th>NOME ENTREVISTADO</th><th>PRINCIPAL MORADOR</th><th>TELEFONE</th><th>CPF</th><th>ESTADO CIVIL</th><th>USO PREDOMINANTE</th><th>TIPO OCUPAÇÃO</th><th>Nº PAVIMENTOS</th><th>LOCALIZAÇÃO</th><th>ACESSO INDEP.</th><th>ÁREA LOTE (M²)</th><th>COMPROV_END</th><th>FOTO COMPROV_END</th><th>FOTO FACHADA</th><th>FOTO SELO</th><th>FOTO OCUPAÇÃO</th><th>LATITUDE</th><th>LONGITUDE</th><th>ALTITUDE</th><th>PRECISÃO</th>
                            </tr>
                        </thead>
                        <tbody class="text-nowrap">
                            <?php foreach ($total as $obj) { ?>
                                <tr>
                                        <td class="text-center font-weight-bold"><?= $obj->id_submissao ?></td>
                                        <td class="font-weight-bold text-center bg-light"><?= $obj->numero_selo ?></td>
                                        <td><?= htmlspecialchars($obj->uuid) ?></td>
                                        <td><?= htmlspecialchars($obj->rua_zona_setor) ?></td>
                                        <td class="text-center"><?= htmlspecialchars($obj->numero_lote) ?></td>
                                        <td><?= htmlspecialchars($obj->endereco_oficial_completo) ?></td>
                                        <td><?= htmlspecialchars($obj->tipo_ocupacao_lote) ?></td>
                                        <td class="text-center"><?= $obj->qtd_domicilios_total ?></td>
                                        <td><?= htmlspecialchars($obj->nome_selador) ?></td>
                                        <td class="text-center"><?= $obj->data_formulario ?></td>
                                        <td class="text-center"><?= $obj->data_hora_submissao ?></td>                                        
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