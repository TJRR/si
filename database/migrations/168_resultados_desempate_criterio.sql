-- Fase 51: qual regra decidiu cada empate, no resultado do Concurso.
--
-- O calculo ja aplicava as regras de desempate em cascata corretamente, mas
-- descartava a informacao de qual delas decidiu assim que a comparacao
-- terminava: o Admin via a colocacao final sem saber por que uma equipe
-- empatada ficou acima da outra. A coluna guarda essa razao em TEXTO ja
-- pronto (ex.: "Criterio Originalidade, maior nota"), e nao o id da regra,
-- por dois motivos: resultado publicado e' congelado por principio no
-- projeto, e a regra pode ser renomeada ou removida depois sem reescrever o
-- passado.
--
-- Tabelas que ja existem em producao desde a Fase 6/7, por isso migration
-- nova e aditiva, nunca edicao na origem (premissa 8 vale so para o intervalo
-- ainda nao implantado).
ALTER TABLE resultados_trilha
    ADD COLUMN desempate_criterio VARCHAR(255) NULL AFTER colocacao;

ALTER TABLE resultados_etapa
    ADD COLUMN desempate_criterio VARCHAR(255) NULL AFTER classificado;
