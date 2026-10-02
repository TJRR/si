-- Fase 60 (pendencia 9): casas decimais do resultado de Trabalhos, no molde
-- de formulas_pontuacao.casas_decimais (133) do Concurso. Vale para a media
-- por criterio, a nota final e a exibicao; a nota lancada pelo avaliador
-- continua com duas casas, como no Concurso.
ALTER TABLE evento_trabalhos_config
    ADD COLUMN casas_decimais TINYINT UNSIGNED NOT NULL DEFAULT 2 AFTER metodo_agregacao_nota;
