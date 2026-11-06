<?php
/**
 * Relatório geral: uma linha por domicílio, com os dados do lote (selagem) e,
 * quando houver, do cadastro sociojurídico e da caracterização ligados pelo selo.
 *
 * - Lotes sem domicílio entram com uma linha (domicílio em branco).
 * - Domicílios cujo lote não existe também entram (lote em branco).
 * - Se um selo tiver mais de um cadastro sociojurídico ou caracterização, entra o
 *   mais recente (maior ID do Kobo) e a coluna de situação mostra "N cadastros".
 * - Cadastros cujo selo não existe em Domicílios não entram (ver "Corrigir selo").
 */

declare(strict_types=1);

class Relatorio
{
    /** prefixo da coluna => tela (config/tabelas.php) */
    public const TELAS = ['s' => 'selagem', 'd' => 'domicilios', 'so' => 'socio', 'c' => 'caracterizacao'];

    public const TITULOS = [
        's'   => 'Lote (Selagem)',
        'd'   => 'Domicílio',
        'sit' => 'Situação',
        'so'  => 'Sócio jurídico',
        'c'   => 'Caracterização',
    ];

    /** Colunas do modelo "Resumido" */
    public const RESUMO = [
        's'  => ['id_submissao', 'rua_zona_setor', 'numero_lote', 'endereco_oficial_completo', 'tipo_ocupacao_lote',
                 'qtd_domicilios_lote', 'nome_selador', 'data_formulario'],
        'd'  => ['numero_selo', 'nome_principal_morador', 'cpf', 'telefone', 'tipo_ocupacao_imovel', 'area_lote_m2'],
        'so' => ['id_submissao', 'r1_nome', 'r1_cpf', 'r1_estado_civil', 'numero_residentes', 'renda_mensal_titular_1',
                 'tempo_ocupacao', 'nome_cadastrador', 'data_registro'],
        'c'  => ['id_submissao', 'quantidade_comodos', 'possui_banheiro', 'agua_acesso', 'esgotamento_sanitario',
                 'coleta_lixo', 'responsavel_coleta', 'data_registro'],
    ];

    /** Colunas da tabela na tela (enxuta; as planilhas trazem o resto) */
    public const TELA = [
        's'  => ['id_submissao', 'rua_zona_setor', 'numero_lote'],
        'd'  => ['numero_selo', 'nome_principal_morador', 'cpf'],
        'so' => ['id_submissao', 'r1_nome'],
        'c'  => ['id_submissao'],
    ];

    /** Rótulos curtos para a tabela da tela */
    public const ROTULOS_TELA = [
        's__id_submissao' => 'ID Kobo', 's__rua_zona_setor' => 'Rua / Setor', 's__numero_lote' => 'Lote',
        'd__numero_selo' => 'Selo', 'd__nome_principal_morador' => 'Morador', 'd__cpf' => 'CPF',
        'sit_situacao' => 'Pendências', 'sit_socio' => 'Sócio', 'sit_caract' => 'Caract.',
        'so__id_submissao' => 'ID Kobo', 'so__r1_nome' => 'Titular',
        'c__id_submissao' => 'ID Kobo', 'c__quantidade_comodos' => 'Cômodos',
    ];

    /** Situações para o filtro (as de sócio/caracterização só aparecem se o perfil vê essas telas) */
    public const SITUACOES = [
        'completo'           => 'Completo',
        'pendente'           => 'Com alguma pendência',
        'sem_domicilio'      => 'Lote sem domicílio',
        'sem_socio'          => 'Domicílio sem sócio jurídico',
        'sem_caracterizacao' => 'Domicílio sem caracterização',
        'duplicado'          => 'Selo com cadastro duplicado',
        'sem_lote'           => 'Domicílio sem lote',
    ];

    /** @var array<string,Crud> prefixo => Crud (só as telas que o perfil pode ver) */
    public array $cruds = [];

    public function __construct()
    {
        foreach (self::TELAS as $p => $tela) {
            if (isset($GLOBALS['TABELAS'][$tela]) && pode($tela, 'ver')) {
                $this->cruds[$p] = new Crud($tela, $GLOBALS['TABELAS'][$tela]);
            }
        }
        if (!isset($this->cruds['s'], $this->cruds['d'])) {
            throw new RuntimeException('O relatório precisa de acesso às telas de Selagens e Domicílios.');
        }
    }

    public function tem(string $p): bool
    {
        return isset($this->cruds[$p]);
    }

    public function situacoes(): array
    {
        $s = self::SITUACOES;
        if (!$this->tem('so')) {
            unset($s['sem_socio']);
        }
        if (!$this->tem('c')) {
            unset($s['sem_caracterizacao']);
        }
        if (!$this->tem('so') && !$this->tem('c')) {
            unset($s['duplicado']);
        }
        return $s;
    }

    /* ================================================================ */
    /* Colunas                                                           */
    /* ================================================================ */

    /**
     * Colunas na ordem do relatório.
     * @return array<int,array{id:string,p:string,grupo:string,label:string,campo:?string}>
     */
    public function colunas(string $modelo): array
    {
        $saida = [];
        foreach (['s', 'd', 'sit', 'so', 'c'] as $p) {
            if ($p === 'sit') {
                $saida[] = ['id' => 'sit_situacao', 'p' => 'sit', 'grupo' => self::TITULOS['sit'], 'label' => 'Pendências', 'campo' => null];
                if ($this->tem('so')) {
                    $saida[] = ['id' => 'sit_socio', 'p' => 'sit', 'grupo' => self::TITULOS['sit'], 'label' => 'Sócio jurídico', 'campo' => null];
                }
                if ($this->tem('c')) {
                    $saida[] = ['id' => 'sit_caract', 'p' => 'sit', 'grupo' => self::TITULOS['sit'], 'label' => 'Caracterização', 'campo' => null];
                }
                continue;
            }
            if (!$this->tem($p)) {
                continue;
            }
            foreach ($this->camposDoGrupo($p, $modelo) as $c) {
                $saida[] = ['id' => $p . '__' . $c, 'p' => $p, 'grupo' => self::TITULOS[$p],
                            'label' => $this->cruds[$p]->campos[$c]['label'], 'campo' => $c];
            }
        }
        if ($modelo === 'tela') {
            foreach ($saida as &$col) {
                $col['label'] = self::ROTULOS_TELA[$col['id']] ?? $col['label'];
            }
            unset($col);
        }
        return $saida;
    }

    private function camposDoGrupo(string $p, string $modelo): array
    {
        $crud = $this->cruds[$p];
        $todos = array_keys(array_filter($crud->campos, fn($c) => $c['tipo'] !== 'senha'));
        if ($modelo === 'completo') {
            return $todos;
        }
        $lista = $modelo === 'tela' ? self::TELA[$p] : self::RESUMO[$p];
        return array_values(array_filter($lista, fn($c) => in_array($c, $todos, true)));
    }

    /* ================================================================ */
    /* Consulta                                                          */
    /* ================================================================ */

    private static function q(string $id): string
    {
        return '`' . str_replace('`', '``', $id) . '`';
    }

    /**
     * SELECT completo (sem filtros).
     * As junções com sócio jurídico e caracterização usam subconsultas pelo selo, que
     * aproveitam os índices em codigo_selo (ver "Índices recomendados" no LEIAME.md).
     */
    private function sqlBase(string $modelo): string
    {
        $S = $this->cruds['s'];
        $D = $this->cruds['d'];
        $sel = [];
        foreach (['s' => $S, 'd' => $D] as $a => $crud) {
            foreach (array_keys($crud->campos) as $c) {
                $sel[] = $a . '.' . self::q($c) . ' AS ' . self::q($a . '__' . $c);
            }
        }
        $joins = '';
        $selo  = self::q('codigo_selo');
        $num   = 'd.' . self::q('numero_selo');
        foreach (['so', 'c'] as $p) {
            if (!$this->tem($p)) {
                continue;
            }
            $crud = $this->cruds[$p];
            $t    = self::q($crud->tabela);
            $pk   = self::q($crud->pk);
            $campos = array_values(array_unique(array_merge([$crud->pk], $this->camposDoGrupo($p, $modelo))));
            foreach ($campos as $c) {
                $sel[] = "$p." . self::q($c) . ' AS ' . self::q($p . '__' . $c);
            }
            // Quantos cadastros o selo tem, e o mais recente (maior ID do Kobo) entra na linha
            $sel[]  = "(SELECT COUNT(*) FROM $t x WHERE x.$selo = $num) AS " . self::q($p . '__qtd');
            $joins .= " LEFT JOIN $t $p ON $p.$pk = (SELECT MAX(x.$pk) FROM $t x WHERE x.$selo = $num)";
        }
        $sel = implode(', ', $sel);
        $tS  = self::q($S->tabela);
        $tD  = self::q($D->tabela);
        $pkS = self::q($S->pk);
        $pai = self::q('id_submissao_pai');

        // Lotes (com ou sem domicílio) + domicílios cujo lote não existe
        return "SELECT $sel FROM $tS s LEFT JOIN $tD d ON d.$pai = s.$pkS$joins"
             . " UNION ALL "
             . "SELECT $sel FROM $tD d LEFT JOIN $tS s ON s.$pkS = d.$pai$joins WHERE s.$pkS IS NULL";
    }

    /** Condição SQL de cada situação (sobre o alias z). */
    private function sqlSituacao(string $situacao): ?string
    {
        $temDom  = 'z.`d__numero_selo` IS NOT NULL';
        $semSoc  = $this->tem('so') ? "($temDom AND z.`so__id_submissao` IS NULL)" : '0';
        $semCar  = $this->tem('c') ? "($temDom AND z.`c__id_submissao` IS NULL)" : '0';
        $dup     = [];
        if ($this->tem('so')) {
            $dup[] = 'z.`so__qtd` > 1';
        }
        if ($this->tem('c')) {
            $dup[] = 'z.`c__qtd` > 1';
        }
        $dupSql   = $dup ? '(' . implode(' OR ', $dup) . ')' : '0';
        $semDom   = 'z.`d__numero_selo` IS NULL';
        $semLote  = 'z.`s__id_submissao` IS NULL';
        $completo = "($temDom AND NOT $semLote AND NOT $semSoc AND NOT $semCar)";

        return match ($situacao) {
            'completo'           => $completo,
            'pendente'           => "NOT $completo",
            'sem_domicilio'      => $semDom,
            'sem_socio'          => $semSoc,
            'sem_caracterizacao' => $semCar,
            'duplicado'          => $dupSql,
            'sem_lote'           => $semLote,
            default              => null,
        };
    }

    /** @return array{0:string,1:array} [WHERE, parâmetros] */
    private function sqlFiltros(array $f, bool $comSituacao = true): array
    {
        $where  = [];
        $params = [];

        $busca = trim((string) ($f['q'] ?? ''));
        if ($busca !== '') {
            $cols = ['s__id_submissao', 's__rua_zona_setor', 's__numero_lote', 's__endereco_oficial_completo', 's__nome_selador',
                     'd__numero_selo', 'd__nome_entrevistado', 'd__nome_principal_morador', 'd__cpf', 'd__telefone'];
            if ($this->tem('so')) {
                array_push($cols, 'so__r1_nome', 'so__r1_cpf', 'so__id_submissao');
            }
            if ($this->tem('c')) {
                $cols[] = 'c__id_submissao';
            }
            $ors = [];
            foreach ($cols as $c) {
                $ors[]    = 'CAST(z.' . self::q($c) . ' AS CHAR) LIKE ?';
                $params[] = '%' . $busca . '%';
            }
            $where[] = '(' . implode(' OR ', $ors) . ')';
        }

        $setor = (string) ($f['setor'] ?? '');
        if ($setor !== '') {
            $where[]  = 'z.`s__rua_zona_setor` = ?';
            $params[] = $setor;
        }

        if ($comSituacao && isset($this->situacoes()[$f['situacao'] ?? ''])) {
            $where[] = $this->sqlSituacao($f['situacao']);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function ordem(): string
    {
        // Lotes na ordem de rua e número (natural: 2 antes de 10); domicílios sem lote no fim
        return ' ORDER BY (z.`s__id_submissao` IS NULL), z.`s__rua_zona_setor`, LENGTH(z.`s__numero_lote`), z.`s__numero_lote`,'
             . ' z.`s__id_submissao`, z.`d__numero_selo`';
    }

    public function contar(array $f): int
    {
        [$w, $p] = $this->sqlFiltros($f);
        $st = db()->prepare('SELECT COUNT(*) FROM (' . $this->sqlBase('resumido') . ') z' . $w);
        $st->execute($p);
        return (int) $st->fetchColumn();
    }

    /** Linhas de uma página (tela). */
    public function linhas(array $f, string $modelo, int $limite, int $offset): array
    {
        [$w, $p] = $this->sqlFiltros($f);
        $st = db()->prepare('SELECT * FROM (' . $this->sqlBase($modelo) . ') z' . $w . $this->ordem()
                          . ' LIMIT ' . max(1, $limite) . ' OFFSET ' . max(0, $offset));
        $st->execute($p);
        return $st->fetchAll();
    }

    /** Todas as linhas, uma de cada vez (exportação, sem carregar tudo na memória). */
    public function percorrer(array $f, string $modelo): Generator
    {
        [$w, $p] = $this->sqlFiltros($f);
        $pdo = db();
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        try {
            $st = $pdo->prepare('SELECT * FROM (' . $this->sqlBase($modelo) . ') z' . $w . $this->ordem());
            $st->execute($p);
            while ($linha = $st->fetch()) {
                yield $linha;
            }
            $st->closeCursor();
        } finally {
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
    }

    /** Totais (respeitam pesquisa e setor, mas não a situação escolhida). */
    public function resumo(array $f): array
    {
        [$w, $p] = $this->sqlFiltros($f, false);
        $col = fn(string $s) => 'SUM(' . ($this->sqlSituacao($s) ?? '0') . ')';
        $sql = 'SELECT COUNT(*) AS linhas, COUNT(DISTINCT z.`s__id_submissao`) AS lotes,'
             . ' SUM(z.`d__numero_selo` IS NOT NULL) AS domicilios';
        foreach (array_keys($this->situacoes()) as $s) {
            $sql .= ', ' . $col($s) . ' AS ' . self::q($s);
        }
        $st = db()->prepare($sql . ' FROM (' . $this->sqlBase('resumido') . ') z' . $w);
        $st->execute($p);
        return array_map('intval', $st->fetch() ?: []);
    }

    /**
     * Índices que o relatório precisa e que não existem no banco (sem eles a tela
     * passa de segundos para minutos com alguns milhares de registros).
     * @return string[] comandos CREATE INDEX que faltam
     */
    public function indicesFaltando(): array
    {
        $necessarios = [
            ['d', 'id_submissao_pai', 'idx_domicilio_pai'],
            ['so', 'codigo_selo', 'idx_socio_selo'],
            ['c', 'codigo_selo', 'idx_caract_selo'],
        ];
        $st = db()->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
                              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? AND SEQ_IN_INDEX = 1');
        $faltam = [];
        foreach ($necessarios as [$p, $coluna, $nome]) {
            if (!$this->tem($p)) {
                continue;
            }
            $tabela = $this->cruds[$p]->tabela;
            $st->execute([$tabela, $coluna]);
            if ((int) $st->fetchColumn() === 0) {
                $faltam[] = "CREATE INDEX $nome ON $tabela ($coluna);";
            }
        }
        return $faltam;
    }

    public function setores(): array
    {
        return db()->query('SELECT DISTINCT `rua_zona_setor` FROM ' . self::q($this->cruds['s']->tabela)
                         . " WHERE `rua_zona_setor` IS NOT NULL AND `rua_zona_setor` <> '' ORDER BY 1")
                   ->fetchAll(PDO::FETCH_COLUMN);
    }

    /* ================================================================ */
    /* Valores                                                           */
    /* ================================================================ */

    /** Texto da situação de um vínculo (sócio/caracterização). */
    private function situacaoVinculo(array $l, string $p): string
    {
        if (($l['d__numero_selo'] ?? null) === null) {
            return '—';
        }
        $n = (int) ($l[$p . '__qtd'] ?? 0);
        return $n === 0 ? 'Não' : ($n === 1 ? 'Sim' : $n . ' cadastros');
    }

    /** Lista de pendências da linha (vazia = completo). */
    public function pendencias(array $l): array
    {
        $saida = [];
        if (($l['s__id_submissao'] ?? null) === null) {
            $saida[] = 'Domicílio sem lote';
        }
        if (($l['d__numero_selo'] ?? null) === null) {
            $saida[] = 'Lote sem domicílio';
            return $saida;
        }
        if ($this->tem('so') && (int) ($l['so__qtd'] ?? 0) === 0) {
            $saida[] = 'Sem sócio jurídico';
        }
        if ($this->tem('c') && (int) ($l['c__qtd'] ?? 0) === 0) {
            $saida[] = 'Sem caracterização';
        }
        if (($this->tem('so') && (int) ($l['so__qtd'] ?? 0) > 1) || ($this->tem('c') && (int) ($l['c__qtd'] ?? 0) > 1)) {
            $saida[] = 'Cadastro duplicado';
        }
        return $saida;
    }

    /**
     * Valor tipado para exportação.
     * @return array{0:string,1:mixed} [tipo (texto|numero|decimal|data|datahora), valor]
     */
    public function valor(array $col, array $l): array
    {
        switch ($col['id']) {
            case 'sit_situacao':
                $p = $this->pendencias($l);
                return ['texto', $p ? implode('; ', $p) : 'Completo'];
            case 'sit_socio':
                return ['texto', $this->situacaoVinculo($l, 'so')];
            case 'sit_caract':
                return ['texto', $this->situacaoVinculo($l, 'c')];
        }
        $crud = $this->cruds[$col['p']];
        $c    = $crud->campos[$col['campo']];
        $v    = $l[$col['id']] ?? null;
        if ($v === null || $v === '') {
            return ['texto', ''];
        }
        if (in_array($c['tipo'], ['simnao', 'select', 'fk'], true)) {
            return ['texto', $crud->exibir($col['campo'], $v)];
        }
        return match ($c['base']) {
            'inteiro'            => ['numero', $v],
            'decimal'            => [$c['escala'] <= 2 ? 'decimal' : 'numero', $v],   // R$ com 2 casas; GPS completo
            'data'               => ['data', (string) $v],
            'datahora'           => ['datahora', (string) $v],
            default              => ['texto', (string) $v],
        };
    }

    /** Valor formatado para a tela. */
    public function texto(array $col, array $l): string
    {
        if ($col['campo'] === null) {
            return (string) $this->valor($col, $l)[1];
        }
        return $this->cruds[$col['p']]->exibir($col['campo'], $l[$col['id']] ?? null);
    }
}
