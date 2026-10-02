-- Fase 58: pontos por presenca em atividade, uma linha por presenca que
-- pontuou. Tabela propria, e nao coluna em evento_checkins: aquela tabela
-- guarda o fato (e alimenta exportacao EJURR e certificado); esta guarda o
-- credito, com a anulacao das Fases 56 e 57.
--
-- Os pontos, a REGRA e o FATO ficam congelados (licao do
-- exigencia_atingida da Fase 57):
--   pontos_presenca / pontos_pontualidade: o que valia no instante;
--   antecedencia_exigida: quantos minutos antes do inicio davam o extra;
--   minutos_antes_do_inicio: quando a leitura aconteceu em relacao ao
--   inicio (negativo quando foi depois).
--
-- UNIQUE (atividade_id, evento_inscricao_id), espelho da chave de
-- evento_checkins: um credito por presenca. E' ela que torna a gravacao
-- idempotente (a perdedora de duas leituras simultaneas nao faz nada).
--
-- anulado_por NULO = anulado pelo sistema (presenca removida), que volta
-- sozinho quando a presenca volta; preenchido = anulado por pessoa, que
-- nunca volta sozinho. Mesma regra de evento_bonus_creditos (184).
--
-- So' existe linha quando o total e' maior que zero: atividade sem pontos
-- nao gera linha, e passa a gerar na reconferencia se ganhar pontos depois.
CREATE TABLE IF NOT EXISTS evento_presenca_creditos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    checkin_id INT UNSIGNED NOT NULL,
    atividade_id INT UNSIGNED NOT NULL,
    evento_inscricao_id INT UNSIGNED NOT NULL,
    pontos_presenca SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    pontos_pontualidade SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    minutos_antes_do_inicio INT NOT NULL DEFAULT 0,
    antecedencia_exigida SMALLINT UNSIGNED NULL,
    creditado_em DATETIME NOT NULL,
    anulado_em DATETIME NULL,
    anulado_por INT UNSIGNED NULL,
    motivo_anulacao VARCHAR(500) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_presenca_creditos (atividade_id, evento_inscricao_id),
    INDEX idx_evento_presenca_creditos_evento_inscricao (evento_id, evento_inscricao_id),
    CONSTRAINT fk_evento_presenca_creditos_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_presenca_creditos_checkin FOREIGN KEY (checkin_id) REFERENCES evento_checkins (id),
    CONSTRAINT fk_evento_presenca_creditos_atividade FOREIGN KEY (atividade_id) REFERENCES evento_atividades (id),
    CONSTRAINT fk_evento_presenca_creditos_inscricao FOREIGN KEY (evento_inscricao_id) REFERENCES evento_inscricoes (id),
    CONSTRAINT fk_evento_presenca_creditos_anulado_por FOREIGN KEY (anulado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
