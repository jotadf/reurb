<?php
/**
 * Cabeçalho + menu lateral + barra superior (tema SB Admin 2).
 * Antes de incluir, defina:
 *   $tituloPagina  (string)  título da aba do navegador
 *   $menuAtivo     (string)  chave da tela ativa (ex.: 'selagem')
 */
$tema       = rtrim((string) cfg('tema_base'), '/') . '/';
$menuAtivo  = $menuAtivo ?? '';
$links      = cfg('links_externos', []);
$T          = $GLOBALS['TABELAS'];

/** Item do menu apontando para uma tela de CRUD */
$itemCrud = function (string $chave, ?string $texto = null) use ($menuAtivo, $T): string {
    if (!isset($T[$chave]) || !pode($chave, 'ver')) {
        return '';
    }
    $ativo = $menuAtivo === $chave ? ' active' : '';
    return '<li class="nav-item' . $ativo . '"><a class="nav-link" href="' . e(url('lista.php', ['t' => $chave])) . '">'
         . '<i class="fas fa-fw ' . e($T[$chave]['icone'] ?? 'fa-table') . '"></i> <span>' . e($texto ?? $T[$chave]['titulo']) . '</span></a></li>';
};
/** Item do menu apontando para uma tela já existente no sistema */
$itemLink = function (string $chaveLink, string $icone, string $texto) use ($links): string {
    if (!pode_link_externo($chaveLink)) {
        return '';
    }
    return '<li class="nav-item"><a class="nav-link" href="' . e($links[$chaveLink] ?? '#') . '">'
         . '<i class="fas fa-fw ' . e($icone) . '"></i> <span>' . e($texto) . '</span></a></li>';
};
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
    <link href="assets/css/reurb-crud.css" rel="stylesheet">
</head>
<body id="page-top">
<div id="wrapper">

    <!-- ===================== Menu lateral ===================== -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
            <div class="sidebar-brand-icon"><i class="fas fa-desktop"></i></div>
            <div class="sidebar-brand-text mx-2"><?= e(cfg('app_nome')) ?></div>
        </a>

        <hr class="sidebar-divider my-0">

        <?php
        // Cada grupo do menu só aparece se o perfil tiver pelo menos um item nele
        $grupos = [
            'Gestão de Acesso'     => $itemCrud('perfil') . $itemCrud('equipe') . $itemCrud('usuario') . $itemCrud('auditoria'),
            'Importação da Coleta' => $itemLink('importar_dados', 'fa-id-card', 'Importar Dados')
                                    . $itemLink('gerenciar_imagens', 'fa-file-image', 'Gerenciar Imagens'),
            'Edição de Dados'      => $itemCrud('selagem') . $itemCrud('domicilios') . $itemCrud('socio') . $itemCrud('caracterizacao'),
            'Relatório'            => $itemLink('gerar_relatorio', 'fa-file-excel', 'Gerar Relatório')
                                    . $itemLink('selagens_sem_socio', 'fa-file-excel', 'Listagem de Selagens Sem Sócio Jurídico')
                                    . $itemLink('caract_sem_selagem', 'fa-file-excel', 'Listagem de Caracterizações Sem Selagem'),
        ];
        // Para quem só visualiza, o grupo de dados não é de "edição"
        if (!pode('selagem', 'alterar') && !pode('domicilios', 'alterar') && !pode('socio', 'alterar') && !pode('caracterizacao', 'alterar')) {
            $grupos = array_combine(
                array_map(fn($g) => $g === 'Edição de Dados' ? 'Dados da Coleta' : $g, array_keys($grupos)),
                array_values($grupos)
            );
        }
        foreach ($grupos as $tituloGrupo => $itens):
            if ($itens === '') {
                continue;
            } ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading"><?= e($tituloGrupo) ?></div>
            <?= $itens ?>
        <?php endforeach; ?>

        <hr class="sidebar-divider d-none d-md-block">
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle" type="button" aria-label="Recolher menu"></button>
        </div>
    </ul>

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
                            <a class="dropdown-item" href="logout.php">
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
