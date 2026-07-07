CREATE TABLE IF NOT EXISTS etapas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trilha_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    data_inicio DATE NULL,
    data_fim DATE NULL,
    formulario_dinamico_id INT UNSIGNED NULL,
    regra_transicao_tipo ENUM('numero_fixo', 'percentual', 'nota_corte') NULL,
    regra_transicao_valor DECIMAL(10,2) NULL,
    CONSTRAINT fk_etapas_trilha FOREIGN KEY (trilha_id) REFERENCES trilhas (id),
    CONSTRAINT fk_etapas_formulario FOREIGN KEY (formulario_dinamico_id) REFERENCES formularios_dinamicos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
