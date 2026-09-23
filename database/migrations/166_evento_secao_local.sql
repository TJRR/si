-- Fase 51: componente "Local e acesso" da pagina publica do Evento: endereco,
-- texto de apoio e mapa. Componente sem lista, entao nao tem tabela de itens;
-- a posicao na pagina continua em evento_secoes_ordem (migration 158).
--
-- mapa_embed_url e' o endereco de incorporacao do mapa, aceito apenas quando
-- aponta para google.com/maps ou openstreetmap.org (validacao no servidor,
-- antes de gravar, nunca so na tela). Decisao consciente do dono do projeto:
-- a pagina passa a carregar conteudo de terceiros nessa secao, diferente do
-- restante do sistema, que nao depende de nada externo. Sem endereco de mapa,
-- a secao mostra so' a imagem enviada (imagem_path), sem chamada externa.
CREATE TABLE IF NOT EXISTS evento_secao_local (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    etiqueta VARCHAR(60) NULL,
    titulo VARCHAR(150) NULL,
    endereco VARCHAR(255) NULL,
    descricao_html TEXT NULL,
    mapa_embed_url VARCHAR(500) NULL,
    mapa_link VARCHAR(500) NULL,
    imagem_path VARCHAR(255) NULL,
    imagem_alt VARCHAR(255) NULL,
    cor_fundo VARCHAR(7) NULL,
    cor_texto VARCHAR(7) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_secao_local_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_secao_local_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
