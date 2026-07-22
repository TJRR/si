-- Fase 19 (#91): efeito de camada sobre a imagem de fundo do slide -
-- porta fiel dos 6 efeitos ja usados no projeto LG Conecta (escurecer
-- gradiente, vinheta, pontos, linhas diagonais, pontos vazados/halftone,
-- trama scrim), com opacidade e cor configuraveis.
ALTER TABLE slides
    ADD COLUMN overlay_efeito ENUM('nenhum','escurecer','vinheta','pontos','linhas','halftone','trama') NOT NULL DEFAULT 'nenhum' AFTER efeito_transicao,
    ADD COLUMN overlay_cor CHAR(7) NULL AFTER overlay_efeito,
    ADD COLUMN overlay_opacidade TINYINT UNSIGNED NOT NULL DEFAULT 40 AFTER overlay_cor;
