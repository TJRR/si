-- Fase 19 (#17): resultado da etapa de inscricao (equipes homologadas) na
-- home, com publicacao controlada pelo Admin e limiar configuravel por
-- trilha (nao hardcoded) de quantos integrantes homologados uma equipe
-- precisa ter para contar como homologada.
ALTER TABLE trilhas
    ADD COLUMN minimo_integrantes_homologados INT UNSIGNED NOT NULL DEFAULT 1
    AFTER ativo;

CREATE TABLE IF NOT EXISTS homologacoes_publicadas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trilha_id INT UNSIGNED NOT NULL,
    publicado_por INT UNSIGNED NULL,
    publicado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_homologacoes_publicadas_trilha (trilha_id),
    CONSTRAINT fk_homologacoes_publicadas_trilha FOREIGN KEY (trilha_id) REFERENCES trilhas (id),
    CONSTRAINT fk_homologacoes_publicadas_usuario FOREIGN KEY (publicado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
