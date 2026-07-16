-- Fase 17 (Bug 3): distingue designacao manual de designacao vinda de
-- sorteio aceito - hoje e' impossivel diferenciar (confirmarDistribuicao()
-- usa o mesmo criar() de atribuir() manual). Designacoes de sorteio nunca
-- podem ser removidas (preserva a lisura do processo).
ALTER TABLE avaliador_designacoes
    ADD COLUMN origem ENUM('manual', 'sorteio') NOT NULL DEFAULT 'manual';
