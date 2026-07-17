-- Fase 18 (3.12): formulario de contato nativo, opcional por edicao
-- (contatos_concurso.formulario_contato_ativo). Reduz dependencia de links
-- externos para duvidas gerais.
CREATE TABLE IF NOT EXISTS mensagens_contato (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    mensagem TEXT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mensagens_contato_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
