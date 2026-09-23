-- Fase 51: componente "Contagem regressiva" da pagina publica do Evento.
--
-- Padrao comum a todos os componentes desta fase: uma tabela-mae por
-- instancia (varias instancias do mesmo tipo podem conviver na mesma pagina,
-- decisao do dono do projeto) e, quando o componente tem lista, uma tabela de
-- itens com ON DELETE CASCADE. A posicao na pagina, o liga/desliga e a
-- entrada no menu do cabecalho NAO ficam aqui: ficam em evento_secoes_ordem
-- (migration 158), uma linha por instancia, para que a ordem seja unica entre
-- todos os tipos de secao.
--
-- data_alvo nula significa "usar a data de inicio do evento" (eventos.data_inicio
-- as 00h00), para o componente funcionar sem configuracao extra.
CREATE TABLE IF NOT EXISTS evento_secao_contagem (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    etiqueta VARCHAR(60) NULL,
    titulo VARCHAR(150) NULL,
    descricao_html TEXT NULL,
    data_alvo DATETIME NULL,
    cor_fundo VARCHAR(7) NULL,
    cor_texto VARCHAR(7) NULL,
    cor_circulo VARCHAR(7) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_secao_contagem_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_secao_contagem_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evento_secao_contagem_itens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    secao_id INT UNSIGNED NOT NULL,
    texto VARCHAR(150) NOT NULL,
    data_referencia DATE NULL,
    cor_marcador VARCHAR(7) NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    CONSTRAINT fk_evento_secao_contagem_itens_secao FOREIGN KEY (secao_id) REFERENCES evento_secao_contagem (id) ON DELETE CASCADE,
    INDEX idx_evento_secao_contagem_itens_secao_ordem (secao_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
