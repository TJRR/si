CREATE TABLE IF NOT EXISTS temas_desafios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trilha_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao_longa TEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_temas_desafios_trilha FOREIGN KEY (trilha_id) REFERENCES trilhas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
