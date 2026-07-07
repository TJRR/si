CREATE TABLE IF NOT EXISTS notificacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento VARCHAR(100) NOT NULL,
    canal ENUM('email') NOT NULL DEFAULT 'email',
    template_codigo VARCHAR(100) NOT NULL,
    destinatario_usuario_id INT UNSIGNED NULL,
    destinatario_email VARCHAR(190) NOT NULL,
    assunto VARCHAR(200) NOT NULL,
    corpo TEXT NOT NULL,
    status ENUM('pendente', 'enviado', 'falhou') NOT NULL DEFAULT 'pendente',
    enviado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificacoes_usuario FOREIGN KEY (destinatario_usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
