<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

try {
    $pdo = db();
    $from = isset($_GET['from']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['from']) ? $_GET['from'] : null;
    $to   = isset($_GET['to']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['to']) ? $_GET['to'] : null;

    $params = [];
    $whereLote = '';
    $whereDom = '';
    $whereSoc = '';
    $whereVul = '';
    if ($from) {
        $whereLote .= ' AND data_formulario >= :from';
        $whereSoc .= ' AND data_registro >= :from';
        $whereVul .= ' AND data_registro >= :from';
        $params[':from'] = $from;
    }
    if ($to) {
        $whereLote .= ' AND data_formulario < DATE_ADD(:to, INTERVAL 1 DAY)';
        $whereSoc .= ' AND data_registro < DATE_ADD(:to, INTERVAL 1 DAY)';
        $whereVul .= ' AND data_registro < DATE_ADD(:to, INTERVAL 1 DAY)';
        $params[':to'] = $to;
    }

    $q = function(string $sql, array $p = []) use ($pdo) {
        $st = $pdo->prepare($sql); $st->execute($p); return $st;
    };

    // Indicadores principais
    $lotes = (int)$q("SELECT COUNT(*) FROM selagem_lotes_import WHERE 1=1 $whereLote", $params)->fetchColumn();
    $domicilios = (int)$q("SELECT COUNT(*) FROM domicilios_import d WHERE 1=1" . ($from || $to ? " AND EXISTS (SELECT 1 FROM selagem_lotes_import l WHERE l.id_submissao=d.id_submissao_pai $whereLote)" : ''), $params)->fetchColumn();
    $socio = (int)$q("SELECT COUNT(*) FROM cadastro_sociojuridico_import WHERE 1=1 $whereSoc", $params)->fetchColumn();
    $vul = (int)$q("SELECT COUNT(*) FROM caracterizacao_vulnerabilidade_import WHERE 1=1 $whereVul", $params)->fetchColumn();

    // Comparação por código do selo. Normaliza espaços e diferenciação de maiúsculas/minúsculas.
    // Usa DISTINCT para evitar que duplicidades de código distorçam o indicador.
    $sqlMatch = "
        SELECT
          COUNT(DISTINCT d.numero_selo) AS total_domicilios,
          COUNT(DISTINCT CASE WHEN s.codigo_selo IS NOT NULL THEN d.numero_selo END) AS domicilios_com_socio,
          COUNT(DISTINCT CASE WHEN s.codigo_selo IS NULL THEN d.numero_selo END) AS domicilios_sem_socio,
          COUNT(DISTINCT s.codigo_selo) AS total_socio,
          COUNT(DISTINCT CASE WHEN d.numero_selo IS NOT NULL THEN s.codigo_selo END) AS socio_com_domicilio,
          COUNT(DISTINCT CASE WHEN d.numero_selo IS NULL THEN s.codigo_selo END) AS socio_sem_domicilio
        FROM (SELECT DISTINCT numero_selo FROM domicilios_import) d
        LEFT JOIN (SELECT DISTINCT codigo_selo FROM cadastro_sociojuridico_import) s
          ON TRIM(UPPER(s.codigo_selo)) = TRIM(UPPER(d.numero_selo))";
    $match = $q($sqlMatch)->fetch();

    // Datas
    $porMes = $q("SELECT DATE_FORMAT(data_formulario, '%Y-%m') mes, COUNT(*) total FROM selagem_lotes_import WHERE data_formulario IS NOT NULL $whereLote GROUP BY DATE_FORMAT(data_formulario, '%Y-%m') ORDER BY mes", $params)->fetchAll();
    $socioMes = $q("SELECT DATE_FORMAT(data_registro, '%Y-%m') mes, COUNT(*) total FROM cadastro_sociojuridico_import WHERE data_registro IS NOT NULL $whereSoc GROUP BY DATE_FORMAT(data_registro, '%Y-%m') ORDER BY mes", $params)->fetchAll();

    // Distribuições úteis para acompanhamento
    $ocupacao = $q("SELECT COALESCE(NULLIF(TRIM(tipo_ocupacao_imovel), ''), 'Não informado') categoria, COUNT(*) total FROM domicilios_import GROUP BY categoria ORDER BY total DESC LIMIT 12")->fetchAll();
    $localizacao = $q("SELECT COALESCE(NULLIF(TRIM(localizacao_domicilio), ''), 'Não informado') categoria, COUNT(*) total FROM domicilios_import GROUP BY categoria ORDER BY total DESC")->fetchAll();
    $vulFields = [
        'pcd_no_domicilio' => 'Domicílio com PCD',
        'ocorrencia_inundacao' => 'Ocorrência de inundação',
        'possui_banheiro' => 'Possui banheiro',
        'energia_acesso' => 'Acesso à energia',
        'agua_acesso' => 'Acesso à água',
        'esgotamento_sanitario' => 'Esgotamento sanitário',
        'coleta_lixo' => 'Coleta de lixo',
    ];
    $vulnerabilidades = [];
    foreach ($vulFields as $field => $label) {
        $rows = $q("SELECT COALESCE(NULLIF(TRIM(`$field`), ''), 'Não informado') categoria, COUNT(*) total FROM caracterizacao_vulnerabilidade_import GROUP BY categoria ORDER BY total DESC LIMIT 6")->fetchAll();
        $vulnerabilidades[$field] = ['label' => $label, 'rows' => $rows];
    }

    echo json_encode([
        'ok' => true,
        'indicadores' => [
            'lotes' => $lotes,
            'domicilios' => $domicilios,
            'sociojuridicos' => $socio,
            'vulnerabilidade' => $vul,
            'domicilios_sem_socio' => (int)$match['domicilios_sem_socio'],
            'socio_sem_domicilio' => (int)$match['socio_sem_domicilio'],
            'domicilios_com_socio' => (int)$match['domicilios_com_socio'],
            'socio_com_domicilio' => (int)$match['socio_com_domicilio'],
        ],
        'matching' => [
            ['categoria'=>'Domicílios sem cadastro sociojurídico', 'total'=>(int)$match['domicilios_sem_socio']],
            ['categoria'=>'Cadastros sociojurídicos sem domicílio', 'total'=>(int)$match['socio_sem_domicilio']],
        ],
        'meses_lotes' => $porMes,
        'meses_socio' => $socioMes,
        'ocupacao' => $ocupacao,
        'localizacao' => $localizacao,
        'vulnerabilidades' => $vulnerabilidades,
        'gerado_em' => date('d/m/Y H:i:s'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'erro'=>'Erro ao consultar o banco de dados.','detalhe'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
