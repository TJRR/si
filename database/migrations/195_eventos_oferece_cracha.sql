-- Fase 58: cracha impresso opcional por evento (dinamica de pontos v2: "para
-- este evento ela nao sera utilizada"). Padrao 1 para nada mudar nos
-- eventos existentes; desligado, some o botao "Imprimir cracha" e a rota do
-- cracha volta para a inscricao. O codigo do participante continua na tela
-- "Minha inscricao" e na tela de conexoes.
ALTER TABLE eventos
    ADD COLUMN oferece_cracha TINYINT(1) NOT NULL DEFAULT 1 AFTER modo_credenciamento;
