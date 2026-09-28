-- Fase 52: publicacao do resultado de Trabalhos (situacao, nota final,
-- posicao e media por criterio visiveis ao autor dentro do aplicativo).
--
-- Ate a Fase 51 o resultado nao tinha momento de publicacao: aplicar o
-- resultado gravava aprovado ou reprovado direto em trabalhos.status, e o
-- autor passava a ver isso no mesmo instante, com a nota sempre calculada
-- na hora. Agora o Admin publica (e reabre) o resultado de um evento, e o
-- que o autor ve e' o que ficou gravado na publicacao.
--
-- evento_trabalhos_config guarda o momento e o autor da publicacao
-- (resultado_publicado_em nulo = nao publicado), tres marcadores que
-- ligam ou desligam cada bloco exibido ao autor (nota final, posicao e
-- media por criterio; padrao ligado) e o texto editavel do aviso enviado
-- aos autores ao publicar. A chave estrangeira do autor da publicacao usa
-- ON DELETE SET NULL para nao travar a exclusao de um usuario
-- (database/excluir_usuario.php).
--
-- trabalhos guarda a nota final e a posicao congeladas na publicacao e um
-- detalhe em JSON (nota maxima total, total de trabalhos classificados e a
-- media de cada criterio com o NOME do criterio no dia da publicacao, no
-- mesmo espirito de desempate_criterio: renomear ou remover um criterio
-- depois nao reescreve o que o autor ja viu).
--
-- Tabelas que ja existem em producao desde a Fase 51, por isso migration
-- nova e aditiva, nunca edicao na origem (premissa 8 vale so para o
-- intervalo ainda nao implantado).
ALTER TABLE evento_trabalhos_config
    ADD COLUMN resultado_exibe_nota TINYINT(1) NOT NULL DEFAULT 1 AFTER mensagem_recebimento_html,
    ADD COLUMN resultado_exibe_posicao TINYINT(1) NOT NULL DEFAULT 1 AFTER resultado_exibe_nota,
    ADD COLUMN resultado_exibe_criterios TINYINT(1) NOT NULL DEFAULT 1 AFTER resultado_exibe_posicao,
    ADD COLUMN mensagem_resultado_html MEDIUMTEXT NULL AFTER resultado_exibe_criterios,
    ADD COLUMN resultado_publicado_em DATETIME NULL AFTER mensagem_resultado_html,
    ADD COLUMN resultado_publicado_por INT UNSIGNED NULL AFTER resultado_publicado_em,
    ADD CONSTRAINT fk_trabalhos_config_resultado_publicado_por FOREIGN KEY (resultado_publicado_por) REFERENCES usuarios (id) ON DELETE SET NULL;

ALTER TABLE trabalhos
    ADD COLUMN nota_final DECIMAL(5,2) NULL AFTER desempate_criterio,
    ADD COLUMN posicao SMALLINT UNSIGNED NULL AFTER nota_final,
    ADD COLUMN resultado_detalhe_json TEXT NULL AFTER posicao;
