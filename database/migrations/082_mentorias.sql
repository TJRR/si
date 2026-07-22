-- Fase 19 (#106): agendamento de mentorias. Mentor = usuario com perfil
-- administrador/suporte (sem cadastro/perfil novo). equipe_id NULL = vago;
-- preenchido = reservado. Modelo "admin cria horario vago, equipe reserva".
CREATE TABLE IF NOT EXISTS mentoria_horarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NOT NULL,
    mentor_usuario_id INT UNSIGNED NOT NULL,
    equipe_id INT UNSIGNED NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    observacao VARCHAR(255) NULL,
    reservado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_mentoria_horarios_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    CONSTRAINT fk_mentoria_horarios_mentor FOREIGN KEY (mentor_usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_mentoria_horarios_equipe FOREIGN KEY (equipe_id) REFERENCES equipes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
