-- Fase 32: relatorio de presenca real (Google Meet) em Mentoria/Oficina.
--
-- A Fase 31 (migration 109) ja sabe quem foi CONVIDADO (attendees) e quem
-- CONFIRMOU (RSVP, tabela google_convite_status). Esta fase acrescenta quem
-- de fato ENTROU na sala, por quanto tempo, e detecta "intrusos" (quem entrou
-- sem estar convidado). A captura e' feita por um processo agendado (cron,
-- database/capturar_presenca_google_meet.php) - o PRIMEIRO do projeto, que
-- ate' aqui era 100% requisicao-resposta.
--
-- google_conference_id: o conferenceData.conferenceId que a Calendar API ja
-- devolvia na criacao do evento, mas que a Fase 31 nao capturava (gap fechado
-- em GoogleCalendarService::interpretarEvento). Sem ele nao ha como saber qual
-- space/conferenceRecord consultar na Meet API depois. Fica NULL para todo
-- horario integrado ANTES desta fase - o backfill retroativo e' feito por
-- database/backfill_conference_id.php (passo de deploy), e tambem de graca
-- sempre que o admin clicar "Verificar novamente" num horario antigo.
--
-- presenca_ultima_tentativa_em: sem esta coluna a cadencia de retentativa nao
-- e' aplicavel - o intervalo real seria ditado so' pelo crontab, e um cron de
-- 15 min queimaria as 30 tentativas em 7,5h em vez das ~15h de projeto. Com
-- ela a cadencia de 30 min fica auto-aplicada no codigo, independente de como
-- o operador configurou o crontab.
ALTER TABLE mentoria_horarios
    ADD COLUMN google_conference_id VARCHAR(255) NULL AFTER google_sincronizado_em,
    ADD COLUMN presenca_status ENUM('pendente', 'capturada', 'indisponivel') NOT NULL DEFAULT 'pendente' AFTER google_conference_id,
    ADD COLUMN presenca_tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER presenca_status,
    ADD COLUMN presenca_ultima_tentativa_em DATETIME NULL AFTER presenca_tentativas,
    ADD COLUMN presenca_capturada_em DATETIME NULL AFTER presenca_ultima_tentativa_em;

ALTER TABLE oficina_horarios
    ADD COLUMN google_conference_id VARCHAR(255) NULL AFTER google_sincronizado_em,
    ADD COLUMN presenca_status ENUM('pendente', 'capturada', 'indisponivel') NOT NULL DEFAULT 'pendente' AFTER google_conference_id,
    ADD COLUMN presenca_tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER presenca_status,
    ADD COLUMN presenca_ultima_tentativa_em DATETIME NULL AFTER presenca_tentativas,
    ADD COLUMN presenca_capturada_em DATETIME NULL AFTER presenca_ultima_tentativa_em;

-- Sessoes BRUTAS de entrada/saida (participantSessions da Meet API), uma linha
-- por trecho - alguem que cai da chamada e volta gera varias linhas, e a
-- duracao final por pessoa e' a SOMA delas (nunca so' a ultima). A agregacao
-- fica em GooglePresencaRepository::listarAgregadoPorHorario(), nao ha tabela
-- de agregado. Mesmo padrao de google_convite_status (migration 109):
-- horario_id resolve por tipo, sem FK cruzada possivel entre as duas tabelas
-- de horario; participante_id e' nullable de proposito (NULL = "nao bateu com
-- nenhum convidado" = intruso).
--
-- nome_bruto e' NULLABLE de proposito: a politica de retencao desta fase
-- anonimiza a linha depois de 30 dias gravando NULL aqui (ver
-- database/expurgar_nomes_presenca.php). Com NOT NULL a politica seria
-- inaplicavel sem uma segunda migration.
--
-- participante_meet_ref guarda o campo `name` do participante devolvido pela
-- Meet API (identificador opaco, escopado ao conferenceRecord, sem dado
-- pessoal). E' por ele que a agregacao agrupa - NUNCA por nome_bruto: depois
-- do expurgo todos os intrusos anonimizados de um mesmo horario colapsariam
-- numa linha so' (participante_id NULL + nome_bruto NULL), somando duracoes de
-- pessoas distintas e destruindo justamente a contagem de intrusos que a
-- politica de retencao quer preservar indefinidamente.
CREATE TABLE IF NOT EXISTS google_presenca_sessoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('mentoria', 'oficina') NOT NULL,
    horario_id INT UNSIGNED NOT NULL,
    participante_meet_ref VARCHAR(255) NULL,
    nome_bruto VARCHAR(255) NULL,
    tipo_origem ENUM('signedinUser', 'anonymousUser', 'phoneUser') NOT NULL,
    participante_id INT UNSIGNED NULL,
    inicio DATETIME NOT NULL,
    fim DATETIME NULL,
    capturado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_google_presenca_sessoes_participante FOREIGN KEY (participante_id) REFERENCES participantes (id),
    KEY idx_google_presenca_sessoes_horario (tipo, horario_id),
    KEY idx_google_presenca_sessoes_participante (participante_id),
    KEY idx_google_presenca_sessoes_expurgo (capturado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
