<?php
/**
 * Importa o histórico diário oficial da B3 (Séries Históricas - COTAHIST).
 *
 * Uso:
 *   php cron/importar_cotahist.php --anos=2020-2026     carga inicial (arquivos anuais)
 *   php cron/importar_cotahist.php --diario             pregões recentes ainda não importados (agende 1x por dia, à noite)
 *   php cron/importar_cotahist.php --arquivo=/caminho/COTAHIST_A2024.ZIP   arquivo baixado manualmente (.ZIP ou .TXT)
 *
 * Opções:
 *   --dias=N     no modo --diario, quantos dias para trás verificar (padrão 7)
 *   --ativos=PETR4,VALE3   importa só estes códigos (padrão: lista de acompanhamento + carteiras)
 *
 * Agendamento sugerido (dias úteis às 21h):
 *   0 21 * * 1-5  php /caminho/painel-b3/cron/importar_cotahist.php --diario
 *
 * Depois de incluir um ativo novo na lista, rode de novo com --anos para trazer o passado dele.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Somente pela linha de comando.');
}
require_once __DIR__ . '/../actions/ManterAtivo.php';
require_once __DIR__ . '/../actions/ImportadorCotahist.php';

config();
set_time_limit(0);
ini_set('memory_limit', '512M');

$opts = getopt('', ['anos:', 'diario', 'dias:', 'arquivo:', 'ativos:']);
if (!$opts) {
    fwrite(STDERR, "Informe --anos=AAAA-AAAA, --diario ou --arquivo=caminho. Veja o cabeçalho deste arquivo.\n");
    exit(1);
}

// Ativos de interesse
if (!empty($opts['ativos'])) {
    $tickers = array_filter(array_map('normaliza_ticker', explode(',', $opts['ativos'])));
} else {
    $tickers = (new ManterAtivo())->tickers();
    foreach (Model::conexao()->query('SELECT DISTINCT ticker FROM carteira_posicao')->fetchAll(PDO::FETCH_COLUMN) as $t) {
        $tickers[] = $t;
    }
}
$tickers = array_values(array_unique(array_filter($tickers, function ($t) { return $t[0] !== '^'; }))); // índices não constam no COTAHIST
if (!$tickers) {
    fwrite(STDERR, "Nenhum ativo para importar.\n");
    exit(1);
}
printf("[%s] Ativos: %s\n", date('Y-m-d H:i:s'), implode(', ', $tickers));

$imp = new ImportadorCotahist();
$afetados = [];
$erros = 0;

$processa = function ($caminho, $nome, $temporario) use ($imp, $tickers, &$afetados) {
    $t0 = microtime(true);
    $r = $imp->processar($caminho, $tickers);
    $imp->marcaProcessado($nome, $r['gravadas']);
    foreach ($r['tickers'] as $t => $n) {
        $afetados[$t] = true;
    }
    printf("  %s: %s linhas lidas, %d pregões gravados (%.1fs)\n", $nome, number_format($r['linhas'], 0, ',', '.'), $r['gravadas'], microtime(true) - $t0);
    if ($temporario) {
        @unlink($caminho);
    }
};

try {
    if (!empty($opts['arquivo'])) {
        $processa($opts['arquivo'], basename($opts['arquivo']), false);
    }

    if (!empty($opts['anos'])) {
        $partes = array_map('intval', explode('-', $opts['anos']));
        $ini = $partes[0];
        $fim = $partes[1] ?? $partes[0];
        if ($ini < 1986 || $fim > (int) date('Y') || $ini > $fim) {
            throw new InvalidArgumentException('Intervalo de anos inválido. Exemplo: --anos=2020-' . date('Y'));
        }
        for ($ano = $ini; $ano <= $fim; $ano++) {
            $nome = ImportadorCotahist::nomeAnual($ano);
            echo "  baixando $nome...\n";
            try {
                $processa($imp->baixar($nome), $nome, true);
            } catch (RuntimeException $e) {
                $erros++;
                fwrite(STDERR, '  ' . $e->getMessage() . "\n");
            }
        }
    }

    if (isset($opts['diario'])) {
        $dias = max(1, (int) ($opts['dias'] ?? 7));
        for ($i = $dias; $i >= 0; $i--) {
            $ts = strtotime("-$i days");
            if ((int) date('N', $ts) >= 6) {
                continue; // fim de semana
            }
            $nome = ImportadorCotahist::nomeDiario($ts);
            if ($imp->jaProcessado($nome)) {
                continue;
            }
            try {
                $processa($imp->baixar($nome), $nome, true);
            } catch (RuntimeException $e) {
                if ($e->getCode() === 404) {
                    echo "  $nome ainda não publicado (ou feriado).\n";
                } else {
                    $erros++;
                    fwrite(STDERR, '  ' . $e->getMessage() . "\n");
                }
            }
        }
    }
} catch (Throwable $e) {
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . "\n");
    exit(1);
}

// Recalcula preços ajustados de quem recebeu dados novos
foreach (array_keys($afetados) as $t) {
    $imp->recalcularAjuste($t);
}
foreach ($imp->log as $msg) {
    echo "  $msg\n";
}
printf("[%s] Concluído. %d ativo(s) atualizados%s.\n", date('Y-m-d H:i:s'), count($afetados), $erros ? ", $erros erro(s)" : '');
exit($erros ? 2 : 0);
