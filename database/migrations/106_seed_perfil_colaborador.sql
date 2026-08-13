-- Fase 29 (Tira-Duvidas, ajuste pos-push): perfil novo "colaborador" -
-- pessoa externa ao NPI que so' pode responder/escalar duvidas atribuidas
-- a ela (quando um administrador escala pra ela). Nao e' "suporte": nao
-- tem acesso a Mentorias, Oficinas, Homologacao, Concursos, Designacoes,
-- Apuracao nem ao Painel administrativo - so' as telas de Duvida (ver
-- App\Middleware\RoleMiddleware::exigir() em DuvidaAdminController, o
-- unico controller que passa a aceitar este perfil).
INSERT IGNORE INTO perfis (chave, nome_exibicao, descricao) VALUES
    ('colaborador', 'Colaborador', 'Pessoa externa ao NPI - so acessa duvidas escaladas para ela, sem nenhum outro acesso administrativo');
