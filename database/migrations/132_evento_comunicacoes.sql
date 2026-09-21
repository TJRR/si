-- Fase 45: aviso em massa aos inscritos de um evento (sub-aba "Comunicacao").
-- evento_comunicacoes e' a campanha (assunto/corpo digitados pelo Admin,
-- contadores de progresso); evento_comunicacao_destinatarios e' a fila de
-- processamento, uma linha por inscrito selecionado no disparo - o script
-- database/processar_comunicacao_evento.php (chamado por cron a cada 1
-- minuto) processa ate' 10 pendentes por execucao, entre todas as campanhas
-- em aberto, o que impoe naturalmente o lote de 10/60s exigido pelo canal de
-- SMTP institucional compartilhado com o 5o Premio de Inovacao em andamento.
CREATE TABLE IF NOT EXISTS evento_comunicacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    autor_usuario_id INT UNSIGNED NOT NULL,
    assunto VARCHAR(200) NOT NULL,
    corpo_html MEDIUMTEXT NOT NULL,
    total_destinatarios INT UNSIGNED NOT NULL DEFAULT 0,
    total_enviados INT UNSIGNED NOT NULL DEFAULT 0,
    total_falhas INT UNSIGNED NOT NULL DEFAULT 0,
    concluido_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_comunicacoes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_comunicacoes_autor FOREIGN KEY (autor_usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evento_comunicacao_destinatarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comunicacao_id INT UNSIGNED NOT NULL,
    evento_inscricao_id INT UNSIGNED NOT NULL,
    status ENUM('pendente', 'enviado', 'falhou') NOT NULL DEFAULT 'pendente',
    processado_em DATETIME NULL,
    CONSTRAINT fk_evento_comunicacao_destinatarios_comunicacao FOREIGN KEY (comunicacao_id) REFERENCES evento_comunicacoes (id),
    CONSTRAINT fk_evento_comunicacao_destinatarios_inscricao FOREIGN KEY (evento_inscricao_id) REFERENCES evento_inscricoes (id),
    INDEX idx_evento_comunicacao_destinatarios_status (status, comunicacao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
