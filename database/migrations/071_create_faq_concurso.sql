-- Fase 18 (3.10): associacao N:N entre o banco global de perguntas e cada
-- edicao - marca quais perguntas estao ativas em qual concurso, sem duplicar
-- o texto ao reaproveitar de uma edicao anterior.
CREATE TABLE IF NOT EXISTS faq_concurso (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    faq_id INT UNSIGNED NOT NULL,
    concurso_id INT UNSIGNED NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_faq_concurso_faq FOREIGN KEY (faq_id) REFERENCES perguntas_frequentes (id),
    CONSTRAINT fk_faq_concurso_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    UNIQUE KEY uq_faq_concurso (faq_id, concurso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
