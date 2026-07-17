-- Fase 18 (3.7 Premiacao): lista estruturada de colocacoes/premios por
-- concurso (substitui o texto solto). Regras gerais em texto rico continuam
-- em blocos_conteudo (chave='premiacao').
CREATE TABLE IF NOT EXISTS premios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NOT NULL,
    posicao INT UNSIGNED NOT NULL,
    descricao TEXT NOT NULL,
    imagem_path VARCHAR(255) NULL,
    imagem_alt VARCHAR(255) NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_premios_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
