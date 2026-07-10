CREATE TABLE IF NOT EXISTS notas_lancadas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submissao_id INT UNSIGNED NOT NULL,
    criterio_avaliacao_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    nota DECIMAL(5,2) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_notas_lancadas_submissao FOREIGN KEY (submissao_id) REFERENCES submissoes (id),
    CONSTRAINT fk_notas_lancadas_criterio FOREIGN KEY (criterio_avaliacao_id) REFERENCES criterios_avaliacao (id),
    CONSTRAINT fk_notas_lancadas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    UNIQUE KEY uq_notas_lancadas (submissao_id, criterio_avaliacao_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
