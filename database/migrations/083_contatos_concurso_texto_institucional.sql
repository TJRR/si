-- Fase 19 (#107): texto institucional livre exibido na coluna 2 do rodape
-- redesenhado (padrao do site modelo indicado pelo usuario).
ALTER TABLE contatos_concurso
    ADD COLUMN texto_institucional TEXT NULL AFTER endereco;
