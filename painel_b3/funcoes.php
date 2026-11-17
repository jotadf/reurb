<?php
/**
 * Funções utilitárias compartilhadas por todas as páginas.
 */

function config($chave = null)
{
    static $cfg = null;
    if ($cfg === null) {
        $arquivo = __DIR__ . '/config/config.php';
        if (!is_file($arquivo)) {
            $arquivo = __DIR__ . '/config/config.example.php';
        }
        $cfg = require $arquivo;
        date_default_timezone_set($cfg['app']['timezone'] ?? 'America/Sao_Paulo');
    }
    if ($chave === null) {
        return $cfg;
    }
    $valor = $cfg;
    foreach (explode('.', $chave) as $parte) {
        if (!is_array($valor) || !array_key_exists($parte, $valor)) {
            return null;
        }
        $valor = $valor[$parte];
    }
    return $valor;
}

/** Escapa texto para HTML. */
function e($valor)
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

/** Normaliza um ticker digitado (ex.: " petr4 " -> "PETR4"). Retorna null se inválido. */
function normaliza_ticker($ticker)
{
    $t = strtoupper(trim((string) $ticker));
    return preg_match('/^\^?[A-Z0-9.]{2,15}$/', $t) ? $t : null;
}

function moeda($valor, $casas = 2)
{
    if ($valor === null || $valor === '') {
        return '—';
    }
    $v = (float) $valor;
    return ($v < 0 ? '-' : '') . 'R$ ' . number_format(abs($v), $casas, ',', '.');
}

function numero($valor, $casas = 2)
{
    if ($valor === null || $valor === '') {
        return '—';
    }
    return number_format((float) $valor, $casas, ',', '.');
}

/** Formata percentual. $fracao=true quando o valor vem como 0,0123 (=1,23%). */
function pct($valor, $casas = 2, $fracao = false, $sinal = true)
{
    if ($valor === null || $valor === '' || (is_float($valor) && is_nan($valor))) {
        return '—';
    }
    $v = (float) $valor * ($fracao ? 100 : 1);
    $txt = number_format($v, $casas, ',', '.') . '%';
    return ($sinal && $v > 0 ? '+' : '') . $txt;
}

/** Classe de cor (verde/vermelho) para um valor com sinal. */
function cor($valor)
{
    if ($valor === null || $valor === '') {
        return 'text-gray-600';
    }
    return (float) $valor >= 0 ? 'text-success' : 'text-danger';
}

/** Números grandes abreviados: 1,2 bi / 350,4 mi / 12,3 mil. */
function abreviado($valor, $prefixo = '')
{
    if ($valor === null || $valor === '') {
        return '—';
    }
    $v = (float) $valor;
    $abs = abs($v);
    if ($abs >= 1e12) return $prefixo . number_format($v / 1e12, 2, ',', '.') . ' tri';
    if ($abs >= 1e9)  return $prefixo . number_format($v / 1e9, 2, ',', '.') . ' bi';
    if ($abs >= 1e6)  return $prefixo . number_format($v / 1e6, 1, ',', '.') . ' mi';
    if ($abs >= 1e3)  return $prefixo . number_format($v / 1e3, 1, ',', '.') . ' mil';
    return $prefixo . number_format($v, 0, ',', '.');
}

function data_br($data, $comHora = false)
{
    if (!$data) {
        return '—';
    }
    $ts = is_numeric($data) ? (int) $data : strtotime($data);
    return $ts ? date($comHora ? 'd/m/Y H:i' : 'd/m/Y', $ts) : '—';
}

/** Converte "1.234,56" ou "1234.56" em float. */
function para_float($texto)
{
    $t = trim((string) $texto);
    if ($t === '') {
        return null;
    }
    if (strpos($t, ',') !== false) {
        $t = str_replace('.', '', $t);
        $t = str_replace(',', '.', $t);
    }
    return is_numeric($t) ? (float) $t : null;
}

function formata_cpf($cpf)
{
    $c = preg_replace('/\D/', '', (string) $cpf);
    return strlen($c) === 11 ? substr($c, 0, 3) . '.' . substr($c, 3, 3) . '.' . substr($c, 6, 3) . '-' . substr($c, 9, 2) : $c;
}

// ------------------------- Proteção CSRF -------------------------
function csrf_token()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo()
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valida()
{
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || !hash_equals(csrf_token(), $enviado)) {
        http_response_code(400);
        exit('Requisição inválida (token de segurança expirado). Volte e tente novamente.');
    }
}

// ------------------------- Mensagens entre páginas -------------------------
function flash($tipo, $mensagem)
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'msg' => $mensagem];
}

function mostra_flash()
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $html = '';
    foreach ($_SESSION['flash'] as $f) {
        $html .= '<div class="alert alert-' . e($f['tipo']) . ' alert-dismissible fade show" role="alert">'
            . e($f['msg'])
            . '<button type="button" class="close" data-dismiss="alert" aria-label="Fechar"><span aria-hidden="true">&times;</span></button></div>';
    }
    unset($_SESSION['flash']);
    return $html;
}

function redireciona($url)
{
    header('Location: ' . $url);
    exit;
}

/** Exige perfil mínimo (1 = administrador). */
function exige_perfil($perfilMaximo)
{
    global $usuario_logado;
    if (!$usuario_logado || (int) $usuario_logado->perfil > $perfilMaximo) {
        flash('warning', 'Seu perfil não tem acesso a esta área.');
        redireciona('index.php');
    }
}

// ------------------------- Componentes visuais -------------------------

/** Cabeçalho de card no padrão do REURB (faixa azul com ícone, título e ações). */
function card_cabecalho($icone, $titulo, $acoes = '')
{
    return '<div class="row ml-0 card-header py-2 bg-gradient-primary align-items-center" style="width:100%">'
        . '<div class="col-auto card-titulo-icone px-1"><i class="' . e($icone) . ' fa-2x text-white"></i></div>'
        . '<div class="col mb-0"><span class="h5 m-0 text-white">' . $titulo . '</span></div>'
        . ($acoes !== '' ? '<div class="col-auto text-right">' . $acoes . '</div>' : '')
        . '</div>';
}

/** Logo do ativo (da brapi) ou um marcador com as iniciais. */
function logo_html($ticker, $url = null, $classe = '')
{
    if ($url && preg_match('#^https://#i', $url)) {
        return '<img src="' . e($url) . '" alt="" loading="lazy" class="logo-ativo ' . e($classe) . '" onerror="this.outerHTML=\'<span class=&quot;logo-ativo logo-placeholder ' . e($classe) . '&quot;>' . e(substr(ltrim($ticker, '^'), 0, 4)) . '</span>\'">';
    }
    return '<span class="logo-ativo logo-placeholder ' . e($classe) . '">' . e(substr(ltrim($ticker, '^'), 0, 4)) . '</span>';
}

/** Nome amigável para índices. */
function nome_ticker($ticker)
{
    $indices = ['^BVSP' => 'Ibovespa'];
    return $indices[$ticker] ?? $ticker;
}

/** Lista as mensagens de erro da API num alerta discreto. */
function alerta_erros_api(array $erros)
{
    if (!$erros) {
        return '';
    }
    $itens = [];
    foreach ($erros as $t => $m) {
        $itens[] = '<strong>' . e($t) . '</strong>: ' . e($m);
    }
    return '<div class="alert alert-warning aviso-api"><i class="fas fa-exclamation-triangle mr-1"></i>'
        . 'Alguns dados não puderam ser atualizados na brapi e estão sendo exibidos a partir do cache (quando existe). '
        . implode(' &middot; ', $itens)
        . (config('brapi.token') === '' ? '<br>Dica: sem token, a brapi só responde PETR4, VALE3, MGLU3 e ITUB4. Configure o token em config/config.php.' : '')
        . '</div>';
}

/** Endereço de retorno seguro (somente páginas locais deste sistema). */
function volta_segura($url, $padrao = 'index.php')
{
    $url = (string) $url;
    return preg_match('/^[a-z_]+\.php(\?[^\r\n]*)?$/i', $url) ? $url : $padrao;
}
