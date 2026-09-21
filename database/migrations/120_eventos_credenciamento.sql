-- Fase 39 revisada (redesenho de arquitetura, 11/09/2026): Evento passa a
-- ter modo de credenciamento configuravel em "Dados Gerais" - automatico
-- (todo inscrito ja credenciado) ou assistido (Admin homologa cada
-- inscricao em "Inscritos", fluxo simples: so' data de homologacao, sem o
-- aparato completo de rejeicao/convite/historico que equipes de concurso
-- tem, porque aqui o participante ja tem conta propria desde a inscricao).
ALTER TABLE eventos
    ADD COLUMN modo_credenciamento ENUM('automatico', 'assistido') NOT NULL DEFAULT 'automatico' AFTER status;

ALTER TABLE evento_inscricoes
    ADD COLUMN homologado_em DATETIME NULL AFTER inscrito_em;
