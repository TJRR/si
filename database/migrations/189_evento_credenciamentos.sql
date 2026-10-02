-- Fase 58: o fato do credenciamento no local, uma linha por inscricao. Os
-- pontos nao ficam aqui: quem credita e' um bonus do tipo
-- credenciamento_local (Fase 57, catalogo de Bonus), que le esta tabela.
--
-- UNIQUE (evento_inscricao_id): uma confirmacao por pessoa; a leitura
-- repetida responde "Credenciamento ja' confirmado".
CREATE TABLE IF NOT EXISTS evento_credenciamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    evento_inscricao_id INT UNSIGNED NOT NULL,
    credenciado_em DATETIME NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_credenciamentos_inscricao (evento_inscricao_id),
    INDEX idx_evento_credenciamentos_evento (evento_id),
    CONSTRAINT fk_evento_credenciamentos_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_credenciamentos_inscricao FOREIGN KEY (evento_inscricao_id) REFERENCES evento_inscricoes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
