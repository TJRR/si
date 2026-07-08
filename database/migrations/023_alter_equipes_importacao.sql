ALTER TABLE equipes
    ADD COLUMN importado_em DATETIME NULL,
    ADD COLUMN vinculo_institucional VARCHAR(255) NULL,
    ADD COLUMN observacoes TEXT NULL;
