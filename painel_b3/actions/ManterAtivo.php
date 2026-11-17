<?php
require_once __DIR__ . '/Model.php';

class ManterAtivo extends Model
{
    /** Ativos da lista de acompanhamento. */
    public function listar($somenteAtivos = true)
    {
        $sql = 'SELECT id, ticker, nome, setor, ordem, ativo FROM ativo'
             . ($somenteAtivos ? ' WHERE ativo = 1' : '')
             . ' ORDER BY ordem, ticker';
        return $this->todos($sql);
    }

    public function tickers()
    {
        return array_column($this->listar(true), 'ticker');
    }

    public function salvar(array $d)
    {
        $ticker = normaliza_ticker($d['ticker'] ?? '');
        if (!$ticker) {
            throw new InvalidArgumentException('Código de negociação inválido. Use, por exemplo, PETR4 ou BOVA11.');
        }
        $id = (int) ($d['id'] ?? 0);
        $dup = $this->um('SELECT id FROM ativo WHERE ticker = ? AND id <> ?', [$ticker, $id]);
        if ($dup) {
            throw new InvalidArgumentException($ticker . ' já está na lista.');
        }
        $params = [
            $ticker,
            trim($d['nome'] ?? '') ?: null,
            trim($d['setor'] ?? '') ?: null,
            (int) ($d['ordem'] ?? 0),
            !empty($d['ativo']) ? 1 : 0,
        ];
        if ($id > 0) {
            $params[] = $id;
            $this->consulta('UPDATE ativo SET ticker=?, nome=?, setor=?, ordem=?, ativo=? WHERE id=?', $params);
            return $id;
        }
        $this->consulta('INSERT INTO ativo (ticker, nome, setor, ordem, ativo) VALUES (?,?,?,?,?)', $params);
        return (int) $this->db->lastInsertId();
    }

    /** Preenche o nome com o retornado pela API quando o usuário não informou. */
    public function completarNome($ticker, $nome)
    {
        if ($nome) {
            $this->consulta("UPDATE ativo SET nome = ? WHERE ticker = ? AND (nome IS NULL OR nome = '')", [$nome, $ticker]);
        }
    }

    public function excluir($id)
    {
        $this->consulta('DELETE FROM ativo WHERE id = ?', [(int) $id]);
    }
}
