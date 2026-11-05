<?php
/**
 * Carregado no início de todas as páginas: configuração, sessão,
 * conexão PDO e funções auxiliares (escape, CSRF, mensagens, login).
 */

declare(strict_types=1);

define('RAIZ', dirname(__DIR__));

$GLOBALS['CONFIG']  = require RAIZ . '/config/config.php';
$GLOBALS['TABELAS'] = require RAIZ . '/config/tabelas.php';
$GLOBALS['PERMISSOES'] = require RAIZ . '/config/permissoes.php';

date_default_timezone_set(cfg('fuso_horario', 'America/Sao_Paulo'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_cache_limiter('');   // cabeçalhos de cache definidos logo abaixo, sem depender do php.ini
    session_start();
}

// Páginas logadas nunca podem ser guardadas pelo navegador ou por proxy:
// senão, depois de trocar de usuário, aparece a página guardada do anterior.
if (PHP_SAPI !== 'cli' && !headers_sent()) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private');
    header('Pragma: no-cache');
    header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
}

require_once __DIR__ . '/Crud.php';
require_once __DIR__ . '/senha.php';
require_once __DIR__ . '/acesso.php';

/* ------------------------------------------------------------------ */
/* Configuração                                                        */
/* ------------------------------------------------------------------ */

function cfg(string $chave, $padrao = null)
{
    return $GLOBALS['CONFIG'][$chave] ?? $padrao;
}

/* ------------------------------------------------------------------ */
/* Banco                                                               */
/* ------------------------------------------------------------------ */

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c   = cfg('db');
        $dsn = "mysql:host={$c['host']};port={$c['porta']};dbname={$c['banco']};charset={$c['charset']}";
        $pdo = new PDO($dsn, $c['usuario'], $c['senha'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

/** Retorna o objeto Crud de uma chave de tabela da configuração, ou encerra com 404. */
function crud_ou_404(?string $chave): Crud
{
    if ($chave === null || !isset($GLOBALS['TABELAS'][$chave])) {
        http_response_code(404);
        exit('Tabela não encontrada.');
    }
    return new Crud($chave, $GLOBALS['TABELAS'][$chave]);
}

/* ------------------------------------------------------------------ */
/* Saída                                                               */
/* ------------------------------------------------------------------ */

function e($valor): string
{
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
}

function redirecionar(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Endereço de uma página/arquivo do CRUD a partir da raiz do site
 * (ex.: 'lista.php?t=selagem' -> '/reurb_crud/lista.php?t=selagem').
 * Assim o menu e o layout funcionam incluídos em páginas de qualquer pasta.
 * Endereços completos (http://, /caminho, #) são mantidos como estão.
 */
function crud_url(string $caminho = ''): string
{
    if (preg_match('#^([a-z][a-z0-9+.\-]*:|//|/|\#)#i', $caminho)) {
        return $caminho;
    }
    return crud_base_url() . $caminho;
}

/** Pasta do CRUD no site, com barra no fim (ex.: '/reurb_crud/'). */
function crud_base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }
    $configurado = (string) cfg('crud_url', '');
    if ($configurado !== '') {
        return $base = rtrim($configurado, '/') . '/';
    }

    $normalizar = function (string $p): string {
        $p = rtrim(str_replace('\\', '/', $p), '/');
        return PHP_OS_FAMILY === 'Windows' ? strtolower($p) : $p;
    };
    $raiz = realpath(RAIZ);

    // 1) Pasta do CRUD dentro da raiz do site (DOCUMENT_ROOT)
    $doc = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($doc && $raiz && str_starts_with($normalizar($raiz) . '/', $normalizar($doc) . '/')) {
        $rel = substr(str_replace('\\', '/', $raiz), strlen(rtrim(str_replace('\\', '/', $doc), '/')));
        return $base = rtrim($rel, '/') . '/';
    }

    // 2) Página atual está na própria pasta do CRUD (ex.: pasta publicada por Alias)
    $script = realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? ''));
    if ($script && $raiz && $normalizar(dirname($script)) === $normalizar($raiz)) {
        return $base = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/') . '/';
    }

    // 3) Sem como descobrir: links relativos (funcionam dentro da pasta do CRUD)
    return $base = '';
}

function url(string $pagina, array $params = []): string
{
    $params = array_filter($params, fn($v) => $v !== null && $v !== '');
    return $pagina . ($params ? '?' . http_build_query($params) : '');
}

/* ------------------------------------------------------------------ */
/* Mensagens (flash)                                                   */
/* ------------------------------------------------------------------ */

function flash(string $tipo, string $mensagem): void
{
    $_SESSION['_flash'][] = ['tipo' => $tipo, 'msg' => $mensagem];
}

function flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/* ------------------------------------------------------------------ */
/* CSRF                                                                */
/* ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_validar(): void
{
    $enviado = $_POST['_csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrf_token(), $enviado)) {
        http_response_code(419);
        exit('Sessão expirada. Volte e tente novamente.');
    }
}

/* ------------------------------------------------------------------ */
/* Autenticação                                                        */
/* ------------------------------------------------------------------ */

function usuario_id(): ?int
{
    $id = $_SESSION[cfg('sessao_id_usuario')] ?? null;
    return $id === null ? null : (int) $id;
}

function usuario_nome(): string
{
    // Nome lido do banco pelo ID da sessão (nunca fica desatualizado)
    if (function_exists('usuario_atual') && usuario_id() !== null) {
        try {
            $u = usuario_atual();
            if ($u !== null) {
                return (string) $u['nome'];
            }
        } catch (Throwable $e) {
            // sem banco: usa o nome guardado na sessão
        }
    }
    return (string) ($_SESSION[cfg('sessao_nome_usuario')] ?? '');
}

/** Encerra a sessão por completo: apaga os dados, o arquivo e o cookie. */
function encerrar_sessao(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 42000,
            'path'     => $p['path'],
            'domain'   => $p['domain'],
            'secure'   => $p['secure'],
            'httponly' => $p['httponly'],
            'samesite' => $p['samesite'] ?: 'Lax',
        ]);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

function exigir_login(): void
{
    if (usuario_id() === null) {
        redirecionar(crud_url(cfg('pagina_login', 'login.php')));
    }
    // Usuário excluído ou inativado depois de entrar: encerra a sessão
    $u = usuario_atual();
    if ($u === null || (string) $u['ativo'] !== '1') {
        $_SESSION = [];
        session_regenerate_id(true);
        flash('warning', 'Seu acesso foi desativado. Procure um administrador.');
        redirecionar(crud_url(cfg('pagina_login', 'login.php')));
    }
}
