<?php
/**
 * Classe base de acesso ao banco (mesmo papel do Model.php do REURB),
 * agora com PDO e consultas preparadas.
 */
require_once __DIR__ . '/../funcoes.php';

class Model
{
    /** @var PDO */
    protected $db;
    private static $conexao = null;

    public function __construct()
    {
        $this->db = self::conexao();
    }

    public static function conexao()
    {
        if (self::$conexao === null) {
            $c = config('db');
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $c['host'], $c['port'], $c['database'], $c['charset']);
            self::$conexao = new PDO($dsn, $c['username'], $c['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            self::$conexao->exec("SET time_zone = '" . date('P') . "'");
        }
        return self::$conexao;
    }

    protected function consulta($sql, array $params = [])
    {
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st;
    }

    protected function todos($sql, array $params = [])
    {
        return $this->consulta($sql, $params)->fetchAll();
    }

    protected function um($sql, array $params = [])
    {
        $linha = $this->consulta($sql, $params)->fetch();
        return $linha === false ? null : $linha;
    }
}
