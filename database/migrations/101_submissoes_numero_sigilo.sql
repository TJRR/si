-- Fase 25 (#1): numero estavel de "Equipe N" sob sigilo cego. Antes disso o
-- numero era calculado do zero a cada requisicao (posicao em
-- listarPorEtapa(), ORDER BY s.id ASC) - instavel se qualquer submissao da
-- etapa fosse removida depois, e vazava a ordem cronologica real de envio.
-- Nullable porque etapas com modo_sigilo = 'aberto' nunca precisam disso.
-- UNIQUE composta garante que nunca duas submissoes da mesma etapa dividem
-- o mesmo numero.
ALTER TABLE submissoes
    ADD COLUMN numero_sigilo_etapa INT UNSIGNED NULL,
    ADD UNIQUE KEY uq_submissoes_numero_sigilo_etapa (etapa_id, numero_sigilo_etapa);
