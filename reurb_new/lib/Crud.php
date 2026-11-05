<?php
/**
 * Motor genérico de CRUD.
 * Junta a estrutura real da tabela (INFORMATION_SCHEMA) com a configuração
 * de config/tabelas.php e faz listagem, leitura, inclusão, alteração,
 * exclusão e gravação na tabela auditoria.
 */

declare(strict_types=1);

class ErroValidacao extends RuntimeException
{
    public array $erros;

    public function __construct(array $erros, string $mensagem = 'Verifique os campos destacados.')
    {
        parent::__construct($mensagem);
        $this->erros = $erros;
    }
}

class Crud
{
    public string $chave;
    public array  $cfg;
    public string $tabela;
    public string $pk;
    public bool   $somenteLeitura;

    /** @var array<string,array> campos na ordem de exibição */
    public array $campos = [];

    private static array $cacheColunas = [];
    private static array $cacheFk = [];

    public function __construct(string $chave, array $cfg)
    {
        $this->chave          = $chave;
        $this->cfg            = $cfg;
        $this->tabela         = $cfg['tabela'];
        $this->pk             = $cfg['pk'];
        $this->somenteLeitura = !empty($cfg['somente_leitura']);
        $this->montarCampos();
    }

    /* ================================================================ */
    /* Estrutura                                                         */
    /* ================================================================ */

    private function lerColunasDoBanco(): array
    {
        if (!isset(self::$cacheColunas[$this->tabela])) {
            $st = db()->prepare(
                'SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_PRECISION, NUMERIC_SCALE,
                        IS_NULLABLE, COLUMN_KEY, EXTRA, COLUMN_DEFAULT
                   FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
               ORDER BY ORDINAL_POSITION'
            );
            $st->execute([$this->tabela]);
            self::$cacheColunas[$this->tabela] = $st->fetchAll();
            if (!self::$cacheColunas[$this->tabela]) {
                throw new RuntimeException("A tabela {$this->tabela} não foi encontrada no banco.");
            }
        }
        return self::$cacheColunas[$this->tabela];
    }

    private function montarCampos(): void
    {
        $config = $this->cfg['campos'] ?? [];
        $doBanco = [];
        foreach ($this->lerColunasDoBanco() as $c) {
            $doBanco[$c['COLUMN_NAME']] = $c;
        }

        // Ordem: primeiro a ordem da configuração, depois as demais colunas do banco.
        $ordem = array_values(array_unique(array_merge(
            array_keys(array_intersect_key($config, $doBanco)),
            array_keys($doBanco)
        )));

        foreach ($ordem as $nome) {
            $col = $doBanco[$nome];
            $c   = $config[$nome] ?? [];
            $dt  = strtolower($col['DATA_TYPE']);

            $base = match (true) {
                in_array($dt, ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'year'], true) => 'inteiro',
                in_array($dt, ['decimal', 'float', 'double', 'numeric'], true)                              => 'decimal',
                $dt === 'date'                                                                              => 'data',
                in_array($dt, ['datetime', 'timestamp'], true)                                              => 'datahora',
                in_array($dt, ['text', 'mediumtext', 'longtext', 'tinytext'], true)                         => 'textarea',
                default                                                                                     => 'texto',
            };

            $tipo = $c['tipo'] ?? null;
            if ($tipo === null && (str_starts_with($nome, 'foto_') || str_contains($nome, '_foto_'))) {
                $tipo = 'foto';
            }

            $autoInc = str_contains(strtolower((string) $col['EXTRA']), 'auto_increment');
            $padrao  = self::lerPadrao($col['COLUMN_DEFAULT']);
            // NOT NULL sem valor padrão = obrigatório (regra vem do próprio banco)
            $notNull = $col['IS_NULLABLE'] === 'NO' && !$autoInc && $padrao === null;

            $this->campos[$nome] = [
                'nome'            => $nome,
                'label'           => $c['label'] ?? self::rotulo($nome),
                'base'            => $base,
                'tipo'            => $tipo ?? $base,
                'col'             => (int) ($c['col'] ?? (($tipo ?? $base) === 'textarea' || $tipo === 'json' ? 12 : 6)),
                'readonly'        => !empty($c['readonly']),
                'readonly_edicao' => !empty($c['readonly_edicao']) || $nome === $this->pk,
                'obrigatorio'     => !empty($c['obrigatorio']) || $notNull || ($nome === $this->pk && !$autoInc),
                'auto_increment'  => $autoInc,
                'padrao'          => $padrao,
                'maxlength'       => $col['CHARACTER_MAXIMUM_LENGTH'] !== null && $base === 'texto' ? (int) $col['CHARACTER_MAXIMUM_LENGTH'] : null,
                'escala'          => $col['NUMERIC_SCALE'] !== null ? (int) $col['NUMERIC_SCALE'] : 0,
                'opcoes'          => $c['opcoes'] ?? null,
                'fk'              => $c['fk'] ?? null,
                'ref'             => $c['ref'] ?? null,
            ];
        }

        if (!isset($this->campos[$this->pk])) {
            throw new RuntimeException("Chave primária {$this->pk} não existe em {$this->tabela}.");
        }
    }

    /**
     * Valor DEFAULT da coluna, normalizado entre MySQL 8 e MariaDB
     * (MariaDB devolve 'NULL' e strings entre aspas). Funções como now() são ignoradas.
     */
    private static function lerPadrao($bruto): ?string
    {
        if ($bruto === null) {
            return null;
        }
        $v = trim((string) $bruto);
        if ($v === '' || strtoupper($v) === 'NULL' || str_contains($v, '(') || stripos($v, 'current_timestamp') !== false) {
            return null;
        }
        if (strlen($v) >= 2 && $v[0] === "'" && substr($v, -1) === "'") {
            $v = str_replace("''", "'", substr($v, 1, -1));
        }
        return $v;
    }

    /** Valores iniciais de um registro novo, a partir do DEFAULT das colunas. */
    public function valoresPadrao(): array
    {
        $saida = [];
        foreach ($this->campos as $nome => $c) {
            if ($c['padrao'] !== null) {
                $saida[$nome] = $c['padrao'];
            }
        }
        return $saida;
    }

    /** Gera um rótulo legível a partir do nome da coluna (usado nas colunas não configuradas). */
    public static function rotulo(string $nome): string
    {
        $siglas = ['cpf' => 'CPF', 'rg' => 'RG', 'nis' => 'NIS', 'iptu' => 'IPTU', 'lgpd' => 'LGPD', 'uuid' => 'UUID',
                   'id' => 'ID', 'pcd' => 'PcD', 'm2' => 'm²', 'r1' => 'R1', 'r2' => 'R2', 'gps' => 'GPS',
                   // acentuação das palavras mais comuns nos formulários do Kobo
                   'observacoes' => 'Observações', 'observacao' => 'Observação', 'agua' => 'Água', 'condicao' => 'Condição',
                   'condicoes' => 'Condições', 'comodo' => 'Cômodo', 'comodos' => 'Cômodos', 'numero' => 'Número',
                   'area' => 'Área', 'servico' => 'Serviço', 'servicos' => 'Serviços', 'situacao' => 'Situação',
                   'ocupacao' => 'Ocupação', 'iluminacao' => 'Iluminação', 'ventilacao' => 'Ventilação',
                   'pavimentacao' => 'Pavimentação', 'saude' => 'Saúde', 'familia' => 'Família', 'responsavel' => 'Responsável',
                   'eletrica' => 'Elétrica', 'hidraulica' => 'Hidráulica', 'publica' => 'Pública', 'publico' => 'Público',
                   'nao' => 'Não', 'ha' => 'Há', 'tem' => 'Tem', 'de' => 'de', 'da' => 'da', 'do' => 'do', 'e' => 'e',
                   'com' => 'com', 'sem' => 'sem', 'por' => 'por', 'no' => 'no', 'na' => 'na'];
        $palavras = array_map(
            fn($p) => $siglas[$p] ?? mb_convert_case($p, MB_CASE_TITLE, 'UTF-8'),
            explode('_', strtolower($nome))
        );
        return implode(' ', $palavras);
    }

    /** Agrupa os campos nas seções configuradas; o que sobrar vai para "Outros dados". */
    public function secoes(): array
    {
        $secoes = $this->cfg['secoes'] ?? null;
        if (!$secoes) {
            return ['' => array_keys($this->campos)];
        }
        $usados = [];
        $saida  = [];
        foreach ($secoes as $titulo => $lista) {
            $lista = array_values(array_filter($lista, fn($c) => isset($this->campos[$c])));
            if ($lista) {
                $saida[$titulo] = $lista;
                $usados = array_merge($usados, $lista);
            }
        }
        $resto = array_values(array_diff(array_keys($this->campos), $usados));
        if ($resto) {
            $saida['Outros dados'] = $resto;
        }
        return $saida;
    }

    /**
     * Agrupa as seções em abas, conforme 'abas' na configuração.
     * Seções que não estiverem em nenhuma aba vão para uma aba "Outros dados".
     * @param string[] $secoesExistentes títulos das seções que têm campos
     * @return array|null [['titulo' => ..., 'secoes' => [...]], ...] ou null se a tela não usa abas
     */
    public function abas(array $secoesExistentes): ?array
    {
        $config = $this->cfg['abas'] ?? null;
        if (!$config) {
            return null;
        }
        $saida  = [];
        $usadas = [];
        foreach ($config as $titulo => $secoes) {
            $secoes = array_values(array_intersect($secoes, $secoesExistentes));
            if ($secoes) {
                $saida[] = ['titulo' => $titulo, 'secoes' => $secoes];
                $usadas  = array_merge($usadas, $secoes);
            }
        }
        $resto = array_values(array_diff($secoesExistentes, $usadas));
        if ($resto) {
            $saida[] = ['titulo' => 'Outros dados', 'secoes' => $resto];
        }
        return count($saida) > 1 ? $saida : null;
    }

    public function colunasLista(): array
    {
        $lista = $this->cfg['lista'] ?? array_slice(array_keys($this->campos), 0, 6);
        $lista = array_values(array_filter($lista, fn($c) => isset($this->campos[$c])));
        if (!in_array($this->pk, $lista, true)) {
            array_unshift($lista, $this->pk);
        }
        return $lista;
    }

    /* ================================================================ */
    /* Consulta                                                          */
    /* ================================================================ */

    private static function q(string $identificador): string
    {
        return '`' . str_replace('`', '``', $identificador) . '`';
    }

    public function filtrosPermitidos(): array
    {
        return array_values(array_filter($this->cfg['filtros'] ?? [], fn($c) => isset($this->campos[$c])));
    }

    /** Coluna da tabela principal da listagem (alias t). */
    private static function qt(string $coluna): string
    {
        return 't.' . self::q($coluna);
    }

    /**
     * Indicadores de registros vinculados (config 'indicadores'), já validados contra o banco.
     * Ex.: selagem sem domicílio; domicílio sem cadastro sociojurídico.
     */
    public function indicadores(): array
    {
        $saida = [];
        foreach ($this->cfg['indicadores'] ?? [] as $ind) {
            $tela = $ind['tela'] ?? '';
            if (!isset($GLOBALS['TABELAS'][$tela]) || !isset($this->campos[$ind['campo_local'] ?? ''])) {
                continue;
            }
            $outro = new Crud($tela, $GLOBALS['TABELAS'][$tela]);
            if (!isset($outro->campos[$ind['coluna'] ?? ''])) {
                continue;
            }
            $ind['tabela_rel'] = $outro->tabela;
            $ind['alias']      = '__ind_' . preg_replace('/\W/', '', $ind['id']);
            $ind['declarado']  = isset($ind['declarado'], $this->campos[$ind['declarado']]) ? $ind['declarado'] : null;
            $saida[$ind['id']] = $ind;
        }
        return $saida;
    }

    /** SQL "não existe registro vinculado" para um indicador. */
    private function sqlSemVinculo(array $ind): string
    {
        return 'NOT EXISTS (SELECT 1 FROM ' . self::q($ind['tabela_rel']) . ' r WHERE r.' . self::q($ind['coluna'])
             . ' = ' . self::qt($ind['campo_local']) . ')';
    }

    /**
     * Condições de pesquisa, filtros e pendência.
     * $pendencia: id de um indicador, 'qualquer' (falta algum) ou '' (sem filtro).
     * @return array{0: string, 1: array} [sql do WHERE, parâmetros]
     */
    private function montarWhere(string $busca, array $filtros, string $pendencia = ''): array
    {
        $where  = [];
        $params = [];

        foreach ($filtros as $campo => $valor) {
            if (in_array($campo, $this->filtrosPermitidos(), true) && $valor !== '' && $valor !== null) {
                $where[]  = self::qt($campo) . ' = ?';
                $params[] = $valor;
            }
        }

        $busca = trim($busca);
        if ($busca !== '') {
            $ors = [];
            foreach ($this->cfg['busca'] ?? [$this->pk] as $campo) {
                if (isset($this->campos[$campo])) {
                    $ors[]    = 'CAST(' . self::qt($campo) . ' AS CHAR) LIKE ?';
                    $params[] = '%' . $busca . '%';
                }
            }
            if ($ors) {
                $where[] = '(' . implode(' OR ', $ors) . ')';
            }
        }

        $indicadores = $this->indicadores();
        if ($pendencia === 'qualquer' && $indicadores) {
            $where[] = '(' . implode(' OR ', array_map([$this, 'sqlSemVinculo'], $indicadores)) . ')';
        } elseif ($pendencia !== '' && isset($indicadores[$pendencia])) {
            $where[] = $this->sqlSemVinculo($indicadores[$pendencia]);
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }

    /**
     * Quantos registros (dentro da pesquisa/filtros atuais) estão sem cada vínculo.
     * @return array<string,int> [id do indicador => total, 'qualquer' => total]
     */
    public function contarPendencias(string $busca = '', array $filtros = []): array
    {
        $indicadores = $this->indicadores();
        if (!$indicadores) {
            return [];
        }
        $saida = [];
        $ids = array_keys($indicadores);
        if (count($ids) > 1) {
            $ids[] = 'qualquer';
        }
        foreach ($ids as $id) {
            [$sqlWhere, $params] = $this->montarWhere($busca, $filtros, $id);
            $st = db()->prepare('SELECT COUNT(*) FROM ' . self::q($this->tabela) . ' t' . $sqlWhere);
            $st->execute($params);
            $saida[$id] = (int) $st->fetchColumn();
        }
        return $saida;
    }

    /**
     * @return array{0: array, 1: int} [linhas, total]
     */
    public function listar(string $busca = '', array $filtros = [], ?string $ordem = null, string $dir = 'asc',
                           int $pagina = 1, int $porPagina = 25, string $pendencia = ''): array
    {
        [$sqlWhere, $params] = $this->montarWhere($busca, $filtros, $pendencia);

        $st = db()->prepare('SELECT COUNT(*) FROM ' . self::q($this->tabela) . ' t' . $sqlWhere);
        $st->execute($params);
        $total = (int) $st->fetchColumn();

        // Ordenação: só colunas reais da tabela
        if ($ordem === null || !isset($this->campos[$ordem])) {
            [$ordem, $dir] = array_pad(preg_split('/\s+/', trim($this->cfg['ordem'] ?? $this->pk)), 2, 'asc');
            if (!isset($this->campos[$ordem])) {
                $ordem = $this->pk;
            }
        }
        $dir = strtolower($dir) === 'desc' ? 'DESC' : 'ASC';

        $colunas = array_map([self::class, 'qt'], $this->colunasLista());
        // Quantidade de registros vinculados de cada indicador (e o valor declarado, se houver)
        foreach ($this->indicadores() as $ind) {
            $colunas[] = '(SELECT COUNT(*) FROM ' . self::q($ind['tabela_rel']) . ' r WHERE r.' . self::q($ind['coluna'])
                       . ' = ' . self::qt($ind['campo_local']) . ') AS ' . self::q($ind['alias']);
            if ($ind['declarado'] !== null && !in_array($ind['declarado'], $this->colunasLista(), true)) {
                $colunas[] = self::qt($ind['declarado']);
            }
        }
        $porPagina = max(1, $porPagina);
        $offset    = (max(1, $pagina) - 1) * $porPagina;

        $sql = 'SELECT ' . implode(', ', array_unique($colunas)) . ' FROM ' . self::q($this->tabela) . ' t' . $sqlWhere
             . ' ORDER BY ' . self::qt($ordem) . " $dir"
             . ($ordem !== $this->pk ? ', ' . self::qt($this->pk) . " $dir" : '')
             . " LIMIT $porPagina OFFSET $offset";
        $st = db()->prepare($sql);
        $st->execute($params);

        return [$st->fetchAll(), $total];
    }

    public function buscar($id): ?array
    {
        $st = db()->prepare('SELECT * FROM ' . self::q($this->tabela) . ' WHERE ' . self::q($this->pk) . ' = ? LIMIT 1');
        $st->execute([$id]);
        $linha = $st->fetch();
        return $linha ?: null;
    }

    /** Opções de um campo do tipo 'fk' (ex.: perfis para o usuário). */
    public function opcoesFk(string $campo): array
    {
        $fk = $this->campos[$campo]['fk'] ?? null;
        if (!$fk) {
            return [];
        }
        $k = $fk['tabela'] . '|' . $fk['valor'] . '|' . $fk['texto'];
        if (!isset(self::$cacheFk[$k])) {
            $sql = 'SELECT ' . self::q($fk['valor']) . ' AS v, ' . self::q($fk['texto']) . ' AS t FROM ' . self::q($fk['tabela'])
                 . ' ORDER BY ' . self::q($fk['texto']);
            self::$cacheFk[$k] = [];
            foreach (db()->query($sql) as $r) {
                self::$cacheFk[$k][(string) $r['v']] = (string) $r['t'];
            }
        }
        return self::$cacheFk[$k];
    }

    /** Registros de outras tabelas ligados a este (configurados em 'relacionados'). */
    public function relacionados(array $linha): array
    {
        $saida = [];
        foreach ($this->cfg['relacionados'] ?? [] as $rel) {
            $valor = $linha[$rel['campo_local']] ?? null;
            if ($valor === null || $valor === '' || !isset($GLOBALS['TABELAS'][$rel['chave']])) {
                continue;
            }
            $outro = new Crud($rel['chave'], $GLOBALS['TABELAS'][$rel['chave']]);
            if (!isset($outro->campos[$rel['coluna']])) {
                continue;
            }
            // Filtro direto (não depende de 'filtros' da outra tela)
            $st = db()->prepare(
                'SELECT ' . implode(', ', array_map([self::class, 'q'], $outro->colunasLista()))
                . ' FROM ' . self::q($outro->tabela) . ' WHERE ' . self::q($rel['coluna']) . ' = ? LIMIT 100'
            );
            $st->execute([$valor]);
            $saida[] = ['rel' => $rel, 'crud' => $outro, 'valor' => $valor, 'linhas' => $st->fetchAll()];
        }
        return $saida;
    }

    /* ================================================================ */
    /* Gravação                                                          */
    /* ================================================================ */

    /**
     * Valida e normaliza os dados enviados pelo formulário.
     * @return array valores prontos para gravar (somente campos editáveis)
     */
    public function validar(array $entrada, bool $novo, ?array $atual = null): array
    {
        $valores = [];
        $erros   = [];

        foreach ($this->campos as $nome => $c) {
            if ($c['readonly'] || (!$novo && $c['readonly_edicao']) || ($novo && $c['auto_increment'])) {
                continue;
            }
            if ($c['tipo'] === 'json') {
                continue;
            }

            // Na alteração, campo que não veio no envio fica como está no banco
            if (!$novo && !array_key_exists($nome, $entrada)) {
                continue;
            }
            $bruto = $entrada[$nome] ?? null;
            if (is_array($bruto)) {
                $erros[$nome] = 'Valor inválido.';
                continue;
            }
            $v = $bruto === null ? '' : trim((string) $bruto);

            if ($c['tipo'] === 'senha') {
                if ($v === '') {
                    if ($novo) {
                        $erros[$nome] = 'Informe a senha.';
                    }
                    continue; // em edição, senha vazia = manter a atual
                }
                if (mb_strlen($v) < 6) {
                    $erros[$nome] = 'A senha deve ter pelo menos 6 caracteres.';
                    continue;
                }
                // Mesmo formato que o banco já usa, para não quebrar o login do sistema atual
                $valores[$nome] = senha_gerar($v, senha_formato_para_gravar($atual[$nome] ?? null));
                continue;
            }

            if ($v === '') {
                if ($c['obrigatorio']) {
                    $erros[$nome] = 'Campo obrigatório.';
                }
                $valores[$nome] = null;
                continue;
            }

            switch ($c['base']) {
                case 'inteiro':
                    if (!preg_match('/^-?\d+$/', $v)) {
                        $erros[$nome] = 'Informe um número inteiro.';
                        continue 2;
                    }
                    break;
                case 'decimal':
                    if (str_contains($v, ',')) {           // aceita 1.412,50 e 1412,50
                        $v = str_replace(['.', ','], ['', '.'], $v);
                    }
                    if (!is_numeric($v)) {
                        $erros[$nome] = 'Informe um número válido.';
                        continue 2;
                    }
                    break;
                case 'data':
                    $d = self::dataEstrita('!Y-m-d', $v) ?: self::dataEstrita('!d/m/Y', $v);
                    if (!$d) {
                        $erros[$nome] = 'Data inválida.';
                        continue 2;
                    }
                    $v = $d->format('Y-m-d');
                    break;
                case 'datahora':
                    $d = self::dataEstrita('Y-m-d\TH:i:s', $v) ?: self::dataEstrita('Y-m-d\TH:i', $v)
                      ?: self::dataEstrita('Y-m-d H:i:s', $v);
                    if (!$d) {
                        $erros[$nome] = 'Data/hora inválida.';
                        continue 2;
                    }
                    $v = $d->format('Y-m-d H:i:s');
                    break;
                default:
                    if ($c['maxlength'] !== null && mb_strlen($v) > $c['maxlength']) {
                        $erros[$nome] = "Máximo de {$c['maxlength']} caracteres.";
                        continue 2;
                    }
            }

            if ($c['tipo'] === 'fk' && !array_key_exists($v, $this->opcoesFk($nome))) {
                $erros[$nome] = 'Selecione uma opção válida.';
                continue;
            }

            $valores[$nome] = $v;
        }

        if ($erros) {
            throw new ErroValidacao($erros);
        }
        return $valores;
    }

    /** @return mixed valor da chave do registro criado */
    public function inserir(array $entrada)
    {
        $valores = $this->validar($entrada, true);

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $cols = array_keys($valores);
            $sql  = 'INSERT INTO ' . self::q($this->tabela) . ' (' . implode(', ', array_map([self::class, 'q'], $cols)) . ')'
                  . ' VALUES (' . implode(', ', array_fill(0, count($cols), '?')) . ')';
            $pdo->prepare($sql)->execute(array_values($valores));

            $id = $this->campos[$this->pk]['auto_increment'] ? $pdo->lastInsertId() : $valores[$this->pk];
            $this->auditar('INSERT', null, $this->buscar($id));
            $pdo->commit();
            return $id;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw self::traduzirErro($e);
        }
    }

    /** @return bool true se algo mudou */
    public function atualizar($id, array $entrada): bool
    {
        $antes = $this->buscar($id);
        if ($antes === null) {
            throw new RuntimeException('Registro não encontrado.');
        }
        $valores = $this->validar($entrada, false, $antes);

        // Grava só o que mudou
        $mudou = [];
        foreach ($valores as $campo => $novo) {
            if (!self::iguais($antes[$campo], $novo, $this->campos[$campo]['base'])) {
                $mudou[$campo] = $novo;
            }
        }
        if (!$mudou) {
            return false;
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $sets = implode(', ', array_map(fn($c) => self::q($c) . ' = ?', array_keys($mudou)));
            $sql  = 'UPDATE ' . self::q($this->tabela) . " SET $sets WHERE " . self::q($this->pk) . ' = ?';
            $pdo->prepare($sql)->execute([...array_values($mudou), $id]);

            $depois = $this->buscar($id);
            $velhos = [$this->pk => $id];
            $novos  = [$this->pk => $id];
            foreach (array_keys($mudou) as $campo) {
                $velhos[$campo] = $antes[$campo];
                $novos[$campo]  = $depois[$campo];
            }
            $this->auditar('UPDATE', $velhos, $novos);
            $pdo->commit();
            return true;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw self::traduzirErro($e);
        }
    }

    public function excluir($id): void
    {
        $antes = $this->buscar($id);
        if ($antes === null) {
            throw new RuntimeException('Registro não encontrado.');
        }

        // O banco não tem chave estrangeira entre as tabelas do Kobo (lote → domicílios → cadastros),
        // então a proteção contra registros órfãos é feita aqui.
        foreach ($this->relacionados($antes) as $r) {
            if (!empty($r['rel']['bloqueia_exclusao']) && $r['linhas']) {
                throw new RuntimeException(sprintf(
                    'Não é possível excluir: existem %d registro(s) em "%s" ligados a este. Exclua ou desvincule esses registros antes.',
                    count($r['linhas']), $r['crud']->cfg['titulo']
                ));
            }
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('DELETE FROM ' . self::q($this->tabela) . ' WHERE ' . self::q($this->pk) . ' = ?')->execute([$id]);
            $this->auditar('DELETE', $antes, null);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw self::traduzirErro($e);
        }
    }

    /** Lê a data no formato indicado sem aceitar valores "estourados" (ex.: 31/02). */
    private static function dataEstrita(string $formato, string $valor): ?DateTime
    {
        $d = DateTime::createFromFormat($formato, $valor);
        $avisos = DateTime::getLastErrors();
        if (!$d || ($avisos && ($avisos['warning_count'] > 0 || $avisos['error_count'] > 0))) {
            return null;
        }
        return $d->format(ltrim($formato, '!')) === $valor ? $d : null;
    }

    private static function iguais($antigo, $novo, string $base): bool
    {
        if (($antigo === null || $antigo === '') && ($novo === null || $novo === '')) {
            return true;
        }
        if ($antigo === null || $novo === null) {
            return false;
        }
        if (in_array($base, ['inteiro', 'decimal'], true) && is_numeric($antigo) && is_numeric($novo)) {
            return (float) $antigo === (float) $novo;
        }
        return (string) $antigo === (string) $novo;
    }

    /** Grava na tabela auditoria. Senhas nunca são gravadas. */
    private function auditar(string $acao, ?array $antes, ?array $depois): void
    {
        if ($this->tabela === 'auditoria') {
            return;
        }
        $limpar = function (?array $dados): ?string {
            if ($dados === null) {
                return null;
            }
            foreach ($dados as $k => $v) {
                if (($this->campos[$k]['tipo'] ?? '') === 'senha' && $v !== null) {
                    $dados[$k] = '***';
                }
            }
            return json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        };

        db()->prepare(
            'INSERT INTO auditoria (tabela, acao, usuario, valor_antigo, valor_atual, atualizado) VALUES (?, ?, ?, ?, ?, NOW())'
        )->execute([$this->tabela, $acao, usuario_id(), $limpar($antes), $limpar($depois)]);
    }

    private static function traduzirErro(Throwable $e): Throwable
    {
        if ($e instanceof PDOException && isset($e->errorInfo[1])) {
            switch ((int) $e->errorInfo[1]) {
                case 1062:
                    return new RuntimeException('Já existe um registro com esse identificador.', 0, $e);
                case 1451:
                    return new RuntimeException('Não é possível excluir: existem registros vinculados a este (ex.: domicílios de um lote, usuários de um perfil).', 0, $e);
                case 1452:
                    return new RuntimeException('O registro informado como referência não existe.', 0, $e);
            }
        }
        return $e;
    }

    /* ================================================================ */
    /* Exibição                                                          */
    /* ================================================================ */

    /** Pasta (no site) das fotos desta tela, com barra no fim. Ex.: '/arquivos/domicilio/'. */
    public function baseFotos(): string
    {
        $base  = rtrim((string) cfg('fotos_base_url', ''), '/');
        $pasta = trim((string) ($this->cfg['pasta_fotos'] ?? ''), '/');
        $url   = $base . '/' . ($pasta !== '' ? $pasta . '/' : '');
        return $url === '/' && $base === '' && $pasta === '' ? '' : $url;
    }

    /** Endereço de uma foto a partir do valor gravado (nome do arquivo). */
    public function urlFoto(?string $valor): string
    {
        $v = trim((string) $valor);
        if ($v === '') {
            return '';
        }
        if (preg_match('#^([a-z][a-z0-9+.\-]*:|//|/)#i', $v)) {
            return $v;   // já é URL completa ou caminho a partir da raiz
        }
        // Codifica cada parte (nomes com espaço ou acento), mantendo as barras
        return $this->baseFotos() . implode('/', array_map('rawurlencode', explode('/', $v)));
    }

    /** Formata um valor para a listagem / leitura. */
    public function exibir(string $campo, $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }
        $c = $this->campos[$campo] ?? null;
        if (!$c) {
            return (string) $valor;
        }
        switch ($c['tipo']) {
            case 'senha':
                return '••••••';
            case 'fk':
                return $this->opcoesFk($campo)[(string) $valor] ?? (string) $valor;
            case 'select':
                return (string) ($c['opcoes'][(string) $valor] ?? $valor);
            case 'simnao':
                $n = self::normalizar((string) $valor);
                return $n === 'sim' ? 'Sim' : ($n === 'nao' ? 'Não' : (string) $valor);
        }
        switch ($c['base']) {
            case 'data':
                $d = DateTime::createFromFormat('!Y-m-d', substr((string) $valor, 0, 10));
                return $d ? $d->format('d/m/Y') : (string) $valor;
            case 'datahora':
                $d = DateTime::createFromFormat('Y-m-d H:i:s', (string) $valor);
                return $d ? $d->format('d/m/Y H:i') : (string) $valor;
            case 'decimal':
                return number_format((float) $valor, min($c['escala'], 2), ',', '.');
        }
        return (string) $valor;
    }

    /** Minúsculas e sem acento, para comparar "Sim"/"sim"/"SIM", "Não"/"nao". */
    public static function normalizar(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        return strtr($s, ['ã' => 'a', 'á' => 'a', 'â' => 'a', 'à' => 'a', 'é' => 'e', 'ê' => 'e', 'í' => 'i',
                          'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ú' => 'u', 'ç' => 'c']);
    }
}
