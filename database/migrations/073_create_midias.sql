-- Fase 18 (4.5 Biblioteca de midia): upload central de imagens/PDFs/videos,
-- reaproveitavel entre edicoes (concurso_id opcional so' filtra a origem).
-- alt_text obrigatorio no fluxo de upload quando tipo='imagem' (WCAG,
-- corrige lacuna dos dois sites atuais) - validado no Controller/Service,
-- nao pode ficar NULL nesse caso.
CREATE TABLE IF NOT EXISTS midias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NULL,
    arquivo_path VARCHAR(255) NOT NULL,
    tipo ENUM('imagem', 'pdf', 'video') NOT NULL,
    alt_text VARCHAR(255) NULL,
    titulo VARCHAR(150) NULL,
    descricao TEXT NULL,
    criado_por INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_midias_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    CONSTRAINT fk_midias_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
