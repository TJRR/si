CREATE TABLE IF NOT EXISTS criterios_avaliacao (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etapa_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    peso DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    escala_min DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    escala_max DECIMAL(5,2) NOT NULL DEFAULT 10.00,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_criterios_avaliacao_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
