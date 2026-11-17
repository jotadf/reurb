<?php
/**
 * Cálculos de análise técnica e de risco. Funções puras, sem acesso ao banco.
 * Todas as séries são arrays de floats ordenados do mais antigo para o mais recente.
 */
class Analise
{
    const DIAS_UTEIS_ANO = 252;

    /** Retornos simples diários: p[i]/p[i-1] - 1. */
    public static function retornos(array $precos)
    {
        $r = [];
        for ($i = 1, $n = count($precos); $i < $n; $i++) {
            if ($precos[$i - 1] > 0) {
                $r[] = $precos[$i] / $precos[$i - 1] - 1;
            }
        }
        return $r;
    }

    public static function retornoTotal(array $precos)
    {
        $n = count($precos);
        return ($n >= 2 && $precos[0] > 0) ? $precos[$n - 1] / $precos[0] - 1 : null;
    }

    public static function media(array $x)
    {
        return $x ? array_sum($x) / count($x) : null;
    }

    /** Desvio-padrão amostral. */
    public static function desvio(array $x)
    {
        $n = count($x);
        if ($n < 2) {
            return null;
        }
        $m = self::media($x);
        $s = 0.0;
        foreach ($x as $v) {
            $s += ($v - $m) ** 2;
        }
        return sqrt($s / ($n - 1));
    }

    public static function volatilidadeAnual(array $retornos)
    {
        $d = self::desvio($retornos);
        return $d === null ? null : $d * sqrt(self::DIAS_UTEIS_ANO);
    }

    /** Maior queda de um topo até um fundo posterior (valor negativo). */
    public static function maxDrawdown(array $precos)
    {
        $pico = null;
        $pior = 0.0;
        foreach ($precos as $p) {
            if ($pico === null || $p > $pico) {
                $pico = $p;
            }
            if ($pico > 0) {
                $dd = $p / $pico - 1;
                if ($dd < $pior) {
                    $pior = $dd;
                }
            }
        }
        return count($precos) >= 2 ? $pior : null;
    }

    /** Série de drawdown ponto a ponto (para gráfico). */
    public static function serieDrawdown(array $precos)
    {
        $pico = null;
        $s = [];
        foreach ($precos as $p) {
            $pico = ($pico === null || $p > $pico) ? $p : $pico;
            $s[] = $pico > 0 ? $p / $pico - 1 : 0;
        }
        return $s;
    }

    /** Índice de Sharpe anualizado com taxa livre de risco anual. */
    public static function sharpe(array $retornos, $taxaAnual)
    {
        $d = self::desvio($retornos);
        if (!$d) {
            return null;
        }
        $rfDia = pow(1 + $taxaAnual, 1 / self::DIAS_UTEIS_ANO) - 1;
        return (self::media($retornos) - $rfDia) / $d * sqrt(self::DIAS_UTEIS_ANO);
    }

    /** Média móvel simples (null nas posições sem dados suficientes). */
    public static function mms(array $precos, $periodo)
    {
        $s = [];
        $soma = 0.0;
        foreach ($precos as $i => $p) {
            $soma += $p;
            if ($i >= $periodo) {
                $soma -= $precos[$i - $periodo];
            }
            $s[] = $i >= $periodo - 1 ? $soma / $periodo : null;
        }
        return $s;
    }

    /** Média móvel exponencial. */
    public static function mme(array $precos, $periodo)
    {
        $s = [];
        $k = 2 / ($periodo + 1);
        $ant = null;
        foreach ($precos as $i => $p) {
            if ($i < $periodo - 1) {
                $s[] = null;
                continue;
            }
            if ($ant === null) {
                $ant = array_sum(array_slice($precos, 0, $periodo)) / $periodo;
            } else {
                $ant = $p * $k + $ant * (1 - $k);
            }
            $s[] = $ant;
        }
        return $s;
    }

    /** Bandas de Bollinger: ['media'=>[], 'superior'=>[], 'inferior'=>[]]. */
    public static function bollinger(array $precos, $periodo = 20, $desvios = 2)
    {
        $m = self::mms($precos, $periodo);
        $sup = $inf = [];
        foreach ($precos as $i => $p) {
            if ($m[$i] === null) {
                $sup[] = $inf[] = null;
                continue;
            }
            $janela = array_slice($precos, $i - $periodo + 1, $periodo);
            $mm = $m[$i];
            $var = 0.0;
            foreach ($janela as $v) {
                $var += ($v - $mm) ** 2;
            }
            $dp = sqrt($var / $periodo);
            $sup[] = $mm + $desvios * $dp;
            $inf[] = $mm - $desvios * $dp;
        }
        return ['media' => $m, 'superior' => $sup, 'inferior' => $inf];
    }

    /** IFR / RSI de Wilder. */
    public static function ifr(array $precos, $periodo = 14)
    {
        $n = count($precos);
        $s = array_fill(0, $n, null);
        if ($n <= $periodo) {
            return $s;
        }
        $ganho = $perda = 0.0;
        for ($i = 1; $i <= $periodo; $i++) {
            $d = $precos[$i] - $precos[$i - 1];
            $ganho += max($d, 0);
            $perda += max(-$d, 0);
        }
        $ganho /= $periodo;
        $perda /= $periodo;
        $s[$periodo] = $perda == 0 ? 100.0 : 100 - 100 / (1 + $ganho / $perda);
        for ($i = $periodo + 1; $i < $n; $i++) {
            $d = $precos[$i] - $precos[$i - 1];
            $ganho = ($ganho * ($periodo - 1) + max($d, 0)) / $periodo;
            $perda = ($perda * ($periodo - 1) + max(-$d, 0)) / $periodo;
            $s[$i] = $perda == 0 ? 100.0 : 100 - 100 / (1 + $ganho / $perda);
        }
        return $s;
    }

    /** MACD (12, 26, 9): ['macd'=>[], 'sinal'=>[], 'histograma'=>[]]. */
    public static function macd(array $precos, $curta = 12, $longa = 26, $sinal = 9)
    {
        $ec = self::mme($precos, $curta);
        $el = self::mme($precos, $longa);
        $macd = [];
        foreach ($precos as $i => $p) {
            $macd[] = ($ec[$i] !== null && $el[$i] !== null) ? $ec[$i] - $el[$i] : null;
        }
        $validos = array_values(array_filter($macd, function ($v) { return $v !== null; }));
        $sinalValidos = self::mme($validos, $sinal);
        $offset = count($macd) - count($validos);
        $sig = $hist = [];
        foreach ($macd as $i => $v) {
            $sv = $i >= $offset ? $sinalValidos[$i - $offset] : null;
            $sig[] = $sv;
            $hist[] = ($v !== null && $sv !== null) ? $v - $sv : null;
        }
        return ['macd' => $macd, 'sinal' => $sig, 'histograma' => $hist];
    }

    public static function correlacao(array $x, array $y)
    {
        $n = min(count($x), count($y));
        if ($n < 3) {
            return null;
        }
        $x = array_slice($x, 0, $n);
        $y = array_slice($y, 0, $n);
        $mx = self::media($x);
        $my = self::media($y);
        $sxy = $sxx = $syy = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $dx = $x[$i] - $mx;
            $dy = $y[$i] - $my;
            $sxy += $dx * $dy;
            $sxx += $dx * $dx;
            $syy += $dy * $dy;
        }
        return ($sxx > 0 && $syy > 0) ? $sxy / sqrt($sxx * $syy) : null;
    }

    /** Beta do ativo em relação ao índice (retornos já alinhados por data). */
    public static function beta(array $retAtivo, array $retIndice)
    {
        $n = min(count($retAtivo), count($retIndice));
        if ($n < 10) {
            return null;
        }
        $a = array_slice($retAtivo, 0, $n);
        $b = array_slice($retIndice, 0, $n);
        $ma = self::media($a);
        $mb = self::media($b);
        $cov = $var = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $cov += ($a[$i] - $ma) * ($b[$i] - $mb);
            $var += ($b[$i] - $mb) ** 2;
        }
        return $var > 0 ? $cov / $var : null;
    }

    /**
     * Alinha várias séries pelas datas em comum.
     * @param array $series nome => [data => preço]
     * @return array ['datas' => [...], 'series' => nome => [preços]]
     */
    public static function alinhar(array $series)
    {
        $comuns = null;
        foreach ($series as $s) {
            $datas = array_keys($s);
            $comuns = $comuns === null ? $datas : array_values(array_intersect($comuns, $datas));
        }
        $comuns = $comuns ?: [];
        sort($comuns);
        $saida = [];
        foreach ($series as $nome => $s) {
            $saida[$nome] = array_map(function ($d) use ($s) { return (float) $s[$d]; }, $comuns);
        }
        return ['datas' => $comuns, 'series' => $saida];
    }

    /** Normaliza a série para começar em 100. */
    public static function base100(array $precos)
    {
        if (!$precos || $precos[0] <= 0) {
            return [];
        }
        $b = $precos[0];
        return array_map(function ($p) use ($b) { return round($p / $b * 100, 4); }, $precos);
    }
}
