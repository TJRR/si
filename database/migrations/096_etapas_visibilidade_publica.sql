-- Fase 23 (item C2): admin passa a escolher o que a pagina publica de
-- resultado da etapa (resultadosPublicos/etapa/{id}) mostra - antes era
-- fixo no codigo (so' nome + video, nunca nota/ranking), decisao pensada
-- so' para a etapa de video e que nao faz sentido pra uma etapa sem esse
-- campo. Default 'apenas_classificados' preserva o comportamento anterior
-- (sem nota/ranking) para etapas ja publicadas, ate o Admin decidir mudar.
ALTER TABLE etapas
    ADD COLUMN visibilidade_publica ENUM('oculto', 'apenas_classificados', 'ranking_completo', 'ranking_e_material') NOT NULL DEFAULT 'apenas_classificados' AFTER modo_avanco;
