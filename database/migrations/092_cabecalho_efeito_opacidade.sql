-- Fase 19 (#84 v4): efeito de transicao na base do cabecalho (onda ja
-- existia fixa; agora o admin escolhe entre onda/diagonal esquerda/
-- diagonal direita) + opacidade do tint de cor primaria sobre a foto de
-- fundo, ambos configuraveis pela aba "Cabecalho".
ALTER TABLE configuracoes_visuais
    ADD COLUMN cabecalho_efeito_transicao ENUM('onda','diagonal_esquerda','diagonal_direita') NOT NULL DEFAULT 'onda',
    ADD COLUMN cabecalho_overlay_opacidade TINYINT UNSIGNED NOT NULL DEFAULT 50;
