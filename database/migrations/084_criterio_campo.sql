-- Fase 19 (#10): vinculo campo do formulario <-> criterio de avaliacao,
-- pra tela do avaliador mostrar so' o conteudo relevante em cada aba de
-- criterio. Criterio sem nenhum vinculo continua mostrando a ficha
-- inteira (fallback, ver AvaliacaoController::notar()).
CREATE TABLE IF NOT EXISTS criterio_campo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    criterio_id INT UNSIGNED NOT NULL,
    campo_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_criterio_campo (criterio_id, campo_id),
    CONSTRAINT fk_criterio_campo_criterio FOREIGN KEY (criterio_id) REFERENCES criterios_avaliacao (id),
    CONSTRAINT fk_criterio_campo_campo FOREIGN KEY (campo_id) REFERENCES campos_dinamicos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
