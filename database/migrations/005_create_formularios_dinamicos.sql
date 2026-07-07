CREATE TABLE IF NOT EXISTS formularios_dinamicos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    status ENUM('rascunho', 'publicado', 'arquivado') NOT NULL DEFAULT 'rascunho',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
