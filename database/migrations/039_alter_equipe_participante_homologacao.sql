-- Homologacao da inscricao, por participante (nao por equipe inteira), a
-- pedido do usuario: um integrante com dado incorreto nao bloqueia os demais.
ALTER TABLE equipe_participante
    ADD COLUMN status_homologacao ENUM('pendente', 'homologado', 'rejeitado') NOT NULL DEFAULT 'pendente',
    ADD COLUMN homologado_por INT UNSIGNED NULL,
    ADD COLUMN homologado_em DATETIME NULL,
    ADD COLUMN motivo_rejeicao TEXT NULL,
    ADD CONSTRAINT fk_equipe_participante_homologado_por FOREIGN KEY (homologado_por) REFERENCES usuarios (id);
