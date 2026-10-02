-- Fase 58: nome da rede digitado pela propria pessoa no envio de "qualquer
-- rede" (decisao do dono: a rede nao importa, basta a captura de tela).
-- Texto livre de ate' 60 caracteres, opcional, so' exibido escapado na
-- auditoria; nunca vira endereco.
--
-- excluido_em / excluido_por: exclusao em lote pela tela Comprovacoes. A
-- linha sai da lista, dos numeros e de toda soma de pontos, mas NAO e'
-- apagada, por decisao do dono: continua registrada em log_auditoria com o
-- conteudo anterior, e o resumo criptografico da prova continua na tabela,
-- onde as chaves unicas uq_evento_divulgacao_endereco e
-- uq_evento_divulgacao_arquivo_pessoa seguem barrando o reenvio da mesma
-- captura de tela. "Restaurar" devolve a linha.
ALTER TABLE evento_divulgacao_comprovacoes
    ADD COLUMN rede_informada VARCHAR(60) NULL AFTER rede,
    ADD COLUMN excluido_em DATETIME NULL AFTER motivo_anulacao,
    ADD COLUMN excluido_por INT UNSIGNED NULL AFTER excluido_em,
    ADD CONSTRAINT fk_evento_divulgacao_comprovacoes_excluido_por FOREIGN KEY (excluido_por) REFERENCES usuarios (id);
