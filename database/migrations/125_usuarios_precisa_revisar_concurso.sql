-- Fase 40 (correcao pos-teste de fumaca, 14/09/2026): a checagem original do
-- selo de "aprovado automaticamente" no Admin (usuarios/index) disparava para
-- QUALQUER conta com perfil 'inscrito' - inclusive um cadastro novo comum,
-- feito so' para participar do evento, sem nenhuma pendencia real com o
-- Concurso. O aviso so' faz sentido no cenario raro: uma conta que JA
-- EXISTIA como pendente (criada por outro caminho, esperando revisao do
-- Admin para um perfil do Concurso) e foi auto-aprovada de passagem pelo
-- fluxo do evento. Coluna nova para marcar exatamente esse caso - contagem
-- de perfis (count(perfis) === 1) nao bastava para diferenciar os dois
-- cenarios. Zerada quando o Admin atribui/edita o perfil da conta
-- (UsuarioAdminController::aprovar()/salvarEdicao()).
ALTER TABLE usuarios
    ADD COLUMN precisa_revisar_concurso TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
