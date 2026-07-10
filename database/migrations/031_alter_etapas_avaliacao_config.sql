-- Configuracao do motor de avaliacao por etapa (Fase 6, item 1).
-- So fazem sentido em etapas que ja tem criterios cadastrados (ordem > 1).
ALTER TABLE etapas
    ADD COLUMN modo_designacao ENUM('manual', 'aberto', 'automatico') NULL,
    ADD COLUMN qtd_avaliadores_por_submissao INT UNSIGNED NOT NULL DEFAULT 1,
    ADD COLUMN modo_consolidacao ENUM('media_criterio', 'media_ne', 'unico') NOT NULL DEFAULT 'unico',
    ADD COLUMN modo_sigilo ENUM('cego', 'aberto') NOT NULL DEFAULT 'aberto',
    ADD COLUMN modo_avanco ENUM('automatico', 'manual') NOT NULL DEFAULT 'manual';
