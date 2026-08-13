-- Fase 29 (Melhoria 6): sistema de Tira-Duvidas - canal estruturado entre
-- participante e organizacao (participante registra, Administrador responde
-- ou escala pra outro administrador/suporte, com SLA de 48h e historico de
-- escalonamento). Ate aqui isso corria por WhatsApp/e-mail informal, sem
-- rastreabilidade nem responsavel definido.
--
-- equipe_id/concurso_id/trilha_id gravados direto na linha (nao derivados
-- por join toda hora) - mesmo padrao ja usado em documentos/mentoria_horarios.
-- responsavel_atual_usuario_id NULL = fila geral (visivel a todo
-- Administrador do concurso); preenchido = escalada, visivel so' a quem foi
-- designado.
CREATE TABLE IF NOT EXISTS duvidas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    participante_id INT UNSIGNED NOT NULL,
    equipe_id INT UNSIGNED NOT NULL,
    concurso_id INT UNSIGNED NOT NULL,
    trilha_id INT UNSIGNED NOT NULL,
    pergunta TEXT NOT NULL,
    anexo_path VARCHAR(255) NULL,
    anexo_nome_original VARCHAR(255) NULL,
    status ENUM('recebida', 'escalada', 'respondida') NOT NULL DEFAULT 'recebida',
    responsavel_atual_usuario_id INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    reaberta_em DATETIME NULL,
    CONSTRAINT fk_duvidas_participante FOREIGN KEY (participante_id) REFERENCES participantes (id),
    CONSTRAINT fk_duvidas_equipe FOREIGN KEY (equipe_id) REFERENCES equipes (id),
    CONSTRAINT fk_duvidas_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    CONSTRAINT fk_duvidas_trilha FOREIGN KEY (trilha_id) REFERENCES trilhas (id),
    CONSTRAINT fk_duvidas_responsavel FOREIGN KEY (responsavel_atual_usuario_id) REFERENCES usuarios (id),
    KEY idx_duvidas_equipe (equipe_id),
    KEY idx_duvidas_status (status),
    KEY idx_duvidas_responsavel (responsavel_atual_usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historico de respostas (mais de uma ao longo do tempo por causa de
-- reabertura - nunca sobrescreve, so' acumula).
CREATE TABLE IF NOT EXISTS duvida_respostas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    duvida_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    resposta TEXT NOT NULL,
    anexo_path VARCHAR(255) NULL,
    anexo_nome_original VARCHAR(255) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_duvida_respostas_duvida FOREIGN KEY (duvida_id) REFERENCES duvidas (id),
    CONSTRAINT fk_duvida_respostas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    KEY idx_duvida_respostas_duvida (duvida_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historico de escalonamentos (quem escalou pra quem e quando) - padrao
-- "quem/pra quem/quando" de avaliador_designacoes, mas sem UNIQUE KEY: aqui
-- e' historico acumulado, nao vinculo atual unico.
CREATE TABLE IF NOT EXISTS duvida_escalonamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    duvida_id INT UNSIGNED NOT NULL,
    de_usuario_id INT UNSIGNED NOT NULL,
    para_usuario_id INT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_duvida_escalonamentos_duvida FOREIGN KEY (duvida_id) REFERENCES duvidas (id),
    CONSTRAINT fk_duvida_escalonamentos_de FOREIGN KEY (de_usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_duvida_escalonamentos_para FOREIGN KEY (para_usuario_id) REFERENCES usuarios (id),
    KEY idx_duvida_escalonamentos_duvida_criado (duvida_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
