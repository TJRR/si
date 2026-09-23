-- Fase 51: componente "Programacao completa" da pagina publica do Evento
-- (abas por dia, cada dia dividido em turnos). Mesmo padrao de tabela-mae
-- mais itens descrito na migration 160.
--
-- fonte = 'atividades': a secao le evento_atividades do evento e agrupa
-- sozinha por dia e por turno a partir de data_inicio, usando o tipo
-- cadastrado (migration 159) como etiqueta. fonte = 'itens': a secao mostra
-- os itens digitados abaixo, para programacao que ainda nao esta cadastrada
-- como atividade (ex.: sessao solene sem inscricao nem presenca).
CREATE TABLE IF NOT EXISTS evento_secao_programacao (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    etiqueta VARCHAR(60) NULL,
    titulo VARCHAR(150) NULL,
    descricao_html TEXT NULL,
    fonte ENUM('atividades','itens') NOT NULL DEFAULT 'atividades',
    mostrar_local TINYINT(1) NOT NULL DEFAULT 1,
    cor_fundo VARCHAR(7) NULL,
    cor_texto VARCHAR(7) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_secao_programacao_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_secao_programacao_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evento_secao_programacao_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    secao_id INT UNSIGNED NOT NULL,
    atividade_id INT UNSIGNED NULL,
    dia DATE NULL,
    turno ENUM('manha','tarde','noite') NOT NULL DEFAULT 'manha',
    horario_texto VARCHAR(40) NULL,
    tipo_texto VARCHAR(60) NULL,
    titulo VARCHAR(255) NULL,
    local VARCHAR(150) NULL,
    descricao VARCHAR(255) NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_evento_secao_programacao_itens_secao FOREIGN KEY (secao_id) REFERENCES evento_secao_programacao (id) ON DELETE CASCADE,
    CONSTRAINT fk_evento_secao_programacao_itens_atividade FOREIGN KEY (atividade_id) REFERENCES evento_atividades (id),
    INDEX idx_evento_secao_programacao_itens_secao_ordem (secao_id, dia, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
