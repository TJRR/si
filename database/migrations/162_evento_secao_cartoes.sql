-- Fase 51: componente "Cartoes" da pagina publica do Evento (ex.: os eixos
-- tematicos na secao "Sobre"): cartoes coloridos com resumo curto, que abrem
-- o texto completo ao clique. Mesmo padrao de tabela-mae mais itens descrito
-- na migration 160.
--
-- Cada item aponta para um eixo tematico ja cadastrado (eixo_tematico_id, e o
-- texto completo vem de trabalho_eixos_tematicos.descricao, fonte unica, sem
-- copiar o texto oficial do edital para um segundo lugar) OU carrega titulo e
-- texto proprios, para cartao que nao seja eixo. Cor e resumo curto ficam
-- sempre no item: sao decisao de pagina, nao dado de eixo.
CREATE TABLE IF NOT EXISTS evento_secao_cartoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    etiqueta VARCHAR(60) NULL,
    titulo VARCHAR(150) NULL,
    descricao_html TEXT NULL,
    colunas TINYINT UNSIGNED NOT NULL DEFAULT 4,
    efeito_hover ENUM('nenhum','elevar','escala','borda') NOT NULL DEFAULT 'elevar',
    efeito_abrir ENUM('nenhum','deslizar','desvanecer') NOT NULL DEFAULT 'deslizar',
    efeito_fechar ENUM('nenhum','deslizar','desvanecer') NOT NULL DEFAULT 'deslizar',
    cor_fundo VARCHAR(7) NULL,
    cor_texto VARCHAR(7) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_secao_cartoes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_secao_cartoes_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evento_secao_cartoes_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    secao_id INT UNSIGNED NOT NULL,
    eixo_tematico_id INT UNSIGNED NULL,
    etiqueta VARCHAR(40) NULL,
    titulo VARCHAR(150) NULL,
    resumo VARCHAR(255) NULL,
    detalhe_html TEXT NULL,
    cor VARCHAR(7) NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_evento_secao_cartoes_itens_secao FOREIGN KEY (secao_id) REFERENCES evento_secao_cartoes (id) ON DELETE CASCADE,
    CONSTRAINT fk_evento_secao_cartoes_itens_eixo FOREIGN KEY (eixo_tematico_id) REFERENCES trabalho_eixos_tematicos (id),
    INDEX idx_evento_secao_cartoes_itens_secao_ordem (secao_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
