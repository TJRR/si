-- Fase 17 (Melhoria 1): feedback do avaliador por criterio individual (modo
-- "criterio" de etapas.modo_feedback_avaliador) - a chave unica existente
-- (submissao_id, criterio_avaliacao_id, usuario_id) ja da "1 feedback por
-- criterio por avaliador" de graca, sem precisar de tabela nova.
ALTER TABLE notas_lancadas
    ADD COLUMN feedback TEXT NULL;
