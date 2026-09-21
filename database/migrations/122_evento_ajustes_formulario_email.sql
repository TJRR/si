-- Fase 39 (correcao pos-teste #2, 14/09/2026): corrige nos bancos ja
-- migrados os 2 campos semeados errado pela migration 121 (o arquivo fonte
-- ja foi corrigido para reinstalacoes novas, mas esta migration ja tinha
-- sido aplicada aqui - precisa de UPDATE explicito). "Cargo" passa a ser
-- lista fechada (mesma lista de "categoria profissional" removida na
-- correcao anterior, reaproveitada aqui a pedido do usuario); "Tribunal ou
-- outro orgao de origem" vira so' "Orgao de origem", com novo texto de
-- ajuda.
UPDATE evento_campos_inscricao
   SET rotulo = 'Cargo',
       tipo = 'lista_opcoes',
       texto_ajuda = 'Opcional. Selecione a opção que melhor representa seu cargo ou categoria profissional.',
       config_json = JSON_OBJECT('opcoes', JSON_ARRAY('Magistrado', 'Promotor', 'Defensor Público', 'Advogado', 'Analista Judiciário', 'Servidor Público', 'Empresário', 'Prestador de serviço', 'Estagiário', 'Estudante'))
 WHERE id = 2 AND rotulo = 'Cargo';

UPDATE evento_campos_inscricao
   SET rotulo = 'Órgão de origem',
       texto_ajuda = 'Opcional. Instituição de onde você vem (ex.: TJRR, MP-RR, DPE-RR, UFRR).'
 WHERE id = 3 AND rotulo = 'Tribunal ou outro órgão de origem';

-- E-mail de confirmacao de inscricao: texto configuravel pelo Admin (editor
-- rico, sub-aba "Dados Gerais" do evento) - vazio usa um texto padrao
-- generico no envio (ver NotificacaoService::confirmarInscricaoEvento()).
ALTER TABLE eventos
    ADD COLUMN mensagem_confirmacao_inscricao MEDIUMTEXT NULL AFTER descricao;
