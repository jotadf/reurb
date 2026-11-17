<?php
/**
 * Importa as Séries Históricas (COTAHIST) publicadas gratuitamente pela B3.
 *
 * Layout oficial: "Layout do Arquivo - Cotações Históricas" (COTAHIST.AAAA.TXT), 245 posições por linha.
 * Arquivos: anual (COTAHIST_AAAAA.ZIP), mensal (COTAHIST_MMMAAAA.ZIP) e diário (COTAHIST_DDDMMAAAA.ZIP).
 *
 * Grava somente os ativos de interesse (lista de acompanhamento + carteiras) e depois
 * recalcula o fechamento ajustado por proventos e desdobramentos/grupamentos.
 */
require_once __DIR__ . '/Model.php';

class ImportadorCotahist extends Model
{
    /** Mensagens para exibir ao final (detecções de desdobramento, avisos). */
    public $log = [];

    // ------------------------------------------------------------------
    // Download
    // ------------------------------------------------------------------

    public static function nomeAnual($ano)
    {
        return sprintf('COTAHIST_A%04d.ZIP', $ano);
    }

    public static function nomeDiario($ts)
    {
        return 'COTAHIST_D' . date('dmY', $ts) . '.ZIP';
    }

    /**
     * Baixa um arquivo da B3 para a pasta temporária.
     * @return string caminho local
     * @throws RuntimeException em falha (inclui HTTP 404 quando o arquivo do dia ainda não existe)
     */
    public function baixar($nome)
    {
        $url = rtrim(config('b3.url_base') ?: 'https://bvmf.bmfbovespa.com.br/InstDados/SerHist/', '/') . '/' . $nome;
        $destino = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'painelb3_' . $nome;
        $fp = fopen($destino, 'wb');
        if (!$fp) {
            throw new RuntimeException('Não foi possível gravar em ' . $destino);
        }
        $ch = curl_init($url);
        $verificar = config('b3.verificar_ssl') !== false;
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 600,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_USERAGENT      => 'Mozilla/5.0 (PainelB3)',
            CURLOPT_SSL_VERIFYPEER => $verificar,
            CURLOPT_SSL_VERIFYHOST => $verificar ? 2 : 0,
        ]);
        $ok   = curl_exec($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro = curl_error($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        fclose($fp);

        if (!$ok || $http !== 200 || filesize($destino) < 100) {
            @unlink($destino);
            if (in_array($errno, [35, 51, 58, 60, 77], true)) {
                throw new RuntimeException("Falha de certificado SSL ao acessar a B3 ($erro). "
                    . "Baixe o arquivo manualmente e use --arquivo, ou defina 'verificar_ssl' => false na seção 'b3' do config.php.");
            }
            throw new RuntimeException($erro ? "Falha ao baixar $nome: $erro" : "Arquivo $nome indisponível (HTTP $http).", $http);
        }
        return $destino;
    }

    // ------------------------------------------------------------------
    // Leitura e gravação
    // ------------------------------------------------------------------

    /**
     * Processa um arquivo COTAHIST (.ZIP ou .TXT).
     * @param string[] $tickers códigos que devem ser gravados
     * @return array ['linhas' => n lidas, 'gravadas' => n, 'tickers' => [ticker => n]]
     */
    public function processar($caminho, array $tickers)
    {
        $alvo = array_fill_keys(array_map('strtoupper', $tickers), true);
        $stream = $this->abrir($caminho);

        $st = $this->db->prepare(
            "INSERT INTO historico_preco (ticker, data, abertura, maxima, minima, fechamento, fechamento_ajustado, volume, fonte)
             VALUES (?,?,?,?,?,?,?,?,'b3')
             ON DUPLICATE KEY UPDATE abertura=VALUES(abertura), maxima=VALUES(maxima), minima=VALUES(minima),
                fechamento=VALUES(fechamento), volume=VALUES(volume), fonte='b3'"
        );
        $res = ['linhas' => 0, 'gravadas' => 0, 'tickers' => []];
        $this->db->beginTransaction();
        try {
            while (($linha = fgets($stream)) !== false) {
                $res['linhas']++;
                // 01 = registro de cotação; 010 = mercado à vista (exclui fracionário, opções, termo)
                if (strncmp($linha, '01', 2) !== 0 || substr($linha, 24, 3) !== '010') {
                    continue;
                }
                $ticker = rtrim(substr($linha, 12, 12));
                if (!isset($alvo[$ticker])) {
                    continue;
                }
                $r = self::lerLinha($linha);
                $st->execute([$ticker, $r['data'], $r['abertura'], $r['maxima'], $r['minima'], $r['fechamento'], $r['fechamento'], $r['volume']]);
                $res['gravadas']++;
                $res['tickers'][$ticker] = ($res['tickers'][$ticker] ?? 0) + 1;
                if ($res['gravadas'] % 5000 === 0) {
                    $this->db->commit();
                    $this->db->beginTransaction();
                }
            }
            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        } finally {
            fclose($stream);
        }
        return $res;
    }

    /** Converte uma linha de cotação (posições conforme o layout da B3). */
    public static function lerLinha($l)
    {
        $fator = (int) substr($l, 210, 7) ?: 1;          // FATCOT: preços cotados por lote de 1 ou 1000
        $preco = function ($ini) use ($l, $fator) {        // campos (11)V99
            return round(((int) substr($l, $ini, 13)) / 100 / $fator, 6);
        };
        $d = substr($l, 2, 8);
        return [
            'data'       => substr($d, 0, 4) . '-' . substr($d, 4, 2) . '-' . substr($d, 6, 2),
            'codbdi'     => substr($l, 10, 2),
            'ticker'     => rtrim(substr($l, 12, 12)),
            'abertura'   => $preco(56),
            'maxima'     => $preco(69),
            'minima'     => $preco(82),
            'fechamento' => $preco(108),
            'volume'     => (int) substr($l, 152, 18),   // QUATOT: quantidade de títulos negociados
        ];
    }

    /** Abre .ZIP (primeiro arquivo interno) ou .TXT como stream de leitura. */
    private function abrir($caminho)
    {
        if (!is_file($caminho)) {
            throw new RuntimeException('Arquivo não encontrado: ' . $caminho);
        }
        if (strtolower(pathinfo($caminho, PATHINFO_EXTENSION)) === 'zip') {
            if (!class_exists('ZipArchive')) {
                throw new RuntimeException('A extensão zip do PHP não está habilitada. Descompacte o arquivo e informe o .TXT.');
            }
            $zip = new ZipArchive();
            if ($zip->open($caminho) !== true || $zip->numFiles < 1) {
                throw new RuntimeException('ZIP inválido: ' . $caminho);
            }
            $interno = $zip->getNameIndex(0);
            $zip->close();
            $stream = fopen('zip://' . $caminho . '#' . $interno, 'r');
        } else {
            $stream = fopen($caminho, 'r');
        }
        if (!$stream) {
            throw new RuntimeException('Não foi possível ler ' . $caminho);
        }
        return $stream;
    }

    // ------------------------------------------------------------------
    // Controle de arquivos já processados
    // ------------------------------------------------------------------

    public function jaProcessado($nome)
    {
        return (bool) $this->um('SELECT 1 FROM cotahist_arquivo WHERE nome = ?', [$nome]);
    }

    public function marcaProcessado($nome, $gravadas)
    {
        $this->consulta('INSERT INTO cotahist_arquivo (nome, registros, processado_em) VALUES (?,?,NOW())
                         ON DUPLICATE KEY UPDATE registros = VALUES(registros), processado_em = NOW()', [$nome, $gravadas]);
    }

    // ------------------------------------------------------------------
    // Ajuste por proventos e desdobramentos
    // ------------------------------------------------------------------

    /**
     * Recalcula fechamento_ajustado de toda a série do ticker a partir do fechamento nominal.
     * - Proventos (tabela dividendo, vinda da brapi): fator 1 - valor / fechamento na data-com.
     * - Desdobramentos/grupamentos: detectados quando o preço muda de patamar numa proporção
     *   "redonda" (1:2, 1:10, 10:1...) já na abertura do pregão seguinte.
     */
    public function recalcularAjuste($ticker)
    {
        // Dentro do período coberto pela B3, a série oficial prevalece: remove datas
        // vindas só da API que a B3 não registra (ex.: dias sem pregão).
        $this->consulta("DELETE h FROM historico_preco h
                           JOIN (SELECT MIN(data) ini, MAX(data) fim FROM historico_preco WHERE ticker = ? AND fonte = 'b3') b
                             ON h.data BETWEEN b.ini AND b.fim
                          WHERE h.ticker = ? AND h.fonte <> 'b3'", [$ticker, $ticker]);

        $linhas = $this->todos('SELECT data, abertura, fechamento FROM historico_preco WHERE ticker = ? ORDER BY data', [$ticker]);
        $n = count($linhas);
        if ($n < 2) {
            return;
        }
        $datas = array_column($linhas, 'data');
        $fech  = array_map('floatval', array_column($linhas, 'fechamento'));
        $abert = array_map(function ($l) { return $l['abertura'] !== null ? (float) $l['abertura'] : (float) $l['fechamento']; }, $linhas);
        $fatorEx = array_fill(0, $n, 1.0); // fator aplicado aos pregões ANTERIORES ao índice i

        // Proventos
        foreach ($this->todos('SELECT data_com, SUM(valor) valor FROM dividendo WHERE ticker = ? AND data_com IS NOT NULL GROUP BY data_com', [$ticker]) as $d) {
            $k = self::ultimoIndiceAte($datas, $d['data_com']);
            if ($k === null || $k >= $n - 1 || $fech[$k] <= 0 || $d['valor'] >= $fech[$k]) {
                continue; // data-com antes da série, ex-data ainda não ocorreu ou valor inconsistente
            }
            $fatorEx[$k + 1] *= 1 - $d['valor'] / $fech[$k];
        }

        // Desdobramentos e grupamentos
        $redondos = [2, 3, 4, 5, 8, 10, 20, 25, 50, 100];
        for ($i = 1; $i < $n; $i++) {
            if ($fech[$i - 1] <= 0 || $fech[$i] <= 0) {
                continue;
            }
            $r = $fech[$i] / $fech[$i - 1];
            if ($r > 0.55 && $r < 1.8) {
                continue;
            }
            $rAbert = $abert[$i] / $fech[$i - 1];
            foreach ($redondos as $k) {
                $alvo = $r < 1 ? 1 / $k : $k;
                if (abs($r / $alvo - 1) < 0.06 && abs($rAbert / $alvo - 1) < 0.10) {
                    $fatorEx[$i] *= $alvo;
                    $this->log[] = sprintf('%s: %s detectado em %s (proporção %s)', $ticker,
                        $r < 1 ? 'desdobramento' : 'grupamento', date('d/m/Y', strtotime($datas[$i])),
                        $r < 1 ? "1:$k" : "$k:1");
                    break;
                }
            }
        }

        // Aplica do mais recente para o mais antigo
        $st = $this->db->prepare('UPDATE historico_preco SET fechamento_ajustado = ? WHERE ticker = ? AND data = ?');
        $this->db->beginTransaction();
        $mult = 1.0;
        for ($i = $n - 1; $i >= 0; $i--) {
            $st->execute([round($fech[$i] * $mult, 6), $ticker, $datas[$i]]);
            $mult *= $fatorEx[$i];
        }
        $this->db->commit();
    }

    /** Índice do último pregão com data <= $data (busca binária). */
    private static function ultimoIndiceAte(array $datas, $data)
    {
        $lo = 0;
        $hi = count($datas) - 1;
        $res = null;
        while ($lo <= $hi) {
            $m = intdiv($lo + $hi, 2);
            if ($datas[$m] <= $data) {
                $res = $m;
                $lo = $m + 1;
            } else {
                $hi = $m - 1;
            }
        }
        return $res;
    }
}
