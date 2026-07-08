CREATE TABLE IF NOT EXISTS importacao_pendencias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trilha_id INT UNSIGNED NOT NULL,
    equipe_id INT UNSIGNED NULL,
    participante_id INT UNSIGNED NULL,
    tipo ENUM(
        'cpf_invalido',
        'cpf_duplicado_na_equipe',
        'cpf_duplicado_entre_equipes',
        'erro_processamento'
    ) NOT NULL,
    aba VARCHAR(30) NOT NULL,
    linha_planilha INT UNSIGNED NOT NULL,
    descricao TEXT NOT NULL,
    dados_brutos_json JSON NULL,
    status ENUM('pendente', 'resolvido', 'ignorado') NOT NULL DEFAULT 'pendente',
    resolvido_por INT UNSIGNED NULL,
    resolvido_em DATETIME NULL,
    observacao_resolucao TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_importacao_pendencias_trilha FOREIGN KEY (trilha_id) REFERENCES trilhas (id),
    CONSTRAINT fk_importacao_pendencias_equipe FOREIGN KEY (equipe_id) REFERENCES equipes (id),
    CONSTRAINT fk_importacao_pendencias_participante FOREIGN KEY (participante_id) REFERENCES participantes (id),
    CONSTRAINT fk_importacao_pendencias_resolvido_por FOREIGN KEY (resolvido_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
