CREATE TABLE IF NOT EXISTS concursos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    slug VARCHAR(160) NOT NULL,
    descricao TEXT NULL,
    data_inicio DATE NULL,
    data_fim DATE NULL,
    status ENUM('rascunho', 'ativo', 'encerrado') NOT NULL DEFAULT 'rascunho',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_concursos_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
