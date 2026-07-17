-- Fase 18 (3.4/3.6/3.7): blocos de conteudo livre/extensiveis, escopados por
-- concurso. chave fixa ('sobre'/'premiacao') identifica os 2 blocos padrao
-- pre-criados a cada nova edicao; chave NULL = bloco livre criado pelo admin
-- (ex.: "Mentorias", "Parceiros"). secao_ancora alimenta o menu dinamico do
-- cabecalho (3.1) e o scrollspy.
CREATE TABLE IF NOT EXISTS blocos_conteudo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NOT NULL,
    chave VARCHAR(40) NULL,
    titulo VARCHAR(150) NOT NULL,
    conteudo_html TEXT NULL,
    imagem_path VARCHAR(255) NULL,
    imagem_alt VARCHAR(255) NULL,
    cta_titulo VARCHAR(150) NULL,
    cta_link VARCHAR(255) NULL,
    secao_ancora VARCHAR(60) NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_blocos_conteudo_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    UNIQUE KEY uq_blocos_conteudo_concurso_chave (concurso_id, chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
