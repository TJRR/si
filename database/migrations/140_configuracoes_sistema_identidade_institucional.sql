-- Motor genérico configurável: "TJRR" e "NPI" estavam escritos diretamente
-- no código em dezenas de lugares (telas, e-mails, títulos de página) em vez
-- de virem de uma configuração editável pelo Admin. Os valores padrão abaixo
-- preservam exatamente o texto que já aparecia nas telas antes desta
-- migration, então nada muda visualmente até o Admin editar os campos.
ALTER TABLE configuracoes_sistema
    ADD COLUMN instituicao VARCHAR(150) NOT NULL DEFAULT 'TJRR',
    ADD COLUMN unidade_responsavel VARCHAR(150) NOT NULL DEFAULT 'NPI';
