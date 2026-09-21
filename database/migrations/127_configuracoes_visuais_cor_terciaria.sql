-- Fase 41: cor terciaria do tema - fundo da area de conteudo e do menu do
-- aplicativo web instalavel (PWA) do Evento, editavel pelo Administrador em
-- Configuracoes > Tema, junto com as demais cores. Default escuro o
-- suficiente para contrastar com texto branco (mesmo espirito de
-- cor_secundaria, migration 036).
ALTER TABLE configuracoes_visuais
    ADD COLUMN cor_terciaria VARCHAR(7) NOT NULL DEFAULT '#CC5200';
