<?php
require_once __DIR__ . '/Model.php';

class ManterUsuario extends Model
{
    public function listar()
    {
        return $this->todos('SELECT u.id, u.nome, u.cpf, u.email, u.id_perfil, u.ativo, p.perfil
                               FROM usuario u JOIN perfil p ON p.id = u.id_perfil ORDER BY u.nome');
    }

    public function perfis()
    {
        return $this->todos('SELECT id, perfil FROM perfil ORDER BY id');
    }

    public function salvar(array $d)
    {
        $cpf = preg_replace('/\D/', '', $d['cpf'] ?? '');
        if (strlen($cpf) !== 11 || trim($d['nome'] ?? '') === '') {
            throw new InvalidArgumentException('Informe nome e CPF válidos.');
        }
        $perfil = (int) ($d['id_perfil'] ?? 2);
        $ativo  = !empty($d['ativo']) ? 1 : 0;
        $email  = trim($d['email'] ?? '') ?: null;
        $id     = (int) ($d['id'] ?? 0);
        $senha  = (string) ($d['senha'] ?? '');

        $dup = $this->um('SELECT id FROM usuario WHERE cpf = ? AND id <> ?', [$cpf, $id]);
        if ($dup) {
            throw new InvalidArgumentException('Já existe um usuário com este CPF.');
        }

        if ($id > 0) {
            $this->consulta('UPDATE usuario SET nome=?, cpf=?, email=?, id_perfil=?, ativo=? WHERE id=?',
                [trim($d['nome']), $cpf, $email, $perfil, $ativo, $id]);
            if ($senha !== '') {
                $this->alterarSenha($id, $senha);
            }
            return $id;
        }
        if (strlen($senha) < 6) {
            throw new InvalidArgumentException('A senha deve ter pelo menos 6 caracteres.');
        }
        $this->consulta('INSERT INTO usuario (nome, cpf, email, senha, id_perfil, ativo) VALUES (?,?,?,?,?,?)',
            [trim($d['nome']), $cpf, $email, password_hash($senha, PASSWORD_DEFAULT), $perfil, $ativo]);
        return (int) $this->db->lastInsertId();
    }

    public function alterarSenha($id, $senha)
    {
        if (strlen($senha) < 6) {
            throw new InvalidArgumentException('A senha deve ter pelo menos 6 caracteres.');
        }
        $this->consulta('UPDATE usuario SET senha = ? WHERE id = ?', [password_hash($senha, PASSWORD_DEFAULT), (int) $id]);
    }

    public function confereSenha($id, $senha)
    {
        $r = $this->um('SELECT senha FROM usuario WHERE id = ?', [(int) $id]);
        return $r && password_verify($senha, $r['senha']);
    }

    public function excluir($id)
    {
        $this->consulta('DELETE FROM usuario WHERE id = ?', [(int) $id]);
    }
}
