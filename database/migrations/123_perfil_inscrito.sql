-- Fase 40 (14/09/2026): novo perfil "inscrito" - auto-aprovado, atribuido a
-- quem se cadastra exclusivamente para participar de um Evento (Semana de
-- Inovacao ou qualquer evento futuro). Diferente dos demais perfis (ver
-- migrations 019/041/106), nao exige aprovacao manual do Administrador -
-- a inscricao em evento pode chegar a centenas de pessoas em poucos dias,
-- inviavel para curadoria individual (ver AuthService::cadastrarInscrito()
-- e AuthService::resolverUsuarioGoogle()). Descricao mantida generica de
-- proposito (nao cita "Semana de Inovacao") para nao ficar desatualizada
-- quando um segundo evento com outro nome existir.
INSERT IGNORE INTO perfis (chave, nome_exibicao, descricao)
VALUES ('inscrito', 'Inscrito em evento',
        'Perfil auto-aprovado, atribuido a quem se cadastra para participar de um Evento - sem aprovacao manual do Administrador.');
