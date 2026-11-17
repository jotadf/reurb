<?php
/**
 * Camada que decide quando usar o cache do MySQL e quando consultar a brapi.
 * As páginas sempre leem daqui; nunca chamam a API diretamente.
 */
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/BrapiClient.php';

class ServicoMercado extends Model
{
    /** Mensagens de erro da API acumuladas durante a requisição (ticker => msg). */
    public $erros = [];
    private $api;

    public function __construct()
    {
        parent::__construct();
        $this->api = new BrapiClient();
    }

    // ================================================================
    // Cotações
    // ================================================================

    /**
     * Cotação atual de cada ticker, atualizando pela API as que estão vencidas.
     * @return array ticker => linha da tabela cotacao (ou null se nunca obtida)
     */
    public function cotacoes(array $tickers, $forcar = false)
    {
        $tickers = array_values(array_unique(array_filter(array_map('normaliza_ticker', $tickers))));
        if (!$tickers) {
            return [];
        }
        $atuais  = $this->lerCotacoes($tickers);
        $ttl     = minutos_cache_cotacao() * 60;
        $vencidos = [];
        foreach ($tickers as $t) {
            if ($forcar || empty($atuais[$t]) || (time() - strtotime($atuais[$t]['atualizado_em'])) > $ttl) {
                $vencidos[] = $t;
            }
        }
        if ($vencidos) {
            foreach ($this->api->cotacoes($vencidos) as $t => $r) {
                if (isset($r['erro'])) {
                    $this->erros[$t] = $r['erro'];
                    continue;
                }
                $this->gravaCotacao($r);
            }
            $atuais = $this->lerCotacoes($tickers);
        }
        $saida = [];
        foreach ($tickers as $t) {
            $saida[$t] = $atuais[$t] ?? null;
        }
        return $saida;
    }

    public function cotacao($ticker, $forcar = false)
    {
        $r = $this->cotacoes([$ticker], $forcar);
        return reset($r) ?: null;
    }

    private function lerCotacoes(array $tickers)
    {
        $marcas = implode(',', array_fill(0, count($tickers), '?'));
        $linhas = $this->todos("SELECT * FROM cotacao WHERE ticker IN ($marcas)", $tickers);
        $mapa = [];
        foreach ($linhas as $l) {
            $mapa[$l['ticker']] = $l;
        }
        return $mapa;
    }

    private function gravaCotacao(array $r)
    {
        $n = function ($k) use ($r) {
            return isset($r[$k]) && is_numeric($r[$k]) ? $r[$k] : null;
        };
        $mercadoEm = null;
        if (!empty($r['regularMarketTime'])) {
            $ts = is_numeric($r['regularMarketTime']) ? (int) $r['regularMarketTime'] : strtotime($r['regularMarketTime']);
            $mercadoEm = $ts ? date('Y-m-d H:i:s', $ts) : null;
        }
        $this->consulta(
            'INSERT INTO cotacao (ticker, nome_curto, nome_longo, moeda, preco, variacao, variacao_pct, abertura, fechamento_anterior,
                                  maxima_dia, minima_dia, volume, valor_mercado, pl, lpa, minima_52s, maxima_52s, logo_url, mercado_em, atualizado_em)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
             ON DUPLICATE KEY UPDATE nome_curto=VALUES(nome_curto), nome_longo=VALUES(nome_longo), moeda=VALUES(moeda), preco=VALUES(preco),
                variacao=VALUES(variacao), variacao_pct=VALUES(variacao_pct), abertura=VALUES(abertura), fechamento_anterior=VALUES(fechamento_anterior),
                maxima_dia=VALUES(maxima_dia), minima_dia=VALUES(minima_dia), volume=VALUES(volume), valor_mercado=VALUES(valor_mercado),
                pl=VALUES(pl), lpa=VALUES(lpa), minima_52s=VALUES(minima_52s), maxima_52s=VALUES(maxima_52s), logo_url=VALUES(logo_url),
                mercado_em=VALUES(mercado_em), atualizado_em=NOW()',
            [
                strtoupper($r['symbol']),
                isset($r['shortName']) ? mb_substr($r['shortName'], 0, 120) : null,
                isset($r['longName']) ? mb_substr($r['longName'], 0, 200) : null,
                $r['currency'] ?? null,
                $n('regularMarketPrice'), $n('regularMarketChange'), $n('regularMarketChangePercent'),
                $n('regularMarketOpen'), $n('regularMarketPreviousClose'),
                $n('regularMarketDayHigh'), $n('regularMarketDayLow'),
                $n('regularMarketVolume') !== null ? (int) $r['regularMarketVolume'] : null,
                $n('marketCap'), $n('priceEarnings'), $n('earningsPerShare'),
                $n('fiftyTwoWeekLow'), $n('fiftyTwoWeekHigh'),
                isset($r['logourl']) ? mb_substr($r['logourl'], 0, 255) : null,
                $mercadoEm,
            ]
        );
    }

    // ================================================================
    // Histórico diário e proventos
    // ================================================================

    /**
     * Série diária de preços do período (ordenada por data).
     * Busca na API quando o cache do período está vencido; os dados ficam
     * acumulados no MySQL, então o histórico cresce com o uso.
     */
    public function historico($ticker, $range, $forcar = false)
    {
        $ticker = normaliza_ticker($ticker);
        if (!$ticker) {
            return [];
        }
        $range = array_key_exists($range, config('analise.ranges') ?: []) || in_array($range, ['ytd', 'max'], true)
            ? $range : config('analise.range_padrao');

        // A API só é consultada até o limite do plano; o restante vem do banco.
        $rangeApi = self::limitaRange($range);
        $sync = $this->um('SELECT atualizado_em FROM api_sync WHERE ticker=? AND tipo=? AND faixa=?', [$ticker, 'historico', $rangeApi]);
        $ttl  = (int) config('cache.historico_minutos') * 60;
        if ($forcar || !$sync || (time() - strtotime($sync['atualizado_em'])) > $ttl) {
            $this->atualizaHistorico([$ticker], $rangeApi);
        }
        $inicio = self::inicioDoPeriodo($range);
        $sql = 'SELECT data, abertura, maxima, minima, fechamento, fechamento_ajustado, volume
                  FROM historico_preco WHERE ticker = ?' . ($inicio ? ' AND data >= ?' : '') . ' ORDER BY data';
        return $this->todos($sql, $inicio ? [$ticker, $inicio] : [$ticker]);
    }

    /** Busca histórico + dividendos de vários ativos na API e grava no banco. */
    public function atualizaHistorico(array $tickers, $range)
    {
        $range = self::limitaRange($range);
        $res = $this->api->cotacoes($tickers, ['range' => $range, 'interval' => '1d', 'dividends' => 'true']);
        foreach ($res as $t => $r) {
            if (isset($r['erro'])) {
                $this->erros[$t] = $r['erro'];
                continue;
            }
            $this->gravaCotacao($r);
            $this->gravaHistorico($t, $r['historicalDataPrice'] ?? []);
            $this->gravaDividendos($t, $r['dividendsData'] ?? []);
            $this->consulta('INSERT INTO api_sync (ticker, tipo, faixa, atualizado_em) VALUES (?,?,?,NOW())
                             ON DUPLICATE KEY UPDATE atualizado_em = NOW()', [$t, 'historico', $range]);
        }
    }

    private function gravaHistorico($ticker, array $serie)
    {
        if (!$serie) {
            return;
        }
        $st = $this->db->prepare(
            'INSERT INTO historico_preco (ticker, data, abertura, maxima, minima, fechamento, fechamento_ajustado, volume)
             VALUES (?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
                abertura            = IF(fonte = \'b3\', abertura, VALUES(abertura)),
                maxima              = IF(fonte = \'b3\', maxima, VALUES(maxima)),
                minima              = IF(fonte = \'b3\', minima, VALUES(minima)),
                fechamento          = IF(fonte = \'b3\', fechamento, VALUES(fechamento)),
                fechamento_ajustado = IF(fonte = \'b3\', fechamento_ajustado, VALUES(fechamento_ajustado)),
                volume              = IF(fonte = \'b3\', volume, VALUES(volume))'
        );
        $this->db->beginTransaction();
        try {
            foreach ($serie as $p) {
                if (!isset($p['date'], $p['close']) || !is_numeric($p['close'])) {
                    continue;
                }
                // Data do pregão em UTC: evita que um horário 00:00 UTC vire o dia anterior no fuso de Brasília
                $dia = is_numeric($p['date']) ? gmdate('Y-m-d', (int) $p['date']) : substr((string) $p['date'], 0, 10);
                if ((int) date('N', strtotime($dia)) >= 6) {
                    continue; // não há pregão em fim de semana
                }
                $st->execute([
                    $ticker, $dia,
                    $p['open'] ?? null, $p['high'] ?? null, $p['low'] ?? null,
                    $p['close'], $p['adjustedClose'] ?? $p['close'],
                    isset($p['volume']) ? (int) $p['volume'] : null,
                ]);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function gravaDividendos($ticker, array $dados)
    {
        $lista = $dados['cashDividends'] ?? [];
        if (!$lista) {
            return;
        }
        $st = $this->db->prepare(
            'INSERT IGNORE INTO dividendo (ticker, tipo, valor, data_com, data_pagamento, referente) VALUES (?,?,?,?,?,?)'
        );
        $data = function ($v) {
            if (!$v) return null;
            $ts = is_numeric($v) ? (int) $v : strtotime($v);
            return $ts ? date('Y-m-d', $ts) : null;
        };
        foreach ($lista as $d) {
            if (!isset($d['rate']) || !is_numeric($d['rate'])) {
                continue;
            }
            $st->execute([
                $ticker,
                mb_substr(strtoupper($d['label'] ?? 'PROVENTO'), 0, 40),
                $d['rate'],
                $data($d['lastDatePrior'] ?? ($d['approvedOn'] ?? null)),
                $data($d['paymentDate'] ?? null),
                isset($d['relatedTo']) ? mb_substr($d['relatedTo'], 0, 80) : null,
            ]);
        }
    }

    public function dividendos($ticker, $desde = null)
    {
        $sql = 'SELECT tipo, valor, data_com, data_pagamento, referente FROM dividendo WHERE ticker = ?'
             . ($desde ? ' AND data_com >= ?' : '') . ' ORDER BY data_com DESC';
        return $this->todos($sql, $desde ? [$ticker, $desde] : [$ticker]);
    }

    /** Soma dos proventos com data-com nos últimos 12 meses. */
    public function proventos12m($ticker)
    {
        $r = $this->um('SELECT SUM(valor) total FROM dividendo WHERE ticker = ? AND data_com >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)', [$ticker]);
        return $r && $r['total'] !== null ? (float) $r['total'] : null;
    }

    // ================================================================
    // Informações de uso
    // ================================================================

    public function requisicoesNoMes()
    {
        $r = $this->um("SELECT COUNT(*) n FROM api_log WHERE criado_em >= DATE_FORMAT(CURDATE(), '%Y-%m-01')");
        return (int) ($r['n'] ?? 0);
    }

    public function ultimaAtualizacao()
    {
        $r = $this->um('SELECT MAX(atualizado_em) m FROM cotacao');
        return $r['m'] ?? null;
    }

    /** Primeira data de histórico guardada no banco para o ticker. */
    public function inicioHistorico($ticker)
    {
        $r = $this->um('SELECT MIN(data) d FROM historico_preco WHERE ticker = ?', [$ticker]);
        return $r['d'] ?? null;
    }

    /** Cobertura do histórico por ticker: ticker => [inicio, fim, pregoes, b3]. */
    public function coberturaHistorico()
    {
        $mapa = [];
        foreach ($this->todos("SELECT ticker, MIN(data) inicio, MAX(data) fim, COUNT(*) pregoes, SUM(fonte = 'b3') b3
                                 FROM historico_preco GROUP BY ticker") as $l) {
            $mapa[$l['ticker']] = $l;
        }
        return $mapa;
    }

    /** Ordem dos períodos aceitos pela brapi, do menor para o maior. */
    const ORDEM_RANGES = ['1d', '2d', '5d', '7d', '1mo', '3mo', '6mo', 'ytd', '1y', '2y', '5y', '10y', 'max'];

    /** Reduz o período ao máximo liberado pelo plano (brapi.range_maximo). */
    public static function limitaRange($range)
    {
        $max = config('brapi.range_maximo') ?: '3mo';
        $iR = array_search($range, self::ORDEM_RANGES, true);
        $iM = array_search($max, self::ORDEM_RANGES, true);
        if ($iR === false) {
            return $max;
        }
        return ($iM !== false && $iR > $iM) ? $max : $range;
    }

    /**
     * Aviso quando o banco ainda não tem histórico cobrindo todo o período pedido.
     * Retorna a data inicial disponível, ou null se o período está completo.
     */
    public static function periodoIncompleto($range, $primeiraData)
    {
        $inicio = self::inicioDoPeriodo($range);
        if (!$inicio || !$primeiraData) {
            return null;
        }
        // tolera feriados e fins de semana no começo do período
        return strtotime($primeiraData) > strtotime($inicio . ' +7 days') ? $primeiraData : null;
    }

    /** Data inicial correspondente a um range da brapi (null = sem limite). */
    public static function inicioDoPeriodo($range)
    {
        $mapa = ['1d' => '-1 day', '5d' => '-5 days', '7d' => '-7 days', '1mo' => '-1 month', '3mo' => '-3 months',
                 '6mo' => '-6 months', '1y' => '-1 year', '2y' => '-2 years', '5y' => '-5 years', '10y' => '-10 years'];
        if ($range === 'ytd') {
            return date('Y-01-01');
        }
        return isset($mapa[$range]) ? date('Y-m-d', strtotime($mapa[$range])) : null;
    }
}
