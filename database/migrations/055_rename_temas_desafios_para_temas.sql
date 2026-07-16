-- Fase 17 (Bug 2): "temas_desafios" hoje e' uma tabela flat que mistura Tema
-- e Desafio - as 6 linhas existentes sao, na verdade, so os 3 Temas fixos do
-- edital por trilha (ja com nome/descricao reais, seed_temas_2026.sql). Vira
-- literalmente a tabela "temas"; o nivel "Desafio" (33 linhas) entra em
-- migration separada (056), como tabela filha.
RENAME TABLE temas_desafios TO temas;
