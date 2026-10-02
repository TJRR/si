-- Fase 61B: cada botao do cabecalho da pagina do evento ("Inscreva-se" e
-- "Entrar") pode ser ocultado pela aba Cabecalho do evento. Padrao 1 para
-- nada mudar nos eventos existentes.
ALTER TABLE evento_configuracao_visual
    ADD COLUMN mostrar_botao_inscricao TINYINT(1) NOT NULL DEFAULT 1 AFTER fonte_texto,
    ADD COLUMN mostrar_botao_entrar TINYINT(1) NOT NULL DEFAULT 1 AFTER mostrar_botao_inscricao;
