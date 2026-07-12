-- Categorias de avaliador (Fase 10) sao livres e cadastradas pelo Admin por
-- concurso -- nao ha nada fixo no codigo (ex.: "professor"/"area"/"TI" sao so
-- dados desta edicao, nao um enum).
CREATE TABLE IF NOT EXISTS categorias_avaliador (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_categorias_avaliador_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
