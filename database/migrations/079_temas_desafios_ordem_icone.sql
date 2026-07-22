-- Fase 19 (#102/#104): ordem manual de exibicao dos Temas (hoje so' tem
-- ordenacao alfabetica) e icone proprio do Desafio, independente do icone
-- do Tema pai (os dois niveis ficam com icone, por decisao do usuario).
ALTER TABLE temas
    ADD COLUMN ordem INT UNSIGNED NOT NULL DEFAULT 0 AFTER descricao_longa;

ALTER TABLE desafios
    ADD COLUMN icone VARCHAR(50) NULL AFTER pergunta;
