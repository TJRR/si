-- Fase 39 (correcao pos-teste, 11/09/2026): os campos da inscricao do
-- evento deixam de ser fixos no codigo - o Administrador passa a defini-los
-- (tipo, rotulo, ordem, obrigatorio, texto de ajuda) numa sub-aba propria
-- do evento ("Formulario de inscricao"). Tabela paralela e' propria do
-- dominio Evento (nao reaproveita campos_dinamicos/formularios_dinamicos
-- do concurso - aquela e' fortemente amarrada a concurso_id NOT NULL e ao
-- motor de etapa/equipe, ver investigacao registrada em memoria do
-- projeto). `tipo` continua VARCHAR livre (nao ENUM), mesmo padrao de
-- campos_dinamicos.tipo, para crescer sem precisar de migration nova.
CREATE TABLE IF NOT EXISTS evento_campos_inscricao (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    rotulo VARCHAR(150) NOT NULL,
    tipo VARCHAR(30) NOT NULL,
    obrigatorio TINYINT(1) NOT NULL DEFAULT 0,
    texto_ajuda VARCHAR(255) NULL,
    config_json JSON NULL,
    CONSTRAINT fk_evento_campos_inscricao_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fase 49B (correcao na fonte): a migration 119 ja nasce sem
-- tipo_documento/cargo/categoria_profissional/tribunal_orgao_origem/
-- categoria_inscricao/documento (movidos para usuarios_perfil ou para
-- resposta de campo configuravel), entao aqui so falta acrescentar
-- respostas_json - nada a dropar. "Tipo de documento", "Cargo" e "Órgão
-- de origem" continuam existindo como CAMPOS CONFIGURAVEIS deste
-- formulario (semeados mais abaixo nesta mesma migration), cuja resposta
-- e' sempre sincronizada com usuarios_perfil no momento da inscricao
-- (EventoInscricaoPublicaController::inscrever()) - "categoria_profissional"
-- e' removida de vez (vai morar no futuro perfil do participante, fora do
-- escopo do evento).
ALTER TABLE evento_inscricoes
    ADD COLUMN respostas_json JSON NULL;

-- Semeia os campos configuraveis do evento 1 (5a Semana de Inovacao) com
-- os dados reais que o usuario forneceu para esta edicao - totalmente
-- editavel depois pela tela administrativa nova, mesmo precedente ja
-- aceito para o cadastro do proprio evento (migration 119).
INSERT IGNORE INTO evento_campos_inscricao (id, evento_id, ordem, rotulo, tipo, obrigatorio, texto_ajuda, config_json) VALUES
    (1, 1, 1, 'Tipo de documento', 'lista_opcoes', 1, 'Selecione o tipo do documento informado acima.', JSON_OBJECT('opcoes', JSON_ARRAY('RG', 'CPF', 'RNE', 'Passaporte'))),
    (2, 1, 2, 'Cargo', 'lista_opcoes', 0, 'Opcional. Selecione a opção que melhor representa seu cargo ou categoria profissional.', JSON_OBJECT('opcoes', JSON_ARRAY('Magistrado', 'Promotor', 'Defensor Público', 'Advogado', 'Analista Judiciário', 'Servidor Público', 'Empresário', 'Prestador de serviço', 'Estagiário', 'Estudante'))),
    (3, 1, 3, 'Órgão de origem', 'texto', 0, 'Opcional. Instituição de onde você vem (ex.: TJRR, MP-RR, DPE-RR, UFRR).', NULL),
    (4, 1, 4, 'Tipo de vínculo', 'lista_opcoes', 0, 'Opcional. Como você participa deste evento.', JSON_OBJECT('opcoes', JSON_ARRAY('Participante', 'Palestrante', 'Expositor', 'Avaliador', 'Apoiador', 'Colaborador')));
