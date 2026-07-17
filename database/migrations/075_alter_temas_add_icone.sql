-- Fase 18 (3.8): tag/icone tematico opcional por Tema (ex.: sustentabilidade,
-- acessibilidade), usado na grade de Desafios da home publica. Guarda o
-- nome/slug de um icone de um set pre-definido no frontend, nao upload
-- livre de arquivo.
ALTER TABLE temas
    ADD COLUMN icone VARCHAR(50) NULL;
