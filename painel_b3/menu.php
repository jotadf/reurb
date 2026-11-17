<?php $pagina_atual = basename($_SERVER['SCRIPT_NAME']); ?>
<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-icon">
            <i class="fa fa-chart-line"></i>
        </div>
        <div class="sidebar-brand-text mx-1"><?= e(config('app.nome')) ?></div>
    </a>

    <!-- Divider -->
    <hr class="sidebar-divider my-0">

    <li class="nav-item <?= $pagina_atual === 'index.php' ? 'active' : '' ?>">
        <a class="nav-link" href="index.php">
            <i class="fas fa-fw fa-tachometer-alt"></i>
            <span>Visão geral</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Análises</div>

    <li class="nav-item <?= $pagina_atual === 'ativo.php' ? 'active' : '' ?>">
        <a class="nav-link" href="ativo.php">
            <i class="fas fa-fw fa-chart-area"></i>
            <span>Análise de ativo</span>
        </a>
    </li>
    <li class="nav-item <?= $pagina_atual === 'comparar.php' ? 'active' : '' ?>">
        <a class="nav-link" href="comparar.php">
            <i class="fas fa-fw fa-balance-scale"></i>
            <span>Comparar ativos</span>
        </a>
    </li>
    <li class="nav-item <?= $pagina_atual === 'carteira.php' ? 'active' : '' ?>">
        <a class="nav-link" href="carteira.php">
            <i class="fas fa-fw fa-briefcase"></i>
            <span>Minha carteira</span>
        </a>
    </li>

    <hr class="sidebar-divider">
    <div class="sidebar-heading">Cadastros</div>

    <li class="nav-item <?= $pagina_atual === 'ativos.php' ? 'active' : '' ?>">
        <a class="nav-link" href="ativos.php">
            <i class="fas fa-fw fa-list"></i>
            <span>Ativos monitorados</span>
        </a>
    </li>
    <?php if ($usuario_logado->perfil <= 1) { ?>
    <li class="nav-item <?= $pagina_atual === 'usuarios.php' ? 'active' : '' ?>">
        <a class="nav-link" href="usuarios.php">
            <i class="fas fa-fw fa-user"></i>
            <span>Usuários</span>
        </a>
    </li>
    <?php } ?>

    <hr class="sidebar-divider d-none d-md-block">

    <!-- Sidebar Toggler (Sidebar) -->
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>

</ul>
<!-- End of Sidebar -->
