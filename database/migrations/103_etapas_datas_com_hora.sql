-- Fase 27 (ajuste pedido pelo usuario): data_inicio/data_fim de etapas
-- passam a aceitar hora, nao so' dia - motivado por prazo_final_submissao
-- (migration 102) ter ficado "solto" entre dois campos so' de data.
--
-- IMPORTANTE: MySQL converte DATE -> DATETIME preenchendo 00:00:00, o que
-- reduziria a janela de toda etapa existente em ate' quase 24h (o codigo
-- ate' aqui tratava data_fim como "o dia inteiro conta", comparando so' a
-- parte de data). O UPDATE abaixo empurra data_fim pre-existente pra
-- 23:59:59 do mesmo dia, preservando exatamente a janela que valia antes -
-- so' data_inicio fica em 00:00:00 (que ja' era o comportamento efetivo).
ALTER TABLE etapas
    MODIFY COLUMN data_inicio DATETIME NULL,
    MODIFY COLUMN data_fim DATETIME NULL;

UPDATE etapas
SET data_fim = DATE_ADD(data_fim, INTERVAL '23:59:59' HOUR_SECOND)
WHERE data_fim IS NOT NULL AND TIME(data_fim) = '00:00:00';
