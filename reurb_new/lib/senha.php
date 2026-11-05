<?php
/**
 * Senhas da tabela usuario.
 *
 * O CRUD reconhece o formato que já está gravado no banco, para conviver com o
 * sistema atual sem quebrar o login dele:
 *   password_hash  ($2y$..., $argon2...)   crypt  ($1$, $5$, $6$...)
 *   md5 (32 hex)   sha1 (40 hex)   sha256 (64 hex)   sha512 (128 hex)
 *   texto          (senha gravada sem criptografia)
 */

declare(strict_types=1);

/** Identifica o formato de um valor gravado na coluna senha. */
function senha_detectar_formato(string $gravado): string
{
    $gravado = trim($gravado);
    if ($gravado === '') {
        return 'vazia';
    }
    $info = password_get_info($gravado);
    if (!empty($info['algo'])) {
        return 'password_hash';
    }
    if ($gravado[0] === '$') {
        return 'crypt';
    }
    return match (true) {
        (bool) preg_match('/^[a-f0-9]{32}$/i', $gravado)  => 'md5',
        (bool) preg_match('/^[a-f0-9]{40}$/i', $gravado)  => 'sha1',
        (bool) preg_match('/^[a-f0-9]{64}$/i', $gravado)  => 'sha256',
        (bool) preg_match('/^[a-f0-9]{128}$/i', $gravado) => 'sha512',
        default                                          => 'texto',
    };
}

/** Confere a senha digitada com o valor gravado, qualquer que seja o formato. */
function senha_confere(string $digitada, string $gravado): bool
{
    $configurado = (string) cfg('senha_formato', 'auto');
    if ($configurado === 'texto') {
        // Desconsidera espaços/quebras de linha nas pontas (comuns em dados importados)
        $g = trim($gravado);
        return $g !== '' && hash_equals($g, trim($digitada));
    }
    $gravado = trim($gravado);
    $formato = $configurado === 'auto' ? senha_detectar_formato($gravado) : $configurado;
    switch ($formato) {
        case 'password_hash':
        case 'crypt':
            return password_verify($digitada, $gravado);
        case 'md5':
            return hash_equals(strtolower($gravado), md5($digitada));
        case 'sha1':
            return hash_equals(strtolower($gravado), sha1($digitada));
        case 'sha256':
            return hash_equals(strtolower($gravado), hash('sha256', $digitada));
        case 'sha512':
            return hash_equals(strtolower($gravado), hash('sha512', $digitada));
        case 'texto':
            return hash_equals($gravado, $digitada);
        default:
            return false;
    }
}

/**
 * Explica por que a senha não conferiu, SEM mostrar a senha gravada.
 * Usado só no diagnóstico do login.
 */
function senha_explicar_diferenca(string $digitada, string $gravado): string
{
    $configurado = (string) cfg('senha_formato', 'auto');
    $formatoBanco = senha_detectar_formato($gravado);
    if ($formatoBanco === 'vazia') {
        return 'A senha gravada no banco está vazia.';
    }
    if ($configurado === 'texto' && $formatoBanco !== 'texto') {
        return 'A configuração diz senha sem criptografia, mas o valor gravado parece ' . $formatoBanco
             . ' (' . strlen(trim($gravado)) . ' caracteres). Use \'senha_formato\' => \'auto\'.';
    }
    if ($formatoBanco !== 'texto') {
        return 'Formato no banco: ' . $formatoBanco . '. Se o sistema atual usa outra regra (ex.: senha com "sal"), informe qual.';
    }
    $g = trim($gravado);
    $d = trim($digitada);
    if (mb_strtolower($g) === mb_strtolower($d)) {
        return 'A diferença é só de letras maiúsculas/minúsculas.';
    }
    return 'Digitada com ' . mb_strlen($d) . ' caractere(s); gravada com ' . mb_strlen($g) . ' caractere(s).';
}

/** Gera o valor a gravar no formato pedido. */
function senha_gerar(string $senha, string $formato): string
{
    return match ($formato) {
        'md5'    => md5($senha),
        'sha1'   => sha1($senha),
        'sha256' => hash('sha256', $senha),
        'sha512' => hash('sha512', $senha),
        'texto'  => $senha,
        default  => password_hash($senha, PASSWORD_DEFAULT),
    };
}

/**
 * Formato em que o CRUD deve gravar uma senha nova ou alterada.
 * Com 'senha_formato' = 'auto' (padrão), usa o mesmo formato da senha atual do
 * usuário ou, num usuário novo, o formato das senhas já existentes no banco.
 */
function senha_formato_para_gravar(?string $gravadoAtual = null): string
{
    $configurado = (string) cfg('senha_formato', 'auto');
    if ($configurado !== 'auto') {
        return $configurado;
    }
    $referencia = $gravadoAtual;
    if ($referencia === null || trim($referencia) === '') {
        try {
            $referencia = (string) db()->query(
                "SELECT senha FROM usuario WHERE senha IS NOT NULL AND senha <> '' ORDER BY id DESC LIMIT 1"
            )->fetchColumn();
        } catch (Throwable $e) {
            $referencia = '';
        }
    }
    $formato = senha_detectar_formato((string) $referencia);
    return in_array($formato, ['md5', 'sha1', 'sha256', 'sha512', 'texto'], true) ? $formato : 'password_hash';
}
