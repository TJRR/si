CREATE TABLE IF NOT EXISTS participantes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    cpf VARCHAR(14) NULL,
    email VARCHAR(190) NULL,
    telefone VARCHAR(20) NULL,
    vinculo_profissao VARCHAR(150) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_participantes_cpf (cpf)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
