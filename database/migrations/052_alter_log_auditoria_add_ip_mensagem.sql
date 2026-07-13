ALTER TABLE log_auditoria
    ADD COLUMN ip_origem VARCHAR(45) NULL AFTER entidade_id,
    ADD COLUMN mensagem TEXT NULL AFTER dados_depois;
