-- Fase 51: componente "Perguntas frequentes" da pagina publica do Evento,
-- com o mesmo acordeao ja usado pelo Concurso. Mesmo padrao de tabela-mae
-- mais itens descrito na migration 160.
--
-- Cadastro proprio por evento, e nao reaproveitamento de perguntas_frequentes
-- mais faq_concurso: aquele par esta em producao servindo o 5o Premio de
-- Inovacao em avaliacao real, e a Fase 50 fixou que conteudo de Evento nao
-- toca tabela nem tela do Concurso. O acordeao (marcacao e folha de estilo) e'
-- que e' compartilhado, nao os dados.
CREATE TABLE IF NOT EXISTS evento_secao_faq (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    etiqueta VARCHAR(60) NULL,
    titulo VARCHAR(150) NULL,
    descricao_html TEXT NULL,
    cor_fundo VARCHAR(7) NULL,
    cor_texto VARCHAR(7) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_secao_faq_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_secao_faq_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evento_secao_faq_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    secao_id INT UNSIGNED NOT NULL,
    pergunta VARCHAR(255) NOT NULL,
    resposta_html TEXT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_evento_secao_faq_itens_secao FOREIGN KEY (secao_id) REFERENCES evento_secao_faq (id) ON DELETE CASCADE,
    INDEX idx_evento_secao_faq_itens_secao_ordem (secao_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
