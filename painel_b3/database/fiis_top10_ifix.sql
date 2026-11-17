-- =====================================================================
-- Painel B3 - inclui os 10 FIIs de maior peso no IFIX
-- Fonte: carteira teórica do IFIX (B3) de 16/11/2026.
-- A B3 revisa a carteira a cada quadrimestre e os pesos mudam com o preço.
--
-- Pode ser executado mais de uma vez: se o código já existir, só atualiza
-- nome, setor e ordem, e volta a marcar o ativo como monitorado.
-- =====================================================================
SET NAMES utf8mb4;
USE painel_b3;

INSERT INTO ativo (ticker, nome, setor, ordem, ativo) VALUES
('KNCR11', 'FII Kinea Rendimentos Imobiliários', 'FII - Papel (CRI)',            101, 1), -- peso 9,82%
('KNIP11', 'FII Kinea Índices de Preços',        'FII - Papel (CRI)',            102, 1), -- peso 5,00%
('BTLG11', 'FII BTG Pactual Logística',          'FII - Logística',              103, 1), -- peso 4,95%
('HGLG11', 'FII Patria Log',                     'FII - Logística',              104, 1), -- peso 4,74%
('XPML11', 'FII XP Malls',                       'FII - Shoppings',              105, 1), -- peso 4,22%
('MXRF11', 'FII Maxi Renda',                     'FII - Papel (CRI)',            106, 1), -- peso 3,54%
('XPLG11', 'FII XP Log',                         'FII - Logística',              107, 1), -- peso 3,33%
('KNRI11', 'FII Kinea Renda Imobiliária',        'FII - Híbrido',                108, 1), -- peso 3,05%
('TRXF11', 'FII TRX Real Estate',                'FII - Renda urbana',           109, 1), -- peso 2,48%
('GGRC11', 'FII GGR Covepi Renda',               'FII - Logística/Industrial',   110, 1)  -- peso 2,06%
ON DUPLICATE KEY UPDATE
    nome  = VALUES(nome),
    setor = VALUES(setor),
    ordem = VALUES(ordem),
    ativo = 1;

-- Conferência
SELECT ticker, nome, setor, ordem FROM ativo WHERE ticker LIKE '%11' ORDER BY ordem;
