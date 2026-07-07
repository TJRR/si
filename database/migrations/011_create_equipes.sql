CREATE TABLE IF NOT EXISTS equipes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trilha_id INT UNSIGNED NOT NULL,
    tema_desafio_id INT UNSIGNED NULL,
    nome_equipe VARCHAR(150) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipes_trilha FOREIGN KEY (trilha_id) REFERENCES trilhas (id),
    CONSTRAINT fk_equipes_tema_desafio FOREIGN KEY (tema_desafio_id) REFERENCES temas_desafios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
