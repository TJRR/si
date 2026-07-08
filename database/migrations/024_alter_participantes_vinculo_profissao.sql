-- Descoberto na importacao real (Fase 3): alguns participantes preencheram
-- biografia completa (500+ caracteres) no campo Local de Trabalho/Profissao
-- do formulario do Google. VARCHAR(150) truncava/rejeitava dado legitimo.
ALTER TABLE participantes
    MODIFY COLUMN vinculo_profissao TEXT NULL;
