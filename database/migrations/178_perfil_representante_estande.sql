-- Fase 54 (27/09/2026): perfil de quem representa um estande de Evento
-- (expositor ou patrocinador). So' nasce por convite do Administrador, pela
-- tela de Estandes, e e' sempre global (concurso_id nulo): nunca da acesso a
-- nada do Concurso. Qual estande a pessoa representa fica em
-- evento_estande_representantes (migration 179), um estande por evento.
INSERT IGNORE INTO perfis (chave, nome_exibicao, descricao)
VALUES ('representante_estande', 'Representante de estande',
        'Acesso restrito ao proprio estande de um Evento, por convite do Administrador.');
