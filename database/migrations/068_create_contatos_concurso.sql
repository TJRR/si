-- Fase 18 (3.12/4.9): contato/canais por edicao (substitui os campos fixos
-- contato_* de conteudos_site). redes_sociais guarda pares rede->link.
CREATE TABLE IF NOT EXISTS contatos_concurso (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NOT NULL,
    email VARCHAR(150) NULL,
    telefone VARCHAR(30) NULL,
    whatsapp VARCHAR(30) NULL,
    endereco VARCHAR(255) NULL,
    redes_sociais JSON NULL,
    formulario_contato_ativo TINYINT(1) NOT NULL DEFAULT 0,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_contatos_concurso_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    UNIQUE KEY uq_contatos_concurso (concurso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
