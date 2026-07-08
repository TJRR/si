ALTER TABLE criterios_avaliacao
    ADD COLUMN codigo VARCHAR(20) NOT NULL AFTER etapa_id,
    ADD UNIQUE KEY uq_criterios_etapa_codigo (etapa_id, codigo);
