-- Fase 34: vinculo opcional de um horario de Mentoria/Oficina a uma etapa.
-- Vinculado = so' enxerga e se inscreve quem esta' habilitado aquela etapa,
-- pelo MESMO criterio que ja libera a submissao (classificado na etapa
-- ANTERIOR - App\Services\AcessoEtapaService::motivoBloqueio).
--
-- NULL de proposito, e nao NOT NULL com backfill: NULL significa "aberto a
-- todos" e preserva integralmente o comportamento dos horarios que ja
-- existem em producao, criados antes desta fase. Nenhum horario precisa ser
-- migrado, e o admin so' passa a restringir quando escolher explicitamente
-- uma etapa no formulario.
--
-- Como etapa pertence a uma trilha, vincular a uma etapa restringe o evento
-- aquela trilha por construcao - isso e' intencional, nao efeito colateral.
ALTER TABLE mentoria_horarios
    ADD COLUMN etapa_id INT UNSIGNED NULL AFTER concurso_id,
    ADD CONSTRAINT fk_mentoria_horarios_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id),
    ADD KEY idx_mentoria_horarios_etapa (etapa_id);

ALTER TABLE oficina_horarios
    ADD COLUMN etapa_id INT UNSIGNED NULL AFTER concurso_id,
    ADD CONSTRAINT fk_oficina_horarios_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id),
    ADD KEY idx_oficina_horarios_etapa (etapa_id);
