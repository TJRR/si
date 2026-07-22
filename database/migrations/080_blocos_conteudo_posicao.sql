-- Fase 19 (#98/#99): posicao da imagem em relacao ao texto e alinhamento
-- do CTA, configuraveis por bloco de conteudo (hoje fixos: imagem sempre
-- a esquerda, CTA sempre alinhado ao fluxo padrao do texto).
ALTER TABLE blocos_conteudo
    ADD COLUMN imagem_posicao ENUM('esquerda','direita') NOT NULL DEFAULT 'esquerda' AFTER imagem_alt,
    ADD COLUMN cta_alinhamento ENUM('esquerda','centro','direita') NOT NULL DEFAULT 'esquerda' AFTER cta_link;
