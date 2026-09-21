-- Fase 48B (correcao pos-teste de fumaca): a logo do sistema deixa de ser
-- uma configuracao unica (configuracoes_visuais.logo_path, aba Cabecalho) e
-- passa a fazer parte de cada tema, com 2 logos: uma para o Concurso (site
-- publico + topbar do painel administrativo) e outra para o Evento (so' na
-- tela de login especifica do app de Evento, auth/loginEvento).

ALTER TABLE temas_visuais
    ADD COLUMN logo_concurso_path VARCHAR(255) NULL AFTER cor_destaque_app,
    ADD COLUMN logo_evento_path VARCHAR(255) NULL AFTER logo_concurso_path;

UPDATE temas_visuais SET logo_concurso_path = 'img/logo-c-laranja.png', logo_evento_path = 'img/logo-e-laranja.png' WHERE nome = 'Laranja';
UPDATE temas_visuais SET logo_concurso_path = 'img/logo-c-roxo.png', logo_evento_path = 'img/logo-e-roxo.png' WHERE nome = 'Roxo';
UPDATE temas_visuais SET logo_concurso_path = 'img/logo-c-verde.png', logo_evento_path = 'img/logo-e-verde.png' WHERE nome = 'Verde';
UPDATE temas_visuais SET logo_concurso_path = 'img/logo-c-vermelha.png', logo_evento_path = 'img/logo-e-vermelha.png' WHERE nome = 'Vermelho';
UPDATE temas_visuais SET logo_concurso_path = 'img/logo-c-azul.png', logo_evento_path = 'img/logo-e-azul.png' WHERE nome = 'Azul-petroleo';

ALTER TABLE configuracoes_visuais DROP COLUMN logo_path;
