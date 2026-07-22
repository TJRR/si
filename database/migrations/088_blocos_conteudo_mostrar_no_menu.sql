-- Fase 19 (#86): atalho no menu superior passa a ser opt-in por bloco livre
-- de conteudo (ex.: "Mentorias Opcionais"), nao mais automatico so' por
-- estar ativo - os blocos padrao (Sobre/Premiacao) continuam com sua propria
-- logica de menu, independente desta coluna.
ALTER TABLE blocos_conteudo
    ADD COLUMN mostrar_no_menu TINYINT(1) NOT NULL DEFAULT 0 AFTER ativo;
