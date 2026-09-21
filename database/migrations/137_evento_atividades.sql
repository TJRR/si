-- Fase 46 (Semana de Inovacao, Bloco C): primeira entidade filha de arvore
-- de Evento — Atividades da agenda (cursos/palestras/seminarios), com
-- inscricao opcional (vagas configuraveis por atividade: ilimitada, limitada
-- com lista de espera, ou limitada com bloqueio ao lotar). FK de
-- evento_atividade_inscricoes aponta para evento_inscricoes (nao para
-- usuarios direto) — so' quem ja' esta' inscrito no EVENTO pode se inscrever
-- numa atividade dele. codigo_atividade (Crockford Base32, mesmo alfabeto de
-- evento_inscricoes.codigo_credenciamento, agora em CodigoUnicoService) e'
-- gerado sempre na criacao pelo painel — sem backfill necessario, por isso
-- NOT NULL (diferente de codigo_credenciamento, que nasceu NULL por causa de
-- linhas legadas anteriores a` coluna existir). Sem status 'cancelada' em
-- evento_atividade_inscricoes: cancelamento e' DELETE da linha, mesmo
-- espirito do resto do sistema (nenhuma tabela faz soft-delete; Auditoria e'
-- a trilha historica).
CREATE TABLE IF NOT EXISTS evento_atividades (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao_html TEXT NULL,
    local VARCHAR(150) NULL,
    data_inicio DATETIME NOT NULL,
    data_fim DATETIME NOT NULL,
    exige_inscricao TINYINT(1) NOT NULL DEFAULT 0,
    emite_certificado TINYINT(1) NOT NULL DEFAULT 0,
    vagas INT UNSIGNED NULL,
    permite_lista_espera TINYINT(1) NOT NULL DEFAULT 0,
    codigo_atividade CHAR(6) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_atividades_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    UNIQUE KEY uq_evento_atividades_codigo (codigo_atividade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evento_atividade_inscricoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    atividade_id INT UNSIGNED NOT NULL,
    evento_inscricao_id INT UNSIGNED NOT NULL,
    status ENUM('confirmada', 'espera') NOT NULL DEFAULT 'confirmada',
    inscrito_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_atividade_inscricoes_atividade FOREIGN KEY (atividade_id) REFERENCES evento_atividades (id),
    CONSTRAINT fk_evento_atividade_inscricoes_inscricao FOREIGN KEY (evento_inscricao_id) REFERENCES evento_inscricoes (id),
    UNIQUE KEY uq_evento_atividade_inscricoes (atividade_id, evento_inscricao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
