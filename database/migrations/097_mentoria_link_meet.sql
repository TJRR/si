-- Fase 24: sala do Google Meet definida com antecedência para o horário de
-- mentoria (mesmo link usado tanto na tela do admin quanto na do
-- participante) - opcional porque horários criados antes desta fase não
-- têm o link ainda.
ALTER TABLE mentoria_horarios ADD COLUMN link_meet VARCHAR(255) NULL AFTER data_fim;
