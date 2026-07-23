-- Fase 20 (#117): regra de desempate ganha etapa_id explicito + tipo, pra
-- suportar criterios de desempate que nao sao nota de avaliacao (ex: "quem
-- se inscreveu primeiro"). Antes, a etapa so' era conhecida indiretamente
-- via criterio_avaliacao_id -> criterios_avaliacao.etapa_id - uma regra sem
-- criterio nao tinha como apontar pra etapa nenhuma.
ALTER TABLE regras_desempate
    ADD COLUMN etapa_id INT UNSIGNED NULL AFTER trilha_id,
    ADD COLUMN tipo ENUM('criterio', 'data_submissao') NOT NULL DEFAULT 'criterio' AFTER etapa_id,
    MODIFY COLUMN criterio_avaliacao_id INT UNSIGNED NULL;

-- Backfill: toda regra ja existente e' tipo 'criterio', entao a etapa dela
-- e' a mesma do criterio que ja referencia - zero mudanca de comportamento.
UPDATE regras_desempate rd
INNER JOIN criterios_avaliacao ca ON ca.id = rd.criterio_avaliacao_id
SET rd.etapa_id = ca.etapa_id;

ALTER TABLE regras_desempate
    MODIFY COLUMN etapa_id INT UNSIGNED NOT NULL,
    ADD CONSTRAINT fk_regras_desempate_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id);
