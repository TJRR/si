-- Motor generico de notificacoes do painel (sino no topo, usado pelos 4
-- perfis) - distinto da tabela `notificacoes` (fila/log de envio de e-mail,
-- migration 017, usada por NotificacaoService). Usado inicialmente para
-- alertar o participante sobre CPF invalido e motivo de rejeicao da
-- inscricao (Fase 12) - tipo e' texto livre para permitir novos usos futuros
-- (admin/avaliador) sem alterar o schema.
CREATE TABLE IF NOT EXISTS notificacoes_painel (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    mensagem TEXT NOT NULL,
    dados JSON NULL,
    lida TINYINT(1) NOT NULL DEFAULT 0,
    registrado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notificacoes_painel_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    KEY idx_notificacoes_painel_usuario_tipo (usuario_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
