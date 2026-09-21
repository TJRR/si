-- Fase 41 (correcao pos-teste de fumaca): cor de destaque do aplicativo -
-- fundo dos blocos internos ("cartoes") dentro do aplicativo web instalavel
-- do Evento (ex.: os blocos "Em breve" do painel), editavel pelo
-- Administrador em Configuracoes > Tema. Antes disso, o fundo desses blocos
-- era calculado via opacidade sobre a cor branca (style="opacity:0.6" fixo
-- na view) - funcionava mas nao dava controle nenhum ao Admin sobre o
-- resultado. Default claro o suficiente para contrastar com o texto escuro
-- que continua sendo usado dentro desses blocos.
ALTER TABLE configuracoes_visuais
    ADD COLUMN cor_destaque_app VARCHAR(7) NOT NULL DEFAULT '#FFD9B3';
