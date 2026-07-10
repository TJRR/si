CREATE TABLE IF NOT EXISTS resultados_trilha (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipe_id INT UNSIGNED NOT NULL,
    trilha_id INT UNSIGNED NOT NULL,
    nf DECIMAL(6,2) NOT NULL,
    colocacao INT UNSIGNED NULL,
    publicado_por INT UNSIGNED NULL,
    publicado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resultados_trilha_equipe FOREIGN KEY (equipe_id) REFERENCES equipes (id),
    CONSTRAINT fk_resultados_trilha_trilha FOREIGN KEY (trilha_id) REFERENCES trilhas (id),
    CONSTRAINT fk_resultados_trilha_publicado_por FOREIGN KEY (publicado_por) REFERENCES usuarios (id),
    UNIQUE KEY uq_resultados_trilha (equipe_id, trilha_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
