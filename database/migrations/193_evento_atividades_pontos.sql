-- Fase 58: excecao de pontos na propria atividade. NULO significa "herda do
-- tipo"; zero significa "esta atividade nao pontua", mesmo que o tipo
-- pontue.
ALTER TABLE evento_atividades
    ADD COLUMN pontos_presenca SMALLINT UNSIGNED NULL AFTER antecedencia_abertura_presenca,
    ADD COLUMN pontos_pontualidade SMALLINT UNSIGNED NULL AFTER pontos_presenca;
