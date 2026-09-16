-- Fase 38B (correcao pos-teste de fumaca): faltava a coluna que guarda o
-- link da sala do Google Meet em si - a migration 134 tinha meet_link_origem
-- (de onde veio o link) mas nao o link em si, diferente de
-- mentoria_horarios/oficina_horarios (coluna link_meet). Sem isso, o link
-- nunca chegava a ser mostrado a' equipe nem ao Admin, mesmo com a
-- integracao funcionando.
ALTER TABLE apresentacoes_pitch
    ADD COLUMN link_meet VARCHAR(255) NULL AFTER modalidade;
