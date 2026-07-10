-- Formulario dinamico passa a pertencer a um unico concurso (antes era
-- global). Os 4 formularios ja existentes (ids 5-8) pertencem todos ao
-- concurso 1 (5o Premio de Inovacao), entao o DEFAULT 1 tambem cobre o
-- backfill desta migracao.
ALTER TABLE formularios_dinamicos
    ADD COLUMN concurso_id INT UNSIGNED NOT NULL DEFAULT 1,
    ADD CONSTRAINT fk_formularios_dinamicos_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id);
