CREATE TABLE IF NOT EXISTS avaliador_designacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submissao_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    atribuido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atribuido_por INT UNSIGNED NULL,
    CONSTRAINT fk_avaliador_designacoes_submissao FOREIGN KEY (submissao_id) REFERENCES submissoes (id),
    CONSTRAINT fk_avaliador_designacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_avaliador_designacoes_atribuido_por FOREIGN KEY (atribuido_por) REFERENCES usuarios (id),
    UNIQUE KEY uq_avaliador_designacoes (submissao_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
