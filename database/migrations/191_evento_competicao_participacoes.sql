-- Fase 58: participacao em competicao, com os pontos congelados no valor da
-- competicao no instante da leitura.
--
-- UNIQUE (competicao_id, evento_inscricao_id): uma participacao pontuada
-- por pessoa por competicao. A gravacao trata a chave repetida (leituras
-- simultaneas nao quebram a resposta do leitor).
--
-- Anulacao so' por pessoa (o Administrador, com motivo), reversivel por
-- pessoa, no molde de evento_divulgacao_comprovacoes (183). A chave vale
-- tambem para linha anulada: anular nao reabre a vaga.
CREATE TABLE IF NOT EXISTS evento_competicao_participacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    competicao_id INT UNSIGNED NOT NULL,
    evento_inscricao_id INT UNSIGNED NOT NULL,
    pontos_creditados SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    participou_em DATETIME NOT NULL,
    anulado_em DATETIME NULL,
    anulado_por INT UNSIGNED NULL,
    motivo_anulacao VARCHAR(500) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_competicao_participacoes (competicao_id, evento_inscricao_id),
    INDEX idx_evento_competicao_participacoes_evento_inscricao (evento_id, evento_inscricao_id),
    CONSTRAINT fk_evento_competicao_participacoes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_competicao_participacoes_competicao FOREIGN KEY (competicao_id) REFERENCES evento_competicoes (id),
    CONSTRAINT fk_evento_competicao_participacoes_inscricao FOREIGN KEY (evento_inscricao_id) REFERENCES evento_inscricoes (id),
    CONSTRAINT fk_evento_competicao_participacoes_anulado_por FOREIGN KEY (anulado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
