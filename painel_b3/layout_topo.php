<?php
/** Abre a estrutura da página (menu lateral + barra superior). Use com layout_base.php. */
include __DIR__ . '/head.php';
?>
    <body id="page-top">
        <!-- Page Wrapper -->
        <div id="wrapper">
            <?php include __DIR__ . '/menu.php'; ?>
            <!-- Content Wrapper -->
            <div id="content-wrapper" class="d-flex flex-column">
                <!-- Main Content -->
                <div id="content">
                    <?php include __DIR__ . '/top_bar.php'; ?>
                    <div class="container-fluid">
                        <?= mostra_flash() ?>
