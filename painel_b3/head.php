<?php
/** Cabeçalho comum. Defina $titulo antes de incluir. */
$titulo = isset($titulo) ? $titulo . ' | ' . config('app.nome') : config('app.nome');
?>
<!DOCTYPE html>
<html lang="pt-br">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <title><?= e($titulo) ?></title>

        <!-- Fontes e ícones -->
        <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
        <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800&display=swap" rel="stylesheet">

        <!-- Tema SB Admin 2 (o mesmo do REURB) -->
        <link href="css/sb-admin-2.min.css" rel="stylesheet">
        <link href="vendor/datatables/dataTables.bootstrap4.min.css" rel="stylesheet">
        <link href="vendor/datatables/responsive.bootstrap4.min.css" rel="stylesheet">
        <link href="css/painel.css" rel="stylesheet">
        <link rel="icon" href="img/favicon.svg" type="image/svg+xml">

        <script src="vendor/jquery/jquery.min.js"></script>
        <script src="vendor/datatables/jquery.dataTables.min.js"></script>
        <script src="vendor/datatables/dataTables.bootstrap4.min.js"></script>
        <script src="vendor/datatables/dataTables.responsive.min.js"></script>
        <script src="vendor/datatables/responsive.bootstrap4.min.js"></script>
        <script src="vendor/jquery-mask/jquery.mask.min.js"></script>
        <script src="vendor/chartjs/chart.umd.min.js"></script>
        <script src="vendor/chartjs/luxon.min.js"></script>
        <script src="vendor/chartjs/chartjs-adapter-luxon.umd.min.js"></script>
        <script src="vendor/chartjs/chartjs-chart-financial.min.js"></script>
        <script src="js/painel.js"></script>
    </head>
