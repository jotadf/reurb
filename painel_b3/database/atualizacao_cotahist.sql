-- =====================================================================
-- Painel B3 - atualização para instalações existentes:
-- suporte à importação das Séries Históricas da B3 (COTAHIST).
-- Execute uma única vez. Instalações novas já recebem isto no schema.sql.
-- =====================================================================
SET NAMES utf8mb4;
USE painel_b3;

ALTER TABLE historico_preco
    ADD COLUMN fonte VARCHAR(8) NOT NULL DEFAULT 'brapi' AFTER volume;

CREATE TABLE IF NOT EXISTS cotahist_arquivo (
    nome           VARCHAR(40) NOT NULL PRIMARY KEY,
    registros      INT         NOT NULL DEFAULT 0,
    processado_em  DATETIME    NOT NULL
) ENGINE=InnoDB;
