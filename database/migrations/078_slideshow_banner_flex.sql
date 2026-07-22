-- Fase 19 (#91/#92/#93/#95/#96): slide sem imagem obrigatoria (cor de
-- fundo como fallback), efeito/duracao de transicao configuraveis por
-- slide, e alinhamento configuravel do bloco de conteudo do banner.
ALTER TABLE slides
    MODIFY COLUMN imagem_desktop_path VARCHAR(255) NULL,
    ADD COLUMN cor_fundo CHAR(7) NULL AFTER imagem_alt,
    ADD COLUMN duracao_ms INT UNSIGNED NOT NULL DEFAULT 7000 AFTER cor_fundo,
    ADD COLUMN efeito_transicao ENUM('fade','slide','zoom') NOT NULL DEFAULT 'fade' AFTER duracao_ms;

ALTER TABLE banners
    ADD COLUMN conteudo_alinhamento ENUM('esquerda','centro','direita') NOT NULL DEFAULT 'centro' AFTER conteudo_html;
