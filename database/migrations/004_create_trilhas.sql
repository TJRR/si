CREATE TABLE IF NOT EXISTS trilhas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    descricao TEXT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_trilhas_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    UNIQUE KEY uq_trilhas_concurso_slug (concurso_id, slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
