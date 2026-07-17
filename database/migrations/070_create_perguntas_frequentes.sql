-- Fase 18 (3.10/4.4): banco global e acumulativo de perguntas frequentes,
-- reaproveitavel entre edicoes via faq_concurso (migration 071).
CREATE TABLE IF NOT EXISTS perguntas_frequentes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pergunta VARCHAR(255) NOT NULL,
    resposta TEXT NOT NULL,
    categoria VARCHAR(80) NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
