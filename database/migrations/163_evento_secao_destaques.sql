-- Fase 51: componente "Destaques" da pagina publica do Evento (cartoes curtos
-- com as atividades que a organizacao quer evidenciar). Mesmo padrao de
-- tabela-mae mais itens descrito na migration 160.
--
-- fonte = 'atividades': a secao le evento_atividades marcadas com
-- destacar_na_pagina (migration 159), sem digitar nada duas vezes.
-- fonte = 'itens': a secao mostra os itens digitados abaixo, para o caso de
-- destaque que nao corresponde a uma atividade cadastrada. Um item pode
-- apontar para uma atividade (atividade_id) e ainda assim sobrescrever o
-- texto, quando a chamada da pagina precisar ser diferente do nome oficial.
--
-- Reabertura da Fase 51 (coluna acrescentada na origem, premissa 8 de
-- Premissas.md): icone_cor, a cor do quadrado atras do icone do item. O
-- icone em si sai de uma lista fechada (EventoSecaoDestaquesRepository::ICONES).
CREATE TABLE IF NOT EXISTS evento_secao_destaques (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    etiqueta VARCHAR(60) NULL,
    titulo VARCHAR(150) NULL,
    descricao_html TEXT NULL,
    fonte ENUM('atividades','itens') NOT NULL DEFAULT 'atividades',
    colunas TINYINT UNSIGNED NOT NULL DEFAULT 4,
    cor_fundo VARCHAR(7) NULL,
    cor_texto VARCHAR(7) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_secao_destaques_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_secao_destaques_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evento_secao_destaques_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    secao_id INT UNSIGNED NOT NULL,
    atividade_id INT UNSIGNED NULL,
    titulo VARCHAR(150) NULL,
    quando_texto VARCHAR(120) NULL,
    local VARCHAR(150) NULL,
    descricao VARCHAR(255) NULL,
    icone VARCHAR(40) NULL,
    icone_cor VARCHAR(7) NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_evento_secao_destaques_itens_secao FOREIGN KEY (secao_id) REFERENCES evento_secao_destaques (id) ON DELETE CASCADE,
    CONSTRAINT fk_evento_secao_destaques_itens_atividade FOREIGN KEY (atividade_id) REFERENCES evento_atividades (id),
    INDEX idx_evento_secao_destaques_itens_secao_ordem (secao_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
