-- Fase 58: cascata de desempate da classificacao geral, no molde de
-- trabalho_regras_desempate (Fase 49). Os criterios possiveis sao fixos em
-- codigo (GamificacaoService::CRITERIOS_DESEMPATE), cada um com a sua
-- direcao; o Administrador escolhe quais usar e em que ordem.
--
-- UNIQUE (evento_id, criterio): o mesmo criterio duas vezes na cascata nao
-- desempataria nada a mais.
CREATE TABLE IF NOT EXISTS evento_gamificacao_desempate (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    criterio VARCHAR(40) NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_gamificacao_desempate (evento_id, criterio),
    INDEX idx_evento_gamificacao_desempate_ordem (evento_id, ordem),
    CONSTRAINT fk_evento_gamificacao_desempate_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
