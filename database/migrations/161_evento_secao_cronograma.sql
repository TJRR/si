-- Fase 51: componente "Cronograma" da pagina publica do Evento (linha do
-- tempo de marcos, ex.: o cronograma de submissao do edital). Mesmo padrao de
-- tabela-mae mais itens descrito na migration 160.
--
-- periodo_texto guarda o rotulo como ele deve aparecer ("19 a 26/10/2026",
-- "16/10/2026, ate 23h59"), porque marco de edital nem sempre e' uma data
-- unica; data_referencia e' opcional e serve para ordenar e para destacar o
-- marco vigente, sem obrigar o Admin a escolher uma data quando o marco e'
-- um intervalo.
--
-- Reabertura da Fase 51 (colunas acrescentadas na origem, premissa 8 de
-- Premissas.md): a secao passou a ter duas colunas, como na identidade
-- visual aprovada. A da esquerda e' o cabecalho comum (etiqueta, titulo,
-- texto) mais dois botoes e a linha de contato; a da direita e' o quadro
-- branco com a linha do tempo, cujo titulo e' titulo_quadro. O botao 1 pode
-- apontar para um Documento do Evento (evento_documentos, migration 171,
-- onde tambem nasce a chave estrangeira, porque aquela tabela so existe
-- depois desta) ou para um endereco digitado. Reabertura da Fase 51, segunda
-- rodada (achado da equipe de Teste Cego): terceiro botao, com a mesma
-- estrutura do primeiro, para o modelo do resumo expandido (arquivo do Word)
-- ficar ao lado do edital e do envio. mostrar_contato exibe e-mail e
-- WhatsApp do contato global do site, com icones.
CREATE TABLE IF NOT EXISTS evento_secao_cronograma (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    etiqueta VARCHAR(60) NULL,
    titulo VARCHAR(150) NULL,
    descricao_html TEXT NULL,
    titulo_quadro VARCHAR(150) NULL,
    botao1_titulo VARCHAR(150) NULL,
    botao1_documento_id INT UNSIGNED NULL,
    botao1_link VARCHAR(255) NULL,
    botao2_titulo VARCHAR(150) NULL,
    botao2_link VARCHAR(255) NULL,
    botao3_titulo VARCHAR(150) NULL,
    botao3_documento_id INT UNSIGNED NULL,
    botao3_link VARCHAR(255) NULL,
    mostrar_contato TINYINT(1) NOT NULL DEFAULT 0,
    cor_fundo VARCHAR(7) NULL,
    cor_texto VARCHAR(7) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_secao_cronograma_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_secao_cronograma_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evento_secao_cronograma_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    secao_id INT UNSIGNED NOT NULL,
    periodo_texto VARCHAR(80) NOT NULL,
    descricao VARCHAR(255) NOT NULL,
    data_referencia DATE NULL,
    cor VARCHAR(7) NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_evento_secao_cronograma_itens_secao FOREIGN KEY (secao_id) REFERENCES evento_secao_cronograma (id) ON DELETE CASCADE,
    INDEX idx_evento_secao_cronograma_itens_secao_ordem (secao_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
