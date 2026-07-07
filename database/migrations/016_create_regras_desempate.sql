CREATE TABLE IF NOT EXISTS regras_desempate (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trilha_id INT UNSIGNED NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criterio_referencia VARCHAR(150) NOT NULL,
    direcao ENUM('desc', 'asc') NOT NULL DEFAULT 'desc',
    CONSTRAINT fk_regras_desempate_trilha FOREIGN KEY (trilha_id) REFERENCES trilhas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
