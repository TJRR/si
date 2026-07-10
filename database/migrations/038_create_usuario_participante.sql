-- Vinculo entre conta de login (usuarios) e pessoa fisica inscrita
-- (participantes) - nao existia nenhuma ligacao ate aqui.
CREATE TABLE IF NOT EXISTS usuario_participante (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    participante_id INT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuario_participante_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_usuario_participante_participante FOREIGN KEY (participante_id) REFERENCES participantes (id),
    UNIQUE KEY uq_usuario_participante (usuario_id, participante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
