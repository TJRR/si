-- Suporta tanto o e-mail de "defina sua senha" no primeiro acesso (apos
-- homologacao) quanto uma futura tela de "esqueci minha senha".
CREATE TABLE IF NOT EXISTS tokens_senha (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    token VARCHAR(100) NOT NULL,
    tipo ENUM('definir', 'recuperar') NOT NULL DEFAULT 'definir',
    expira_em DATETIME NOT NULL,
    usado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tokens_senha_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    UNIQUE KEY uq_tokens_senha_token (token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
