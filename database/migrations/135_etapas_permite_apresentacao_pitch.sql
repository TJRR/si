-- Fase 38B (correcao pos-teste de fumaca): a sub-aba "Apresentação" estava
-- aparecendo em toda Etapa do sistema, nao so' na Etapa 3 do concurso
-- vigente - precisa ser uma escolha explicita do Admin em Dados Gerais,
-- mesmo padrao de outras sub-abas condicionais (ex.: mecanismo_avaliacao
-- controlando as abas "somente avaliadores").
ALTER TABLE etapas
    ADD COLUMN permite_apresentacao_pitch TINYINT(1) NOT NULL DEFAULT 0 AFTER visibilidade_publica;
