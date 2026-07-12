-- Novo modo de designacao (Fase 10): sorteio aleatorio garantindo 1
-- avaliador de cada categoria por submissao, sem grupos fixos (decisao da
-- reuniao de 10/07/2026 com a France, ata secao 7.3).
ALTER TABLE etapas
    MODIFY COLUMN modo_designacao ENUM('manual', 'aberto', 'automatico', 'sorteio_categoria') NULL;
