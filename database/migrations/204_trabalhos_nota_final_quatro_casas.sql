-- Fase 60 (pendencia 9): nota_final passa a guardar ate quatro casas, o
-- teto de evento_trabalhos_config.casas_decimais. Mesmos tres digitos
-- inteiros de antes, entao nenhum valor ja gravado e' cortado.
ALTER TABLE trabalhos
    MODIFY COLUMN nota_final DECIMAL(7,4) NULL;
