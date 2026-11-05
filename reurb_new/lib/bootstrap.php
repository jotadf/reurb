<?php
/**
 * Carregado no início de todas as páginas: configuração, sessão,
 * conexão PDO e funções auxiliares (escape, CSRF, mensagens, login).
 */

declare(strict_types=1);

define('RAIZ', dirname(__DIR__));

$GLOBALS['CONFIG']  = require RAIZ . '/config/config.php';
$GLOBALS['TABELAS'] = require RAIZ . '/config/tabelas.php';

date_default_timezone_set(cfg('fuso_horario', 'America/Sao_Paulo'));

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once __DIR__ . '/Crud.php';

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
    return (string) ($_SESSION[cfg('sessao_nome_usuario')] ?? '');
}

function exigir_login(): void
{
    if (usuario_id() === null) {
        redirecionar(cfg('pagina_login', 'login.php'));
    }
}
