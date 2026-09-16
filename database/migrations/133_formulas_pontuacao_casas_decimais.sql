ALTER TABLE formulas_pontuacao
    ADD COLUMN casas_decimais TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER expressao;
