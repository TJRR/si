-- Fase 17 (Melhoria 1): feedback do avaliador por submissao inteira (modo
-- "submissao" de etapas.modo_feedback_avaliador) - mesmo modelo de
-- notas_lancadas, 1 feedback por avaliador por submissao.
CREATE TABLE IF NOT EXISTS feedback_submissao (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submissao_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    feedback TEXT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_feedback_submissao_submissao FOREIGN KEY (submissao_id) REFERENCES submissoes (id),
    CONSTRAINT fk_feedback_submissao_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    UNIQUE KEY uq_feedback_submissao (submissao_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
