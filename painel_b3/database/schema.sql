-- =====================================================================
-- Painel B3 - estrutura do banco (MySQL 5.7+ / 8.x ou MariaDB 10.3+)
-- =====================================================================
SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS painel_b3 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE painel_b3;

-- ---------------------------------------------------------------------
-- Gestão de acesso (mesmo modelo do REURB: usuário + perfil)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS perfil (
    id      TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    perfil  VARCHAR(40) NOT NULL
) ENGINE=InnoDB;

INSERT IGNORE INTO perfil (id, perfil) VALUES (1, 'Administrador'), (2, 'Analista');

CREATE TABLE IF NOT EXISTS usuario (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(120) NOT NULL,
    cpf        CHAR(11)     NOT NULL,
    email      VARCHAR(160) NULL,
    senha      VARCHAR(255) NOT NULL,           -- hash gerado por password_hash()
    id_perfil  TINYINT UNSIGNED NOT NULL DEFAULT 2,
    ativo      TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuario_cpf (cpf),
    CONSTRAINT fk_usuario_perfil FOREIGN KEY (id_perfil) REFERENCES perfil (id)
) ENGINE=InnoDB;

-- Usuário inicial: CPF 000.000.000-00 / senha admin123  (TROQUE APÓS O PRIMEIRO ACESSO)
INSERT IGNORE INTO usuario (nome, cpf, email, senha, id_perfil, ativo)
VALUES ('Administrador', '00000000000', NULL,
        '$2y$10$rEfJSYElo.S1U6s/F04wGegD/OAK.hLsiX7vplhEGZBVvTachYxbq', 1, 1);

-- ---------------------------------------------------------------------
-- Ativos monitorados (lista de acompanhamento)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS ativo (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ticker     VARCHAR(16)  NOT NULL,
    nome       VARCHAR(120) NULL,
    setor      VARCHAR(80)  NULL,
    ordem      INT          NOT NULL DEFAULT 0,
    ativo      TINYINT(1)   NOT NULL DEFAULT 1,
    criado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_ativo_ticker (ticker)
) ENGINE=InnoDB;

INSERT IGNORE INTO ativo (ticker, nome, setor, ordem) VALUES
('PETR4', 'Petrobras PN',  'Petróleo e gás', 1),
('VALE3', 'Vale ON',       'Mineração',      2),
('ITUB4', 'Itaú Unibanco PN', 'Bancos',      3),
('MGLU3', 'Magazine Luiza ON', 'Varejo',     4);

-- ---------------------------------------------------------------------
-- Cache de dados da API
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS cotacao (
    ticker               VARCHAR(16)   NOT NULL PRIMARY KEY,
    nome_curto           VARCHAR(120)  NULL,
    nome_longo           VARCHAR(200)  NULL,
    moeda                VARCHAR(8)    NULL,
    preco                DECIMAL(18,4) NULL,
    variacao             DECIMAL(18,4) NULL,
    variacao_pct         DECIMAL(10,4) NULL,
    abertura             DECIMAL(18,4) NULL,
    fechamento_anterior  DECIMAL(18,4) NULL,
    maxima_dia           DECIMAL(18,4) NULL,
    minima_dia           DECIMAL(18,4) NULL,
    volume               BIGINT        NULL,
    valor_mercado        DECIMAL(22,2) NULL,
    pl                   DECIMAL(14,4) NULL,
    lpa                  DECIMAL(14,4) NULL,
    minima_52s           DECIMAL(18,4) NULL,
    maxima_52s           DECIMAL(18,4) NULL,
    logo_url             VARCHAR(255)  NULL,
    mercado_em           DATETIME      NULL,   -- regularMarketTime
    atualizado_em        DATETIME      NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS historico_preco (
    ticker               VARCHAR(16)   NOT NULL,
    data                 DATE          NOT NULL,
    abertura             DECIMAL(18,4) NULL,
    maxima               DECIMAL(18,4) NULL,
    minima               DECIMAL(18,4) NULL,
    fechamento           DECIMAL(18,4) NOT NULL,
    fechamento_ajustado  DECIMAL(18,4) NULL,
    volume               BIGINT        NULL,
    PRIMARY KEY (ticker, data)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dividendo (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ticker          VARCHAR(16)   NOT NULL,
    tipo            VARCHAR(40)   NOT NULL,          -- DIVIDENDO, JCP, RENDIMENTO...
    valor           DECIMAL(18,8) NOT NULL,
    data_com        DATE          NULL,
    data_pagamento  DATE          NULL,
    referente       VARCHAR(80)   NULL,
    UNIQUE KEY uk_dividendo (ticker, tipo, data_com, valor),
    KEY idx_dividendo_ticker (ticker, data_com)
) ENGINE=InnoDB;

-- Controle de quando cada dado foi buscado na API
CREATE TABLE IF NOT EXISTS api_sync (
    ticker         VARCHAR(16) NOT NULL,
    tipo           VARCHAR(20) NOT NULL,   -- cotacao | historico
    faixa          VARCHAR(10) NOT NULL DEFAULT '',
    atualizado_em  DATETIME    NOT NULL,
    PRIMARY KEY (ticker, tipo, faixa)
) ENGINE=InnoDB;

-- Registro de chamadas (para acompanhar o consumo mensal do plano)
CREATE TABLE IF NOT EXISTS api_log (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    url        VARCHAR(255) NOT NULL,
    status     SMALLINT     NOT NULL,
    mensagem   VARCHAR(255) NULL,
    criado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_api_log_data (criado_em)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- Carteira de cada usuário
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS carteira_posicao (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_usuario   INT UNSIGNED  NOT NULL,
    ticker       VARCHAR(16)   NOT NULL,
    quantidade   DECIMAL(18,4) NOT NULL,
    preco_medio  DECIMAL(18,4) NOT NULL,
    data_compra  DATE          NULL,
    observacao   VARCHAR(255)  NULL,
    UNIQUE KEY uk_posicao (id_usuario, ticker),
    CONSTRAINT fk_posicao_usuario FOREIGN KEY (id_usuario) REFERENCES usuario (id) ON DELETE CASCADE
) ENGINE=InnoDB;
