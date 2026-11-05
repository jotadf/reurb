<?php
/**
 * Cabeçalho + menu lateral + barra superior (tema SB Admin 2).
 * Antes de incluir, defina:
 *   $tituloPagina  (string)  título da aba do navegador
 *   $menuAtivo     (string)  chave do item ativo no menu (ex.: 'selagem')
 *
 * O menu lateral fica em includes/menu.php.
 * Este arquivo e o rodape.php também podem ser usados por páginas de fora da
 * pasta do CRUD (os endereços saem completos via crud_url()).
 */
$tema      = crud_url(rtrim((string) cfg('tema_base'), '/') . '/');
$menuAtivo = $menuAtivo ?? '';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= e(($tituloPagina ?? '') !== '' ? $tituloPagina . ' | ' . cfg('app_nome') : cfg('app_nome')) ?></title>

    <link href="<?= e($tema) ?>vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Nunito:300,400,600,700,800,900" rel="stylesheet">
    <link href="<?= e($tema) ?>css/sb-admin-2.min.css" rel="stylesheet">
    <link href="<?= e(crud_url('assets/css/reurb-crud.css')) ?>" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">

    <?php require __DIR__ . '/menu.php'; ?>

    <!-- ===================== Conteúdo ===================== -->
    <div id="content-wrapper" class="d-flex flex-column">
        <div id="content">

            <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3" type="button" aria-label="Abrir menu">
                    <i class="fa fa-bars"></i>
                </button>
                <ul class="navbar-nav ml-auto">
                    <div class="topbar-divider d-none d-sm-block"></div>
                    <li class="nav-item dropdown no-arrow">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                           data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?= e(usuario_nome()) ?>( <?= e(cfg('app_nome')) ?> )</span>
                            <i class="fas fa-user-circle fa-2x text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                            <span class="dropdown-item-text small text-gray-600">
                                <i class="fas fa-user-shield fa-sm fa-fw mr-2 text-gray-400"></i>Perfil: <strong><?= e(perfil_nome() ?: '—') ?></strong>
                            </span>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="<?= e(crud_url('logout.php')) ?>">
                                <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i> Sair
                            </a>
                        </div>
                    </li>
                </ul>
            </nav>

            <div class="container-fluid">
                <?php foreach (flashes() as $f): ?>
                    <div class="alert alert-<?= e($f['tipo']) ?> alert-dismissible fade show reurb-alerta" role="alert">
                        <?= e($f['msg']) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
                    </div>
                <?php endforeach; ?>
