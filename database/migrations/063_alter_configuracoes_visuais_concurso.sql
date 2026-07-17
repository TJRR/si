-- Fase 18: identidade visual passa a poder ser sobrescrita por concurso.
-- concurso_id NULL = configuracao global/padrao (a linha id=1 existente
-- continua servindo de fallback); uma linha por concurso e' o override
-- daquela edicao especifica. id precisa virar AUTO_INCREMENT para permitir
-- mais de uma linha (antes era fixo em 1).
ALTER TABLE configuracoes_visuais
    MODIFY COLUMN id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    ADD COLUMN concurso_id INT UNSIGNED NULL,
    ADD COLUMN logo_path VARCHAR(255) NULL,
    ADD CONSTRAINT fk_configuracoes_visuais_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    ADD UNIQUE KEY uq_configuracoes_visuais_concurso (concurso_id);
