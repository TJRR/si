ALTER TABLE regras_desempate
    DROP COLUMN criterio_referencia,
    ADD COLUMN criterio_avaliacao_id INT UNSIGNED NOT NULL AFTER trilha_id,
    ADD CONSTRAINT fk_regras_desempate_criterio FOREIGN KEY (criterio_avaliacao_id) REFERENCES criterios_avaliacao (id);
