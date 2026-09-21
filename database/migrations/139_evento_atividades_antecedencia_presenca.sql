-- Fase 47 (correcao pos-teste de fumaca): achado real do teste manual -
-- confirmar presenca era aceito a qualquer momento ate data_fim, incluindo
-- MESES antes de data_inicio (cenario real: atividade agendada pra
-- 04/11/2026 confirmada em 17/09/2026). antecedencia_abertura_presenca
-- define a partir de quantos minutos ANTES do inicio a leitura passa a ser
-- aceita - campo independente de tolerancia_presenca_efetiva (que define
-- ATE QUANDO DEPOIS do inicio ainda conta como presenca efetiva). Sem opcao
-- "sem restricao" - sempre ha uma janela minima, decisao explicita do
-- usuario apos ver o cenario real quebrado.
ALTER TABLE evento_atividades
    ADD COLUMN antecedencia_abertura_presenca TINYINT UNSIGNED NOT NULL DEFAULT 60
        AFTER tolerancia_presenca_efetiva;
