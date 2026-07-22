-- Fase 19 (#84 v2): Tema, Slideshow, Banners, Blocos de Conteudo e Contato
-- eram escopados por concurso (Fase 18), mas na pratica sao configuracao
-- do SITE, nao da edicao - usuario confirmou apos testar que isso foi um
-- erro de arquitetura. So existe 1 concurso hoje e nenhuma linha real
-- diverge entre concursos nessas tabelas (conferido via SHOW CREATE TABLE +
-- contagem real antes de escrever esta migration), entao achatar pra
-- global e' seguro. Nomes de constraint conferidos direto no banco de dev,
-- nao presumidos a partir das migrations originais.

ALTER TABLE configuracoes_visuais
    DROP FOREIGN KEY fk_configuracoes_visuais_concurso,
    DROP INDEX uq_configuracoes_visuais_concurso,
    DROP COLUMN concurso_id,
    ADD COLUMN rodape_logo_path VARCHAR(255) NULL,
    ADD COLUMN rodape_mostrar_trilhas TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN rodape_mostrar_cronograma TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN rodape_mostrar_desafios TINYINT(1) NOT NULL DEFAULT 1,
    ADD COLUMN rodape_mostrar_contato TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE slides
    DROP FOREIGN KEY fk_slides_concurso,
    DROP COLUMN concurso_id;

ALTER TABLE banners
    DROP FOREIGN KEY fk_banners_concurso,
    DROP COLUMN concurso_id;

ALTER TABLE blocos_conteudo
    DROP FOREIGN KEY fk_blocos_conteudo_concurso,
    DROP INDEX uq_blocos_conteudo_concurso_chave,
    DROP COLUMN concurso_id,
    ADD UNIQUE KEY uq_blocos_conteudo_chave (chave),
    ADD COLUMN mostrar_no_rodape TINYINT(1) NOT NULL DEFAULT 1;

ALTER TABLE contatos_concurso
    DROP FOREIGN KEY fk_contatos_concurso_concurso,
    DROP INDEX uq_contatos_concurso,
    DROP COLUMN concurso_id;

ALTER TABLE mensagens_contato
    DROP FOREIGN KEY fk_mensagens_contato_concurso,
    DROP COLUMN concurso_id;
