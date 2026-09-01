-- Fase 38 (#247): bloco de conteudo rico opcional por concurso, exibido na
-- pagina publica da edicao encerrada, depois da galeria de fotos. 1 bloco
-- por concurso (upsert), mesmo padrao estrutural de contatos_concurso.
CREATE TABLE IF NOT EXISTS blocos_concurso (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(150) NULL,
    conteudo_html MEDIUMTEXT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_blocos_concurso_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    UNIQUE KEY uq_blocos_concurso_concurso (concurso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
