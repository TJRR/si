-- Fase 31: integracao opcional de Mentoria/Oficina com o Google Agenda do
-- organizador (mentor_usuario_id / criado_por), via Service Account com
-- Domain-Wide Delegation (sem OAuth individual por admin - ver
-- App\Core\GoogleServiceAccountAuth). integracao_google=0 preserva 100% do
-- fluxo manual atual de link_meet; as colunas abaixo so' sao lidas/escritas
-- quando integracao_google=1. google_calendar_id aponta pra agenda
-- secundaria do organizador (criada automaticamente na primeira integracao,
-- ver App\Services\GoogleCalendarService::garantirCalendarioSecundario).
-- meet_pendente cobre o caso em que o Google ainda nao terminou de gerar a
-- sala do Meet no momento da criacao do evento; google_sincronizado_em
-- registra a ultima chamada bem-sucedida ao Google (usado pro throttle de
-- reconciliacao sob demanda, ja que o projeto nao tem fila/cron).
ALTER TABLE mentoria_horarios
    ADD COLUMN integracao_google TINYINT(1) NOT NULL DEFAULT 0 AFTER link_meet,
    ADD COLUMN google_event_id VARCHAR(255) NULL AFTER integracao_google,
    ADD COLUMN google_calendar_id VARCHAR(255) NULL AFTER google_event_id,
    ADD COLUMN meet_link_origem ENUM('google_auto', 'manual') NULL AFTER google_calendar_id,
    ADD COLUMN meet_pendente TINYINT(1) NOT NULL DEFAULT 0 AFTER meet_link_origem,
    ADD COLUMN google_sincronizado_em DATETIME NULL AFTER meet_pendente;

ALTER TABLE oficina_horarios
    ADD COLUMN integracao_google TINYINT(1) NOT NULL DEFAULT 0 AFTER link_meet,
    ADD COLUMN google_event_id VARCHAR(255) NULL AFTER integracao_google,
    ADD COLUMN google_calendar_id VARCHAR(255) NULL AFTER google_event_id,
    ADD COLUMN meet_link_origem ENUM('google_auto', 'manual') NULL AFTER google_calendar_id,
    ADD COLUMN meet_pendente TINYINT(1) NOT NULL DEFAULT 0 AFTER meet_link_origem,
    ADD COLUMN google_sincronizado_em DATETIME NULL AFTER meet_pendente;

-- Rastreio de aceite/recusa do convite (RSVP), atualizado a partir da
-- resposta da propria Calendar API (attendees[].responseStatus) sempre que
-- o evento e' lido (criacao, patch de attendees, ou reconciliacao sob
-- demanda) - nunca uma chamada extra so' pra isso. horario_id resolve por
-- tipo (mentoria_horarios ou oficina_horarios), sem FK cruzada unica
-- possivel entre as duas tabelas. participante_id e' so' rotulo de
-- exibicao; a chave real e' o e-mail, que e' o que o Google conhece.
CREATE TABLE IF NOT EXISTS google_convite_status (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('mentoria', 'oficina') NOT NULL,
    horario_id INT UNSIGNED NOT NULL,
    email VARCHAR(190) NOT NULL,
    participante_id INT UNSIGNED NULL,
    status ENUM('needsAction', 'accepted', 'declined', 'tentative') NOT NULL DEFAULT 'needsAction',
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_google_convite_status_participante FOREIGN KEY (participante_id) REFERENCES participantes (id),
    UNIQUE KEY uq_google_convite_status (tipo, horario_id, email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
