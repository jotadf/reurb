<!-- Sidebar -->
<ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion " id="accordionSidebar">

    <!-- Sidebar - Brand -->
    <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-icon">
            <i class="fa fa-desktop"></i>
        </div>
        <div class="sidebar-brand-text mx-1">REURB</div>
    </a>


        <!-- Divider -->
        <hr class="sidebar-divider my-0">

        <!-- Divider -->
        <hr class="sidebar-divider">
    <?php
    if ($usuario_logado->perfil < 2) {
        ?>
        <!-- Heading -->
        <div class="sidebar-heading">
            Gestão de Acesso
        </div>      
        <!-- Nav Item - Pages Collapse Menu -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="perfis.php">
                <i class="fa fa-id-card"></i>
                <span>Perfis</span>
            </a>
        </li>
        <!-- Nav Item - Pages Collapse Menu -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="equipes.php">
                <i class="fa fa-users"></i>
                <span>Equipes</span>
            </a>
        </li>      
        <!-- Nav Item - Pages Collapse Menu -->
        <li class="nav-item">
            <a class="nav-link collapsed" href="usuarios.php">
                <i class="fa fa-user"></i>
                <span>Usuários</span>
            </a>
        </li>
   <?php
    } 
    if ($usuario_logado->perfil <= 3) {
    ?>
    <!-- Divider -->
    <hr class="sidebar-divider">  
    <div class="sidebar-heading">
        Importação da Coleta
    </div>
    <!-- Nav Item - Pages Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="importa_dados.php">
            <i class="fa fa-id-card"></i>
            <span>Importar Dados</span>
        </a>
    </li>
            <!-- Nav Item - Pages Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="gerenciar_arquivos_importacao.php">
            <i class="fa fa-file-image"></i>
            <span>Gerenciar Imagens</span>
        </a>
    </li>

    <!-- Divider -->
    <hr class="sidebar-divider">  
    <div class="sidebar-heading">
        Edição de Dados
    </div>
    <!-- Nav Item - Pages Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="selagens.php">
            <i class="fa fa-id-card"></i>
            <span>Selagens</span>
        </a>
    </li>
    <!-- Nav Item - Pages Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="domicilios.php">
            <i class="fa fa-home"></i>
            <span>Domicílios</span>
        </a>
    </li>
    <!-- Nav Item - Pages Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="sociojuridicos.php">
            <i class="fa fa-balance-scale"></i>
            <span>Sócio jurídicos</span>
        </a>
    </li>
    <!-- Nav Item - Pages Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="caracterizacoes.php">
            <i class="fa fa-building"></i>
            <span>Caracterizações</span>
        </a>
    </li>
     <?php
    } 
    if ($usuario_logado->perfil <= 4) {
    ?>  
    <!-- Divider -->
    <hr class="sidebar-divider">  
    <div class="sidebar-heading">
        Relatório
    </div>    
    <!-- Nav Item - Pages Collapse Menu -->
    <li class="nav-item">
        <a class="nav-link collapsed" href="gerar_busca_periodo.php">
            <i class="fa fa-file-excel"></i>
            <span>Gerar Relatório</span>
        </a>
    </li>

        <!-- Sidebar Toggler (Sidebar) --> 
    <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
    </div>
</ul>
<!-- End of Sidebar -->
 <?php
    }
