CREATE TABLE IF NOT EXISTS equipe_participante (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    equipe_id INT UNSIGNED NOT NULL,
    participante_id INT UNSIGNED NOT NULL,
    papel ENUM('lider', 'integrante') NOT NULL DEFAULT 'integrante',
    CONSTRAINT fk_equipe_participante_equipe FOREIGN KEY (equipe_id) REFERENCES equipes (id),
    CONSTRAINT fk_equipe_participante_participante FOREIGN KEY (participante_id) REFERENCES participantes (id),
    UNIQUE KEY uq_equipe_participante (equipe_id, participante_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
