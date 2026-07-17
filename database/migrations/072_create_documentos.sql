-- Fase 18 (4.6): documentos/editais por edicao, com historico de versoes
-- simples - grupo_documento agrupa as versoes de "o mesmo documento"
-- (ex.: "edital-trilha-interna"); cada novo upload gera uma linha nova com
-- versao incrementada, sem sobrescrever/apagar a anterior. ativo=1 marca a
-- versao atual (a que aparece em destaque na listagem publica); as demais
-- do mesmo grupo continuam listadas como historico.
CREATE TABLE IF NOT EXISTS documentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NOT NULL,
    trilha_id INT UNSIGNED NULL,
    tipo ENUM('edital', 'edital_simples', 'anexo', 'retificacao', 'resultado_final', 'ata') NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    arquivo_path VARCHAR(255) NOT NULL,
    grupo_documento VARCHAR(160) NOT NULL,
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_por INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_documentos_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    CONSTRAINT fk_documentos_trilha FOREIGN KEY (trilha_id) REFERENCES trilhas (id),
    CONSTRAINT fk_documentos_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios (id),
    KEY idx_documentos_grupo (grupo_documento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
