-- Declara explicitamente como cada etapa e avaliada, no lugar da inferencia
-- implicita que existia espalhada pelo codigo (ordem=1 = Cadastro de Equipe,
-- "tem criterio cadastrado" = avaliada por avaliadores).
ALTER TABLE etapas
    ADD COLUMN mecanismo_avaliacao ENUM('nenhuma', 'administrador', 'avaliadores') NOT NULL DEFAULT 'nenhuma';

-- Backfill preserva o comportamento atual das etapas ja cadastradas.
UPDATE etapas SET mecanismo_avaliacao = 'administrador' WHERE ordem = 1;

UPDATE etapas e
SET mecanismo_avaliacao = 'avaliadores'
WHERE ordem > 1
  AND EXISTS (SELECT 1 FROM criterios_avaliacao c WHERE c.etapa_id = e.id);
