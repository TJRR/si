-- Fase 38B: agendamento das apresentacoes de pitch (Etapa 3), Edital NPI
-- n.9/2026, item 2.3. etapa_id NOT NULL (diferente de mentoria_horarios,
-- onde e' opcional) - toda linha pertence a uma Etapa especifica, que por
-- sua vez pertence a uma Trilha e um Concurso especificos, isolando por
-- construcao a grade de cada edicao futura do premio.
--
-- modalidade comeca NULL (equipe ainda nao escolheu) e vira 'presencial'
-- ou 'online' na reserva. equipe_id NULL = vago, preenchido = reservado -
-- mesmo modelo de mentoria_horarios. As 6 colunas de integracao Google sao
-- copia estrutural de mentoria_horarios/oficina_horarios (Fase 31/32), sem
-- reaproveitamento de codigo entre as tabelas.
CREATE TABLE IF NOT EXISTS apresentacoes_pitch (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etapa_id INT UNSIGNED NOT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    modalidade ENUM('presencial', 'online') NULL,
    equipe_id INT UNSIGNED NULL,
    reservado_em DATETIME NULL,
    integracao_google TINYINT(1) NOT NULL DEFAULT 0,
    google_event_id VARCHAR(255) NULL,
    google_calendar_id VARCHAR(255) NULL,
    meet_link_origem ENUM('google_auto', 'manual') NULL,
    meet_pendente TINYINT(1) NOT NULL DEFAULT 0,
    google_sincronizado_em DATETIME NULL,
    google_conference_id VARCHAR(255) NULL,
    presenca_status ENUM('pendente', 'capturada', 'indisponivel') NOT NULL DEFAULT 'pendente',
    presenca_tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0,
    presenca_ultima_tentativa_em DATETIME NULL,
    presenca_capturada_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_apresentacoes_pitch_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id),
    CONSTRAINT fk_apresentacoes_pitch_equipe FOREIGN KEY (equipe_id) REFERENCES equipes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuracao da grade, 1 linha por etapa: janela em que as equipes podem
-- escolher (24 a 30/09 nesta edicao), conta institucional que organiza os
-- eventos no Google, e o endereco fixo mostrado quando a equipe escolhe
-- apresentacao presencial. Tabela separada (nao colunas soltas em `etapas`)
-- porque e' especifica desta funcionalidade, sem sentido pra qualquer outra
-- etapa do sistema.
CREATE TABLE IF NOT EXISTS apresentacao_pitch_config (
    etapa_id INT UNSIGNED NOT NULL PRIMARY KEY,
    janela_escolha_inicio DATETIME NULL,
    janela_escolha_fim DATETIME NULL,
    email_organizador_google VARCHAR(190) NULL,
    endereco_presencial VARCHAR(255) NULL,
    CONSTRAINT fk_apresentacao_pitch_config_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
