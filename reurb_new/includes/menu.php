<?php
/**
 * Menu lateral (tema SB Admin 2).
 *
 * Usado pelo includes/topo.php do CRUD e pode ser incluído em qualquer outra
 * página do sistema (Importar Dados, relatórios...). Na página de fora:
 *
 *   <?php
 *   require_once __DIR__ . '/reurb_crud/lib/bootstrap.php';  // no início, antes de qualquer HTML
 *   exigir_login();
 *   $menuAtivo = 'importar_dados';                            // item destacado (opcional)
 *   ?>
 *   ...
 *   <div id="wrapper">
 *       <?php require RAIZ . '/includes/menu.php'; ?>
 *       <div id="content-wrapper" class="d-flex flex-column"> ... </div>
 *   </div>
 *
 * Os links já saem com o caminho completo da pasta do CRUD, então funcionam
 * de qualquer pasta. Os itens aparecem conforme o perfil (config/permissoes.php).
 *
 * Variável opcional: $menuAtivo = chave do item ativo ('selagem', 'usuario',
 * 'importar_dados', 'gerar_relatorio'...).
 */

require_once dirname(__DIR__) . '/lib/bootstrap.php';

/* ------------------------------------------------------------------
 * Estrutura do menu. Para mudar a ordem, os textos ou os ícones, edite aqui.
 *   ['crud', chave da tela em config/tabelas.php]
 *   ['link', chave em 'links_externos' (config.php), ícone, texto]
 * ------------------------------------------------------------------ */
$MENU_LATERAL = [
    'Gestão de Acesso' => [
        ['crud', 'perfil'],
        ['crud', 'equipe'],
        ['crud', 'usuario'],
        ['crud', 'auditoria'],
    ],
    'Importação da Coleta' => [
        ['link', 'importar_dados', 'fa-id-card', 'Importar Dados'],
        ['link', 'gerenciar_imagens', 'fa-file-image', 'Gerenciar Imagens'],
    ],
    'Edição de Dados' => [
        ['crud', 'selagem'],
        ['crud', 'domicilios'],
        ['crud', 'socio'],
        ['crud', 'caracterizacao'],
    ],
    'Relatório' => [
        ['link', 'gerar_relatorio', 'fa-file-excel', 'Gerar Relatório'],
        ['link', 'selagens_sem_socio', 'fa-file-excel', 'Listagem de Selagens Sem Sócio Jurídico'],
        ['link', 'caract_sem_selagem', 'fa-file-excel', 'Listagem de Caracterizações Sem Selagem'],
    ],
];

(function (array $menu, string $ativo): void {
    $T     = $GLOBALS['TABELAS'];
    $links = cfg('links_externos', []);

    $item = function (string $href, string $icone, string $texto, bool $estaAtivo): string {
        return '<li class="nav-item' . ($estaAtivo ? ' active' : '') . '"><a class="nav-link" href="' . e($href) . '">'
             . '<i class="fas fa-fw ' . e($icone) . '"></i> <span>' . e($texto) . '</span></a></li>';
    };

    // Quem só visualiza os dados não vê o grupo como "Edição"
    $soVe = !pode('selagem', 'alterar') && !pode('domicilios', 'alterar')
         && !pode('socio', 'alterar') && !pode('caracterizacao', 'alterar');
    ?>
    <!-- ===================== Menu lateral ===================== -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">

        <a class="sidebar-brand d-flex align-items-center justify-content-center" href="<?= e(crud_url('index.php')) ?>">
            <div class="sidebar-brand-icon"><i class="fas fa-desktop"></i></div>
            <div class="sidebar-brand-text mx-2"><?= e(cfg('app_nome')) ?></div>
        </a>

        <hr class="sidebar-divider my-0">

        <?php foreach ($menu as $grupo => $itens):
            $html = '';
            foreach ($itens as $i) {
                if ($i[0] === 'crud') {
                    $chave = $i[1];
                    if (isset($T[$chave]) && pode($chave, 'ver')) {
                        $html .= $item(crud_url(url('lista.php', ['t' => $chave])), $T[$chave]['icone'] ?? 'fa-table',
                                       $i[2] ?? $T[$chave]['titulo'], $ativo === $chave);
                    }
                } elseif ($i[0] === 'link' && pode_link_externo($i[1])) {
                    $html .= $item(crud_url($links[$i[1]] ?? '#'), $i[2], $i[3], $ativo === $i[1]);
                }
            }
            if ($html === '') {
                continue;   // grupo sem nenhum item permitido para o perfil
            }
            if ($grupo === 'Edição de Dados' && $soVe) {
                $grupo = 'Dados da Coleta';
            } ?>
            <hr class="sidebar-divider">
            <div class="sidebar-heading"><?= e($grupo) ?></div>
            <?= $html ?>
        <?php endforeach; ?>

        <hr class="sidebar-divider d-none d-md-block">
        <div class="text-center d-none d-md-inline">
            <button class="rounded-circle border-0" id="sidebarToggle" type="button" aria-label="Recolher menu"></button>
        </div>
    </ul>
    <?php
})($MENU_LATERAL, (string) ($menuAtivo ?? ''));
