<?php
/**
 * Cliente da API brapi.dev (https://brapi.dev/docs).
 * Faz as requisições em paralelo (curl_multi) e registra cada chamada em api_log.
 */
require_once __DIR__ . '/Model.php';

class BrapiClient extends Model
{
    private $base;
    private $token;
    private $timeout;
    private $variosPorRequisicao;

    public function __construct()
    {
        parent::__construct();
        $c = config('brapi');
        $this->base    = rtrim($c['base_url'] ?? 'https://brapi.dev/api', '/');
        $this->token   = trim((string) ($c['token'] ?? ''));
        $this->timeout = (int) ($c['timeout'] ?? 15);
        $this->variosPorRequisicao = !empty($c['varios_ativos_por_requisicao']);
    }

    /**
     * Busca cotações (e opcionalmente histórico/dividendos) de vários ativos.
     * @param string[] $tickers
     * @param array    $params  ex.: ['range' => '3mo', 'interval' => '1d', 'dividends' => 'true']
     * @return array ticker => resultado da API | ['erro' => mensagem]
     */
    public function cotacoes(array $tickers, array $params = [])
    {
        $tickers = array_values(array_unique(array_filter($tickers)));
        if (!$tickers) {
            return [];
        }
        $grupos = $this->variosPorRequisicao ? array_chunk($tickers, 10) : array_map(function ($t) { return [$t]; }, $tickers);

        $mh = curl_multi_init();
        $handles = [];
        foreach ($grupos as $i => $grupo) {
            $url = $this->base . '/quote/' . implode(',', array_map('rawurlencode', $grupo));
            if ($params) {
                $url .= '?' . http_build_query($params);
            }
            $ch = curl_init($url);
            $headers = ['Accept: application/json'];
            if ($this->token !== '') {
                $headers[] = 'Authorization: Bearer ' . $this->token;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_USERAGENT      => 'PainelB3/1.0',
            ]);
            curl_multi_add_handle($mh, $ch);
            $handles[$i] = ['ch' => $ch, 'grupo' => $grupo, 'url' => $url];
        }

        do {
            $status = curl_multi_exec($mh, $ativos);
            if ($ativos) {
                curl_multi_select($mh, 1.0);
            }
        } while ($ativos && $status === CURLM_OK);

        $saida = [];
        foreach ($handles as $h) {
            $ch    = $h['ch'];
            $corpo = curl_multi_getcontent($ch);
            $http  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $erroCurl = curl_error($ch);
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);

            $json = json_decode((string) $corpo, true);
            $msg  = null;
            if ($erroCurl) {
                $msg = 'Falha de conexão: ' . $erroCurl;
            } elseif ($http !== 200 || !is_array($json)) {
                $msg = is_array($json) && !empty($json['message']) ? $json['message'] : ('HTTP ' . $http);
            }
            $this->registra($h['url'], $http, $msg);

            if ($msg !== null) {
                foreach ($h['grupo'] as $t) {
                    $saida[$t] = ['erro' => $msg];
                }
                continue;
            }
            $porTicker = [];
            foreach ($json['results'] ?? [] as $r) {
                if (!empty($r['symbol'])) {
                    $porTicker[strtoupper($r['symbol'])] = $r;
                }
            }
            foreach ($h['grupo'] as $t) {
                $saida[$t] = $porTicker[$t] ?? ['erro' => 'Ativo não encontrado na API'];
            }
        }
        curl_multi_close($mh);
        return $saida;
    }

    private function registra($url, $status, $mensagem)
    {
        try {
            // Nunca grava o token no log.
            $url = preg_replace('/([?&]token=)[^&]+/', '$1***', $url);
            $this->consulta('INSERT INTO api_log (url, status, mensagem) VALUES (?,?,?)',
                [substr($url, 0, 255), $status, $mensagem ? mb_substr($mensagem, 0, 255) : null]);
        } catch (Throwable $e) {
            // o log não pode derrubar a página
        }
    }
}
