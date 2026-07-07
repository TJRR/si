CREATE TABLE IF NOT EXISTS formulas_pontuacao (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etapa_id INT UNSIGNED NULL,
    trilha_id INT UNSIGNED NULL,
    template_codigo ENUM('media_ponderada_criterios', 'soma_ponderada_etapas', 'media_aritmetica') NOT NULL,
    parametros_json JSON NULL,
    CONSTRAINT fk_formulas_pontuacao_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id),
    CONSTRAINT fk_formulas_pontuacao_trilha FOREIGN KEY (trilha_id) REFERENCES trilhas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
