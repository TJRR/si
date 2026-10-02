-- Fase 58: pontos de presenca por tipo de atividade (decisao do dono: o
-- tipo da' o padrao, a atividade pode ter valor proprio). Zero por padrao:
-- tipo sem valor nao pontua, e nada e' semeado.
--
-- pontos_pontualidade: o extra de quem confirma presenca ate' N minutos
-- antes do inicio (N em evento_gamificacao_config.minutos_pontualidade).
ALTER TABLE evento_atividade_tipos
    ADD COLUMN pontos_presenca SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER cor,
    ADD COLUMN pontos_pontualidade SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER pontos_presenca;
