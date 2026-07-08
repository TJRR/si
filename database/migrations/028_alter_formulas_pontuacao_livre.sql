ALTER TABLE formulas_pontuacao
    DROP COLUMN template_codigo,
    DROP COLUMN parametros_json,
    ADD COLUMN expressao TEXT NOT NULL AFTER trilha_id;
