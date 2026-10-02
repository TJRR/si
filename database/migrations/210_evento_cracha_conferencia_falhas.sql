-- Fase 60 (pendencia 27): tentativas falhas da tela "Conferir cracha", em
-- tabela propria, separada da contagem de "Conectar com participante".
CREATE TABLE IF NOT EXISTS evento_cracha_conferencia_falhas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    evento_id INT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_evento_cracha_conferencia_falhas_usuario (usuario_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
