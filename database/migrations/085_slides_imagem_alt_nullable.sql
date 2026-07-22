-- Fase 19 (#91/#92): a migration 078 tornou imagem_desktop_path opcional
-- (slide sem imagem, com cor_fundo como fallback), mas esqueceu de
-- relaxar imagem_alt junto -- SlideAdminController grava alt = null
-- quando nao ha upload de imagem, e o INSERT quebrava com
-- "Column 'imagem_alt' cannot be null" (imagem_alt so' faz sentido
-- quando existe imagem).
ALTER TABLE slides
    MODIFY COLUMN imagem_alt VARCHAR(255) NULL;
