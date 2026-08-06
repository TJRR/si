-- Fase 27 (correcao de seguranca): prazo de edicao da submissao pelo
-- participante, desacoplado de data_fim - data_fim continua sendo a janela
-- de acesso do avaliador (AvaliacaoController) e do avaliador seguinte, que
-- geralmente precisa ficar aberta bem depois do prazo real de envio. NULL
-- (padrao em toda etapa existente) mantem o comportamento atual (fallback
-- pra data_fim em SubmissaoService::preparar()) - o Admin precisa preencher
-- manualmente em cada etapa ja em avaliacao pra fechar a falha de fato.
ALTER TABLE etapas
    ADD COLUMN prazo_final_submissao DATETIME NULL;
