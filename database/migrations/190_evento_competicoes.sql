-- Fase 58: competicoes e experiencias do evento (Karaoke, Batalha de
-- Prompts). A dinamica de pontos v2 pontua a PARTICIPACAO, lida num codigo
-- "na mao do responsavel", sem inscricao previa; vencer nao pontua, entao
-- nao ha' resultado nem vencedor.
--
-- atividade_id: a atividade da programacao em que a competicao acontece.
-- Da' a janela de leitura e diz quem e' o facilitador que ve o codigo em
-- tela cheia. ON DELETE SET NULL, e nao o bloqueio padrao: remover a
-- atividade nao pode esbarrar na competicao nem cair na mensagem "ja tem
-- inscricoes" de AtividadeAdminController::remover().
--
-- codigo_participacao: 6 caracteres, unico entre as tres colunas lidas pelo
-- leitor de presenca (CodigoUnicoService::gerarCodigoFixoDoEvento()).
--
-- ativo: competicao com participacao nunca e' apagada, e sim desativada.
CREATE TABLE IF NOT EXISTS evento_competicoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    regras_html MEDIUMTEXT NULL,
    atividade_id INT UNSIGNED NULL,
    codigo_participacao CHAR(6) NOT NULL,
    pontos_participacao SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_competicoes_codigo (codigo_participacao),
    INDEX idx_evento_competicoes_evento_ordem (evento_id, ordem),
    CONSTRAINT fk_evento_competicoes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_competicoes_atividade FOREIGN KEY (atividade_id) REFERENCES evento_atividades (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
