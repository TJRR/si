-- Fase 47 (Semana de Inovacao, Bloco C): primeiro consumidor real de
-- evento_atividades.codigo_atividade (gerado na Fase 46, sem uso ate agora).
-- evento_checkins grava so' presenca bruta (horario exato) - nenhuma
-- classificacao de pontualidade e' persistida: "presenca efetiva" e' sempre
-- calculada em tempo de leitura a partir de data_inicio/tolerancia da
-- atividade, nunca gravada, para nao ficar desatualizada se o Admin mudar a
-- tolerancia depois do check-in ja' ter acontecido. FK de evento_checkins
-- aponta para evento_inscricoes (Evento), nao para evento_atividade_inscricoes
-- (Atividade) - presenca ja' ocorrida e' fato historico e nao desaparece se a
-- pessoa cancelar a inscricao na atividade depois.
ALTER TABLE evento_atividades
    ADD COLUMN tolerancia_presenca_efetiva TINYINT UNSIGNED NOT NULL DEFAULT 100
        AFTER permite_lista_espera;

CREATE TABLE IF NOT EXISTS evento_checkins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    atividade_id INT UNSIGNED NOT NULL,
    evento_inscricao_id INT UNSIGNED NOT NULL,
    checkin_em DATETIME NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_checkins_atividade FOREIGN KEY (atividade_id) REFERENCES evento_atividades (id),
    CONSTRAINT fk_evento_checkins_inscricao FOREIGN KEY (evento_inscricao_id) REFERENCES evento_inscricoes (id),
    UNIQUE KEY uq_evento_checkins (atividade_id, evento_inscricao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Diverge do precedente que a inspirou (evento_leituras_codigo_falhas, Fase
-- 43) em dois pontos, verificados por leitura de codigo e nao replicados por
-- precedente cego (ver memoria do projeto,
-- feedback_verificar_motivo_do_padrao_copiado): FK em usuario_id (o
-- precedente nao tem, sem justificativa documentada) e atividade_id nullable
-- com FK propria (permite chavear o limite de tentativas por atividade
-- quando o codigo lido bate numa atividade real, caindo num "balde" comum
-- quando nao bate em nenhuma - caso mais comum de erro de leitura/digitacao).
CREATE TABLE IF NOT EXISTS evento_atividade_leituras_falhas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    atividade_id INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_atividade_leituras_falhas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_evento_atividade_leituras_falhas_atividade FOREIGN KEY (atividade_id) REFERENCES evento_atividades (id),
    INDEX idx_atividade_leituras_falhas_usuario_atividade_criado (usuario_id, atividade_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
