CREATE TABLE IF NOT EXISTS submissoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etapa_id INT UNSIGNED NOT NULL,
    formulario_dinamico_id INT UNSIGNED NULL,
    equipe_id INT UNSIGNED NULL,
    participante_id INT UNSIGNED NULL,
    dados_json JSON NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_submissoes_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id),
    CONSTRAINT fk_submissoes_formulario FOREIGN KEY (formulario_dinamico_id) REFERENCES formularios_dinamicos (id),
    CONSTRAINT fk_submissoes_equipe FOREIGN KEY (equipe_id) REFERENCES equipes (id),
    CONSTRAINT fk_submissoes_participante FOREIGN KEY (participante_id) REFERENCES participantes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
