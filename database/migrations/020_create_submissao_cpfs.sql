CREATE TABLE IF NOT EXISTS submissao_cpfs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submissao_id INT UNSIGNED NOT NULL,
    trilha_id INT UNSIGNED NOT NULL,
    cpf VARCHAR(11) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_submissao_cpfs_submissao FOREIGN KEY (submissao_id) REFERENCES submissoes (id),
    CONSTRAINT fk_submissao_cpfs_trilha FOREIGN KEY (trilha_id) REFERENCES trilhas (id),
    UNIQUE KEY uq_submissao_cpfs_trilha_cpf (trilha_id, cpf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
