<?php
require_once __DIR__ . '/Model.php';
require_once __DIR__ . '/../dto/Usuario.php';

class Login extends Model
{
    /** Retorna Usuario se CPF e senha conferem, ou false. */
    public function logar($cpf, $senha)
    {
        $cpf = preg_replace('/\D/', '', (string) $cpf);
        $r = $this->um('SELECT id, nome, cpf, email, senha, id_perfil, ativo FROM usuario WHERE cpf = ? AND ativo = 1', [$cpf]);
        if (!$r || !password_verify((string) $senha, $r['senha'])) {
            return false;
        }
        if (password_needs_rehash($r['senha'], PASSWORD_DEFAULT)) {
            $this->consulta('UPDATE usuario SET senha = ? WHERE id = ?', [password_hash($senha, PASSWORD_DEFAULT), $r['id']]);
        }
        $u = new Usuario();
        $u->id     = (int) $r['id'];
        $u->nome   = $r['nome'];
        $u->cpf    = $r['cpf'];
        $u->email  = $r['email'];
        $u->perfil = (int) $r['id_perfil'];
        $u->ativo  = (int) $r['ativo'];
        return $u;
    }
}
