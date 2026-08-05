-- Fase 24: oficinas - encontros coletivos com tema pre-definido, sem
-- "mentor" (o organizador do concurso conduz). Diferente de mentoria
-- (mentoria_horarios: 1 equipe por horario, equipe_id direto na linha),
-- uma oficina aceita varias equipes no mesmo horario - por isso a
-- inscricao vive numa tabela N:N separada em vez de uma coluna equipe_id.
CREATE TABLE IF NOT EXISTS oficina_horarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NOT NULL,
    tema VARCHAR(255) NOT NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    link_meet VARCHAR(255) NULL,
    observacao VARCHAR(255) NULL,
    criado_por INT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_oficina_horarios_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    CONSTRAINT fk_oficina_horarios_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS oficina_inscricoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    oficina_horario_id INT UNSIGNED NOT NULL,
    equipe_id INT UNSIGNED NOT NULL,
    inscrito_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_oficina_inscricoes_horario FOREIGN KEY (oficina_horario_id) REFERENCES oficina_horarios (id),
    CONSTRAINT fk_oficina_inscricoes_equipe FOREIGN KEY (equipe_id) REFERENCES equipes (id),
    UNIQUE KEY uq_oficina_inscricoes (oficina_horario_id, equipe_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
