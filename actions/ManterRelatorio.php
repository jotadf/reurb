<?php

require_once('Model.php');

class ManterRelatorio extends Model {

    public function __construct() { //metodo construtor
        parent::__construct();
    }

    /**
     * Retorna todos os registros e colunas da tabela de Selagem de Lotes
     */
    public function listarSelagem($filtro = ''){
        $sql = "SELECT * FROM selagem_lotes_import $filtro ORDER BY data_formulario DESC";
        $resultado = $this->db->Execute($sql);
        $array_dados = array();
        
        while ($registro = $resultado->fetchRow()) {
            $dados = new stdClass();
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
            $array_dados[] = $dados;
        }
        return $array_dados;
    }

    /**
     * Busca uma Selagem de Lote específica pelo ID da Submissão
     */
    public function getSelagemPorId($id_submissao) {
        $sql = "SELECT * FROM selagem_lotes_import WHERE id_submissao = " . (int)$id_submissao;
        $resultado = $this->db->Execute($sql);
        
        $dados = new stdClass();
        if ($registro = $resultado->fetchRow()) {
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
        }
        return $dados;
    }

    /**
     * Retorna todos os registros e colunas da tabela de Domicílios
     */
    public function listarDomicilios($filtro = '') {

        $sql = "SELECT * FROM domicilios_import $filtro ORDER BY numero_selo ASC";
        if ($filtro == '') {
            $sql = "SELECT d.*, s.data_formulario FROM domicilios_import as d, selagem_lotes_import as s WHERE d.id_submissao_pai = s.id_submissao ORDER BY d.numero_selo ASC";
        }
        
        $resultado = $this->db->Execute($sql);
        $array_dados = array();
        
        while ($registro = $resultado->fetchRow()) {
            $dados = new stdClass();
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
            $array_dados[] = $dados;
        }
        return $array_dados;
    }

    /**
     * Busca um Domicílio específico pelo Número do Selo (Chave Primária)
     */
    public function getDomicilioPorSelo($numero_selo) {
        $sql = "SELECT * FROM domicilios_import WHERE numero_selo = '" . $numero_selo . "'";
        $resultado = $this->db->Execute($sql);
        
        $dados = new stdClass();
        if ($registro = $resultado->fetchRow()) {
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
        }
        return $dados;
    }
    /**
     * Busca um Domicílio específico pelo Número do Selo (Chave Primária)
     */
    public function existeDomicilioPorSelo($numero_selo) {
        $sql = "SELECT * FROM domicilios_import WHERE numero_selo = '" . $numero_selo . "'";
        $resultado = $this->db->Execute($sql);
    
        if ($registro = $resultado->fetchRow()) {
            return true;
        }
        return false;
    }
    public function alteraSeloSociojuridico($selo, $novo_selo, $id_submissao, $id_usuario) {
        $sql = "UPDATE cadastro_sociojuridico_import SET codigo_selo = '{$novo_selo}' WHERE id_submissao = " . $id_submissao;
        $sql_log = "INSERT INTO auditoria (tabela, acao, usuario, valor_antigo, valor_atual, atualizado) 
    VALUES ('cadastro_sociojuridico_import', 'UPDATE', '{$id_usuario}', '{$selo}', '{$novo_selo}', now())";

        $resultado = $this->db->Execute($sql);
        if ($resultado) {
            $this->db->Execute($sql_log);
        } else {
            return false; // Retorna false se a atualização falhar
        }
        return $resultado;
    }
    /**
     * Busca um Domicílio pelo ID de submissão do Lote Pai (Selagem)
     */
    public function getDomicilioPorSubmissaoPai($id_submissao_pai) {
        $sql = "SELECT * FROM domicilios_import WHERE id_submissao_pai = " . (int)$id_submissao_pai . " LIMIT 1";
        $resultado = $this->db->Execute($sql);
        
        $dados = new stdClass();
        if ($registro = $resultado->fetchRow()) {
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
        }
        return $dados;
    }
    /**
     * Retorna todos os registros e colunas da tabela de Selagem de Lotes
     */
    public function listarSelagemDomicilios($filtro = ''){
        $sql = "SELECT s.*, d.* FROM selagem_lotes_import as s, domicilios_import as d $filtro ORDER BY s.data_formulario DESC";
        $resultado = $this->db->Execute($sql);
        $array_dados = array();
        
        while ($registro = $resultado->fetchRow()) {
            $dados = new stdClass();
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
            $array_dados[] = $dados;
        }
        return $array_dados;
    }
     /**
     * Retorna todos os registros e colunas da tabela de Selagem de Lotes
     */
    public function listarSelagemSemSocioJuridico($filtro = ''){
        $sql = "SELECT s.*,d.* 
        FROM selagem_lotes_import as s, domicilios_import as d
        WHERE d.id_submissao_pai = s.id_submissao
        AND numero_selo NOT IN(SELECT codigo_selo FROM cadastro_sociojuridico_import)
        ORDER BY data_formulario DESC";
        $resultado = $this->db->Execute($sql);
        $array_dados = array();
        
        while ($registro = $resultado->fetchRow()) {
            $dados = new stdClass();
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
            $array_dados[] = $dados;
        }
        return $array_dados;
    }

    /**
     * Retorna todos os registros e colunas da tabela de Cadastro Sociojurídico
     */
    public function listarSociojuridico($filtro = '') {
        $sql = "SELECT * FROM cadastro_sociojuridico_import  $filtro ORDER BY r1_nome ASC";
        $resultado = $this->db->Execute($sql);
        $array_dados = array();
        
        while ($registro = $resultado->fetchRow()) {
            $dados = new stdClass();
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
            $array_dados[] = $dados;
        }
        return $array_dados;
    }

    /**
     * Busca o Cadastro Sociojurídico de uma família pelo ID da Submissão
     */
    public function getSociojuridicoPorId($id_submissao) {
        $sql = "SELECT * FROM cadastro_sociojuridico_import WHERE id_submissao = " . (int)$id_submissao;
        $resultado = $this->db->Execute($sql);
        
        $dados = new stdClass();
        if ($registro = $resultado->fetchRow()) {
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
        }
        return $dados;
    }
    
    /**
     * Busca o Cadastro Sociojurídico de uma família pelo Código do Selo
     */
    public function getSociojuridicoPorCodigoSelo($codigo_selo) {
        // Protege a string simples com escape básico
        $sql = "SELECT * FROM cadastro_sociojuridico_import WHERE codigo_selo = '" . $codigo_selo . "' LIMIT 1";
        $resultado = $this->db->Execute($sql);
        
        $dados = new stdClass();
        if ($registro = $resultado->fetchRow()) {
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
        }
        return $dados;
    }

    /**
     * Lista todas as caracterizações trazendo todos os campos da tabela
     * @return array Lista de objetos com o mapeamento completo do banco
     */
    public function listarCaracterizacao($filtro = '') {
        // 🔥 Alterado para buscar todas as colunas de forma irrestrita
        $sql = "SELECT * FROM caracterizacao_vulnerabilidade_import $filtro ORDER BY data_registro DESC";
                
        $resultado = $this->db->Execute($sql);
        $array_dados = array();
        
        while ($registro = $resultado->fetchRow()) {
            $dados = new stdClass();
            
            // 🔥 Mapeamento dinâmico: qualquer campo novo criado no banco 
            // será indexado automaticamente como propriedade do objeto
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
            
            $array_dados[] = $dados;
        }
        return $array_dados;
    }
    /**
     * Busca um registro completo através do ID de Submissão (Chave Primária)
     */
    public function getCaracterizacaoPorIdSubmissao($id_submissao) {
        $sql = "SELECT * FROM caracterizacao_vulnerabilidade_import WHERE id_submissao = " . (int)$id_submissao;
        $resultado = $this->db->Execute($sql);
        
        $dados = new stdClass();
        if ($registro = $resultado->fetchRow()) {
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
        }
        return $dados;
    }

    /**
     * Busca um registro através da Chave Estrangeira (Código do Selo)
     */
    public function getCaracterizacaoPorCodigoSelo($codigo_selo) {
        $sql = "SELECT * FROM caracterizacao_vulnerabilidade_import WHERE codigo_selo = '" . $codigo_selo . "'";
        $resultado = $this->db->Execute($sql);
        
        $dados = new stdClass();
        if ($registro = $resultado->fetchRow()) {
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
        }
        return $dados;
    }
        /**
     * Lista todas as caracterizações trazendo todos os campos da tabela
     * @return array Lista de objetos com o mapeamento completo do banco
     */
    public function listarCaracterizacaoSemSelagem() {
        // 🔥 Alterado para buscar todas as colunas de forma irrestrita
        $sql = "SELECT * 
                FROM caracterizacao_vulnerabilidade_import
                WHERE codigo_selo NOT IN(SELECT numero_selo FROM domicilios_import)
                ORDER BY data_registro DESC";
                
        $resultado = $this->db->Execute($sql);
        $array_dados = array();
        
        while ($registro = $resultado->fetchRow()) {
            $dados = new stdClass();
            
            // 🔥 Mapeamento dinâmico: qualquer campo novo criado no banco 
            // será indexado automaticamente como propriedade do objeto
            foreach ($registro as $coluna => $valor) {
                $dados->$coluna = $valor;
            }
            
            $array_dados[] = $dados;
        }
        return $array_dados;
    }

}

