<?php
require_once __DIR__ . '/Model.php';

class ManterCarteira extends Model
{
    public function listar($idUsuario)
    {
        return $this->todos('SELECT id, ticker, quantidade, preco_medio, data_compra, observacao
                               FROM carteira_posicao WHERE id_usuario = ? ORDER BY ticker', [(int) $idUsuario]);
    }

    public function salvar($idUsuario, array $d)
    {
        $ticker = normaliza_ticker($d['ticker'] ?? '');
        $qtd    = para_float($d['quantidade'] ?? '');
        $preco  = para_float($d['preco_medio'] ?? '');
        if (!$ticker || $qtd === null || $qtd <= 0 || $preco === null || $preco < 0) {
            throw new InvalidArgumentException('Informe código, quantidade e preço médio válidos.');
        }
        $data = trim($d['data_compra'] ?? '');
        $data = preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) ? $data : null;
        $obs  = trim($d['observacao'] ?? '') ?: null;
        $id   = (int) ($d['id'] ?? 0);

        $dup = $this->um('SELECT id FROM carteira_posicao WHERE id_usuario = ? AND ticker = ? AND id <> ?', [(int) $idUsuario, $ticker, $id]);
        if ($dup) {
            throw new InvalidArgumentException($ticker . ' já está na carteira. Edite a posição existente.');
        }
        if ($id > 0) {
            $this->consulta('UPDATE carteira_posicao SET ticker=?, quantidade=?, preco_medio=?, data_compra=?, observacao=?
                              WHERE id=? AND id_usuario=?', [$ticker, $qtd, $preco, $data, $obs, $id, (int) $idUsuario]);
            return $id;
        }
        $this->consulta('INSERT INTO carteira_posicao (id_usuario, ticker, quantidade, preco_medio, data_compra, observacao)
                         VALUES (?,?,?,?,?,?)', [(int) $idUsuario, $ticker, $qtd, $preco, $data, $obs]);
        return (int) $this->db->lastInsertId();
    }

    public function excluir($idUsuario, $id)
    {
        $this->consulta('DELETE FROM carteira_posicao WHERE id = ? AND id_usuario = ?', [(int) $id, (int) $idUsuario]);
    }
}
