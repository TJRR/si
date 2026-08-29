-- Fase 37: vinculo N:N entre um Criterio de Avaliacao e as etapas
-- ANTERIORES da mesma trilha que o avaliador pode consultar para julgar
-- aquele criterio (ex.: "Evolucao do Conceito" na Etapa 4 pode exigir ver
-- a submissao da Etapa 1) - nao e' necessariamente a etapa imediatamente
-- anterior, e um mesmo criterio pode vincular mais de uma etapa.
CREATE TABLE IF NOT EXISTS criterio_etapa_comparacao (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    criterio_id INT UNSIGNED NOT NULL,
    etapa_comparacao_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_criterio_etapa_comparacao (criterio_id, etapa_comparacao_id),
    CONSTRAINT fk_criterio_etapa_comparacao_criterio FOREIGN KEY (criterio_id) REFERENCES criterios_avaliacao (id),
    CONSTRAINT fk_criterio_etapa_comparacao_etapa FOREIGN KEY (etapa_comparacao_id) REFERENCES etapas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
