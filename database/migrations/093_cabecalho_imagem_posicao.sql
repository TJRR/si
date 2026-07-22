-- Fase 19 (#84 v5): posicao da imagem de fundo do cabecalho, escolhida
-- numa grade 3x3 (mesmo vocabulario de 9 posicoes ja usado em
-- banners.cta_posicao / BannerRepository::CTA_POSICOES). Padrao
-- 'superior_centro' = o "background-position: center top" que ja e' o
-- fallback fixo hoje - zero mudanca visual pra quem nao mexer.
ALTER TABLE configuracoes_visuais
    ADD COLUMN cabecalho_imagem_posicao ENUM(
        'superior_esquerda','superior_centro','superior_direita',
        'centro_esquerda','centro_centro','centro_direita',
        'inferior_esquerda','inferior_centro','inferior_direita'
    ) NOT NULL DEFAULT 'superior_centro';
