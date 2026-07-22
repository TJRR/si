-- Fase 19 (#102): ordem manual tambem para Desafio, mesma logica ja
-- aplicada a Tema na migration 079 (a home nao deve depender de ordem
-- alfabetica).
ALTER TABLE desafios
    ADD COLUMN ordem INT UNSIGNED NOT NULL DEFAULT 0 AFTER icone;
