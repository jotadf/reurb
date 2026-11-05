<?php
/**
 * Sugestões para corrigir um vínculo errado (ex.: código do selo de um cadastro
 * sociojurídico que não existe em Domicílios). Configuração: 'corrigir_vinculo'
 * em config/tabelas.php.
 *
 * Critérios (somados; o maior total aparece primeiro):
 *   100  mesmo selo escrito de outro jeito (espaço, traço, maiúscula, zeros à esquerda)
 *    90  mesmo CPF
 *    80  mesmo nome   |  60 nome parecido
 *    70  selo com 1 caractere diferente  |  50 com 2 caracteres diferentes
 *    +5  domicílio que ainda não tem esse tipo de cadastro
 */

declare(strict_types=1);

/** Selo só com letras e números, maiúsculo: " ds-0037 01" -> "DS003701" */
function vinculo_compacto(string $s): string
{
    return strtoupper(preg_replace('/[^a-z0-9]/i', '', Crud::normalizar($s)));
}

/** Selo sem zeros à esquerda em cada grupo: "DS-0037-01" -> "DS-37-1" */
function vinculo_sem_zeros(string $s): string
{
    $grupos = preg_split('/[^a-z0-9]+/i', Crud::normalizar($s), -1, PREG_SPLIT_NO_EMPTY);
    $grupos = array_map(fn($g) => ctype_digit($g) ? (ltrim($g, '0') === '' ? '0' : ltrim($g, '0')) : strtoupper($g), $grupos);
    return implode('-', $grupos);
}

function vinculo_nome(string $s): string
{
    return trim(preg_replace('/\s+/', ' ', preg_replace('/[^a-z ]/', ' ', Crud::normalizar($s))));
}

/**
 * @return array{destino: Crud, campo: string, colunas: string[], sugestoes: array, ja_vinculados: array<string,int>, todos: string[]}
 */
function vinculo_sugestoes(Crud $crud, array $linha, int $limite = 8): array
{
    $cfgV    = $crud->cfg['corrigir_vinculo'];
    $campo   = $cfgV['campo'];
    $destino = new Crud($cfgV['tela'], $GLOBALS['TABELAS'][$cfgV['tela']]);
    $chave   = $destino->pk;
    $atual   = trim((string) ($linha[$campo] ?? ''));

    // Colunas do destino usadas para exibir e comparar
    $mapaCpf  = array_filter($cfgV['cpf'] ?? [], fn($d, $o) => isset($crud->campos[$o], $destino->campos[$d]), ARRAY_FILTER_USE_BOTH);
    $mapaNome = [];
    foreach ($cfgV['nome'] ?? [] as $o => $ds) {
        if (isset($crud->campos[$o])) {
            $mapaNome[$o] = array_values(array_filter((array) $ds, fn($d) => isset($destino->campos[$d])));
        }
    }
    $colunas = array_slice($destino->colunasLista(), 0, 4);
    $usadas  = array_values(array_unique(array_merge(
        [$chave], $colunas, array_values($mapaCpf), ...array_values($mapaNome ?: [[]])
    )));

    $q = fn(string $c) => '`' . str_replace('`', '``', $c) . '`';
    $todos = db()->query('SELECT ' . implode(', ', array_map($q, $usadas)) . ' FROM ' . $q($destino->tabela))->fetchAll();

    // Quantos registros desta tela já apontam para cada selo
    $ja = db()->query('SELECT ' . $q($campo) . ' AS v, COUNT(*) AS n FROM ' . $q($crud->tabela) . ' GROUP BY ' . $q($campo))
               ->fetchAll(PDO::FETCH_KEY_PAIR);
    $jaNorm = [];
    foreach ($ja as $v => $n) {
        $jaNorm[mb_strtoupper(trim((string) $v))] = ($jaNorm[mb_strtoupper(trim((string) $v))] ?? 0) + (int) $n;
    }

    $aCompacto = vinculo_compacto($atual);
    $aSemZeros = vinculo_sem_zeros($atual);
    $cpfs  = [];
    foreach ($mapaCpf as $o => $d) {
        $dig = preg_replace('/\D/', '', (string) ($linha[$o] ?? ''));
        if (strlen($dig) >= 11) {
            $cpfs[$d] = $dig;
        }
    }
    $nomes = [];
    foreach ($mapaNome as $o => $ds) {
        $n = vinculo_nome((string) ($linha[$o] ?? ''));
        if (mb_strlen($n) >= 5) {
            $nomes[] = [$n, $ds];
        }
    }

    $sugestoes = [];
    foreach ($todos as $d) {
        $valor   = (string) $d[$chave];
        if ($valor === $atual) {
            continue;
        }
        $pontos  = 0;
        $motivos = [];

        // Selo igual ou quase igual
        $dCompacto = vinculo_compacto($valor);
        if ($aCompacto !== '' && ($dCompacto === $aCompacto || vinculo_sem_zeros($valor) === $aSemZeros)) {
            $pontos += 100;
            $motivos[] = 'Mesmo selo escrito de outro jeito';
        } elseif (strlen($aCompacto) >= 4) {
            $dist = levenshtein(substr($aCompacto, 0, 255), substr($dCompacto, 0, 255));
            if ($dist === 1) {
                $pontos += 70;
                $motivos[] = 'Selo com 1 caractere diferente';
            } elseif ($dist === 2) {
                $pontos += 50;
                $motivos[] = 'Selo com 2 caracteres diferentes';
            }
        }

        // Mesmo CPF
        foreach ($cpfs as $col => $dig) {
            if (preg_replace('/\D/', '', (string) ($d[$col] ?? '')) === $dig) {
                $pontos += 90;
                $motivos[] = 'Mesmo CPF';
                break;
            }
        }

        // Mesmo nome / nome parecido
        foreach ($nomes as [$n, $cols]) {
            $melhor = 0.0;
            foreach ($cols as $col) {
                $dn = vinculo_nome((string) ($d[$col] ?? ''));
                if ($dn === '') {
                    continue;
                }
                if ($dn === $n) {
                    $melhor = 100.0;
                    break;
                }
                similar_text($n, $dn, $pct);
                $melhor = max($melhor, $pct);
            }
            if ($melhor >= 100) {
                $pontos += 80;
                $motivos[] = 'Mesmo nome';
            } elseif ($melhor >= 85) {
                $pontos += 60;
                $motivos[] = 'Nome parecido';
            }
        }

        if ($pontos === 0) {
            continue;
        }
        $vinculados = $jaNorm[mb_strtoupper(trim($valor))] ?? 0;
        if ($vinculados === 0) {
            $pontos += 5;
        }
        $sugestoes[] = ['valor' => $valor, 'linha' => $d, 'pontos' => $pontos, 'motivos' => $motivos, 'vinculados' => $vinculados];
    }

    usort($sugestoes, fn($a, $b) => $b['pontos'] <=> $a['pontos'] ?: strcmp($a['valor'], $b['valor']));

    return [
        'destino'       => $destino,
        'campo'         => $campo,
        'colunas'       => $colunas,
        'sugestoes'     => array_slice($sugestoes, 0, $limite),
        'ja_vinculados' => $jaNorm,
        'todos'         => array_slice(array_column($todos, $chave), 0, 5000),
    ];
}
