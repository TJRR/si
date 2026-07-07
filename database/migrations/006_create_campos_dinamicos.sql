CREATE TABLE IF NOT EXISTS campos_dinamicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    formulario_id INT UNSIGNED NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    rotulo VARCHAR(150) NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    obrigatorio TINYINT(1) NOT NULL DEFAULT 0,
    config_json JSON NULL,
    CONSTRAINT fk_campos_dinamicos_formulario FOREIGN KEY (formulario_id) REFERENCES formularios_dinamicos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
