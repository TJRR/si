-- Fase 17 (Melhoria 1): feedback qualitativo do avaliador, configuravel pelo
-- Admin por etapa - 3 modos possiveis, mesmo padrao de mecanismo_avaliacao
-- (migration 051). "nenhum" preserva o comportamento atual (concurso vigente).
ALTER TABLE etapas
    ADD COLUMN modo_feedback_avaliador ENUM('nenhum', 'submissao', 'criterio') NOT NULL DEFAULT 'nenhum';
