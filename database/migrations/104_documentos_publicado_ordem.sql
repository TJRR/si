-- Fase 29 (Bug 3): despublicar documento sem apagar - excluir perde o
-- arquivo e o historico, o que pode prejudicar a lisura do certame (edital
-- publicado precisa continuar rastreavel mesmo se tirado da home).
-- Tambem adiciona ordem manual (drag-and-drop na tela de Documentos,
-- refletida na home) - ate aqui a ordem era so' fixa por tipo+titulo.
ALTER TABLE documentos
    ADD COLUMN publicado TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN ordem INT UNSIGNED NOT NULL DEFAULT 0;

-- Backfill: preserva a ordem visual atual (tipo, titulo) como ponto de
-- partida - senao todo documento existente ficaria empatado em ordem=0 e a
-- lista pareceria embaralhar sozinha no primeiro carregamento da tela nova.
SET @ordem_documentos := -1;

UPDATE documentos
SET ordem = (@ordem_documentos := @ordem_documentos + 1)
WHERE ativo = 1
ORDER BY concurso_id ASC, tipo ASC, titulo ASC;
