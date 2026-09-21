-- Fase 48: modalidade de atividade (presencial/online/hibrido), segundo
-- codigo de confirmacao de presenca (para quem participa online) e cadastro
-- de Facilitador (instrutor/professor/palestrante) por atividade, exigido
-- pelo layout da exportacao EJURR. modalidade_acesso registra, por
-- check-in, qual dos dois caminhos foi de fato usado - necessario porque
-- atividade hibrida aceita os dois ao mesmo tempo.
ALTER TABLE evento_atividades
    ADD COLUMN modalidade ENUM('presencial','online','hibrido') NOT NULL DEFAULT 'presencial' AFTER local,
    ADD COLUMN codigo_presenca_online CHAR(5) NULL UNIQUE AFTER codigo_atividade;

ALTER TABLE evento_checkins
    ADD COLUMN modalidade_acesso ENUM('presencial','online') NOT NULL DEFAULT 'presencial' AFTER checkin_em;

-- removido_em: designacao de facilitador alimenta a exportacao EJURR (que
-- pode ser gerada em qualquer momento, inclusive apos a atividade
-- acontecer), entao "remover" nunca e' um DELETE fisico - mesmo espirito de
-- preservacao historica ja usado em evento_checkins (Fase 47).
CREATE TABLE IF NOT EXISTS evento_atividade_facilitadores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    atividade_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    documento VARCHAR(30) NOT NULL,
    tipo_documento VARCHAR(20) NOT NULL DEFAULT 'CPF',
    cargo VARCHAR(150) NULL,
    categoria VARCHAR(150) NULL,
    orgao_origem VARCHAR(150) NULL,
    minicurriculo TEXT NULL,
    foto_path VARCHAR(255) NULL,
    perfil VARCHAR(20) NOT NULL DEFAULT 'instrutor',
    designado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    removido_em DATETIME NULL,
    CONSTRAINT fk_evento_atividade_facilitadores_atividade FOREIGN KEY (atividade_id) REFERENCES evento_atividades (id),
    CONSTRAINT fk_evento_atividade_facilitadores_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    UNIQUE KEY uq_evento_atividade_facilitadores (atividade_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
