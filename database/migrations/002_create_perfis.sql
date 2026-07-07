CREATE TABLE IF NOT EXISTS perfis (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(50) NOT NULL,
    nome_exibicao VARCHAR(100) NOT NULL,
    descricao VARCHAR(255) NULL,
    UNIQUE KEY uq_perfis_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
