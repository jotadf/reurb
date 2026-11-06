<?php
/**
 * Controle de acesso por perfil (config/permissoes.php).
 *
 * O perfil é lido do banco a cada requisição (não fica guardado na sessão):
 * mudar o perfil de alguém ou inativá-lo vale na hora, sem precisar sair e entrar.
 */

declare(strict_types=1);

/** Usuário logado com o nome do perfil, ou null. */
function usuario_atual(): ?array
{
    static $cache = false;
    if ($cache !== false) {
        return $cache;
    }
    $id = usuario_id();
    if ($id === null) {
        return $cache = null;
    }
    $st = db()->prepare('SELECT u.id, u.nome, u.ativo, u.id_perfil, p.perfil
                           FROM usuario u LEFT JOIN perfil p ON p.id = u.id_perfil
                          WHERE u.id = ?');
    $st->execute([$id]);
    return $cache = ($st->fetch() ?: null);
}

function perfil_nome(): string
{
    return (string) (usuario_atual()['perfil'] ?? '');
}

/** Regras do perfil (por ID ou nome). Perfil não configurado = sem regras. */
function regras_do_perfil($idPerfil, ?string $nomePerfil): array
{
    $perfis = $GLOBALS['PERMISSOES']['perfis'] ?? [];
    $nome   = Crud::normalizar((string) $nomePerfil);
    foreach ($perfis as $chave => $regras) {
        if ((string) $chave === (string) $idPerfil || ($nome !== '' && Crud::normalizar((string) $chave) === $nome)) {
            return $regras;
        }
    }
    return [];
}

/** O perfil informado pode executar a ação na tela? */
function perfil_pode(array $regras, string $tela, string $acao): bool
{
    $acoes = array_merge($regras['*'] ?? [], $regras[$tela] ?? []);
    return in_array($acao, $acoes, true);
}

/** O usuário logado pode executar a ação na tela? */
function pode(string $tela, string $acao = 'ver'): bool
{
    $u = usuario_atual();
    if ($u === null) {
        return false;
    }
    return perfil_pode(regras_do_perfil($u['id_perfil'], $u['perfil']), $tela, $acao);
}

/** O usuário logado vê o link do menu que aponta para uma tela externa? */
function pode_link_externo(string $chave): bool
{
    $lista = $GLOBALS['PERMISSOES']['menu_externo'][$chave] ?? null;
    if ($lista === null) {
        return true;
    }
    return perfil_na_lista($lista);
}

/** O usuário logado pode usar uma página própria do CRUD ('paginas' em permissoes.php)? */
function pode_pagina(string $chave): bool
{
    $lista = $GLOBALS['PERMISSOES']['paginas'][$chave] ?? [];
    return perfil_na_lista($lista);
}

/** O perfil do usuário logado está na lista (por nome, sem acento/maiúscula, ou por ID)? */
function perfil_na_lista(array $lista): bool
{
    $u = usuario_atual();
    if ($u === null) {
        return false;
    }
    foreach ($lista as $p) {
        if ((string) $p === (string) $u['id_perfil'] || Crud::normalizar((string) $p) === Crud::normalizar((string) $u['perfil'])) {
            return true;
        }
    }
    return false;
}

/** Interrompe a página com "acesso negado" se o usuário não tiver a permissão. */
function exigir_permissao(string $tela, string $acao = 'ver'): void
{
    if (!pode($tela, $acao)) {
        $nomes = ['ver' => 'visualizar', 'incluir' => 'incluir registros em', 'alterar' => 'alterar', 'excluir' => 'excluir registros de'];
        $titulo = $GLOBALS['TABELAS'][$tela]['titulo'] ?? $tela;
        acesso_negado('Seu perfil (' . (perfil_nome() ?: 'sem perfil') . ') não tem permissão para '
            . ($nomes[$acao] ?? $acao) . ' ' . $titulo . '.');
    }
}

function acesso_negado(string $mensagem): void
{
    http_response_code(403);
    error_log('[REURB acesso negado] usuário ' . usuario_id() . ': ' . $mensagem);
    $tituloPagina = 'Acesso negado';
    $menuAtivo    = '';
    require RAIZ . '/includes/topo.php';
    echo '<div class="text-center mt-5">'
       . '<div class="error mx-auto" data-text="403">403</div>'
       . '<p class="lead text-gray-800 mb-3">Acesso negado</p>'
       . '<p class="text-gray-600 mb-4">' . e($mensagem) . '</p>'
       . '<a href="' . e(crud_url('index.php')) . '">&larr; Voltar para o início</a>'
       . '</div>';
    require RAIZ . '/includes/rodape.php';
    exit;
}

/**
 * Impede que o sistema fique sem nenhum Administrador ativo
 * (alguém que possa alterar usuários).
 * $novos = valores que serão gravados; null = exclusão.
 */
function proteger_ultimo_admin(Crud $crud, $id, ?array $novos): void
{
    if ($crud->tabela !== 'usuario') {
        return;
    }
    $alvo = $crud->buscar($id);
    if ($alvo === null || (string) $alvo['ativo'] !== '1' || !perfil_id_eh_admin($alvo['id_perfil'])) {
        return; // não é um administrador ativo hoje
    }
    if ($novos !== null) {
        $perfilNovo = array_key_exists('id_perfil', $novos) && $novos['id_perfil'] !== '' ? $novos['id_perfil'] : $alvo['id_perfil'];
        $ativoNovo  = array_key_exists('ativo', $novos) && $novos['ativo'] !== '' ? $novos['ativo'] : $alvo['ativo'];
        if ((string) $ativoNovo === '1' && perfil_id_eh_admin($perfilNovo)) {
            return; // continua administrador ativo
        }
    }
    $st = db()->prepare('SELECT id_perfil FROM usuario WHERE ativo = 1 AND id <> ?');
    $st->execute([$id]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $idPerfil) {
        if (perfil_id_eh_admin($idPerfil)) {
            return; // existe outro administrador ativo
        }
    }
    throw new RuntimeException('Operação bloqueada: este é o único Administrador ativo. '
        . 'Cadastre ou ative outro Administrador antes de remover, inativar ou trocar o perfil deste.');
}

/** Administrador = perfil que pode alterar a tela de usuários. */
function perfil_id_eh_admin($idPerfil): bool
{
    static $nomes = null;
    if ($nomes === null) {
        $nomes = db()->query('SELECT id, perfil FROM perfil')->fetchAll(PDO::FETCH_KEY_PAIR);
    }
    return perfil_pode(regras_do_perfil($idPerfil, $nomes[$idPerfil] ?? null), 'usuario', 'alterar');
}
