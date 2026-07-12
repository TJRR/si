-- Quantos avaliadores de cada categoria sao exigidos por submissao numa etapa
-- (Fase 10). Substitui qtd_avaliadores_por_submissao quando a etapa usa o
-- modo_designacao 'sorteio_categoria' -- a coluna antiga continua servindo os
-- outros modos (manual/aberto/automatico), sem alteracao.
CREATE TABLE IF NOT EXISTS etapa_categoria_avaliadores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etapa_id INT UNSIGNED NOT NULL,
    categoria_avaliador_id INT UNSIGNED NOT NULL,
    quantidade INT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT fk_etapa_categoria_avaliadores_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id),
    CONSTRAINT fk_etapa_categoria_avaliadores_categoria FOREIGN KEY (categoria_avaliador_id) REFERENCES categorias_avaliador (id),
    UNIQUE KEY uq_etapa_categoria_avaliadores (etapa_id, categoria_avaliador_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
