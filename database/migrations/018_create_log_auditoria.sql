CREATE TABLE IF NOT EXISTS log_auditoria (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NULL,
    acao VARCHAR(100) NOT NULL,
    entidade VARCHAR(100) NOT NULL,
    entidade_id INT UNSIGNED NULL,
    dados_antes JSON NULL,
    dados_depois JSON NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
