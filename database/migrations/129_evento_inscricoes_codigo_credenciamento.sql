-- Fase 42 (Semana de Inovacao, Bloco A): codigo de credenciamento pessoal,
-- um por inscricao (nao por conta - a mesma pessoa pode ter inscricoes, e
-- portanto codigos, em mais de um evento). Coluna fica NULLable de proposito
-- (mesmo padrao ja usado por evento_inscricoes.homologado_em e por
-- submissoes.numero_sigilo_etapa/atribuir_numeros_sigilo_etapa.php): o
-- preenchimento das linhas ja existentes e' tarefa de manutencao
-- (database/gerar_codigos_credenciamento_pendentes.php), nao de schema -
-- MySQL permite mais de um NULL numa UNIQUE KEY sem violar a constraint.
-- Todo registro NOVO (EventoInscricaoRepository::inscrever()) ja nasce com
-- o codigo preenchido a partir desta fase.
--
-- Correcao de arquitetura (Fase 50): a coluna ja nasce no formato final,
-- CHAR(6) (Crockford Base32, EventoInscricaoRepository::
-- ALFABETO_CODIGO_CREDENCIAMENTO, pensado pra digitacao manual de fallback
-- caso a leitura do QR falhe) - a primeira versao desta migration criava
-- CHAR(32) e uma migration seguinte encolhia para CHAR(6) pouco depois; como
-- nada disso chegou a producao, o formato longo nunca existiu de verdade em
-- nenhum ambiente novo.
ALTER TABLE evento_inscricoes
    ADD COLUMN codigo_credenciamento CHAR(6) NULL AFTER homologado_em,
    ADD UNIQUE KEY uq_evento_inscricoes_codigo (codigo_credenciamento);
