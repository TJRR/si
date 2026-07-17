-- Fase 18 (3.9): eventos avulsos do cronograma publico, cadastrados
-- manualmente pelo admin (ex.: cerimonia de premiacao, live de duvidas) que
-- nao sao uma Etapa formal do fluxo de inscricao/avaliacao. etapa_id e'
-- opcional (permite vincular o evento a uma Etapa real quando fizer
-- sentido). Status (concluido/andamento/futuro) e' calculado em runtime
-- pela data atual, igual ja e' feito hoje para Etapas - nao precisa coluna.
CREATE TABLE IF NOT EXISTS eventos_cronograma (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NOT NULL,
    etapa_id INT UNSIGNED NULL,
    titulo VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_eventos_cronograma_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    CONSTRAINT fk_eventos_cronograma_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
