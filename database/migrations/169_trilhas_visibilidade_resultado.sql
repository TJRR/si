-- Fase 51: divulgacao publica do resultado final da trilha.
--
-- Ate aqui so existia resultado publico por etapa (etapas.visibilidade_publica,
-- migration 096). O resultado final da trilha (Nota Final e colocacao) ficava
-- so' no painel administrativo, e o campo "Destaque publico" (resumo e imagem
-- do case vencedor, cadastrado desde a Fase 18 em resultados_trilha) nunca
-- aparecia em tela publica nenhuma: a funcionalidade existia pela metade, so'
-- o lado do cadastro.
--
-- Mesmos tres estados ja usados por etapa, adaptados ao que faz sentido numa
-- classificacao final: oculto (padrao, nada muda para quem ja usa),
-- apenas_destaques (so' as colocacoes que tem destaque cadastrado) e
-- ranking_completo (todas as equipes, com Nota Final e colocacao).
ALTER TABLE trilhas
    ADD COLUMN visibilidade_publica_resultado ENUM('oculto','apenas_destaques','ranking_completo') NOT NULL DEFAULT 'oculto';
