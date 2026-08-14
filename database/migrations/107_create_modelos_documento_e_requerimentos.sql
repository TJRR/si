-- Fase 30: modelos de documento cadastrados pelo Administrador dentro de uma
-- Etapa (pode haver mais de um por etapa) e os requerimentos que o lider de
-- uma equipe classificada protocola a partir de um desses modelos - gera o
-- PDF sem assinatura, assina fora do sistema (gov.br), devolve assinado,
-- entra num fluxo de analise que espelha o Tira-Duvidas (duvidas/
-- duvida_respostas/duvida_escalonamentos, migration 105), mas em tabelas
-- proprias: o documento assinado carrega peso juridico, os campos fazem
-- sentido diferente do de uma duvida (necessidade declarada, desfecho
-- estruturado, conferencia de assinatura, retencao/expurgo).

CREATE TABLE IF NOT EXISTS modelos_documento (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    etapa_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    finalidade TEXT NOT NULL,
    corpo_html MEDIUMTEXT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_modelos_documento_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id),
    KEY idx_modelos_documento_etapa (etapa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- equipe_id/concurso_id/trilha_id/etapa_id gravados direto na linha, mesmo
-- padrao de duvidas - evita join a cada consulta. 'aguardando_assinatura' e'
-- o estado inicial (e tambem o estado de retorno apos um pedido de
-- esclarecimentos) - o lider ainda precisa sair do sistema, assinar no
-- gov.br e devolver o PDF. enviado_em (nunca criado_em) e' a unica
-- referencia valida pro prazo de atendimento de 48h: e' sobrescrita a cada
-- vez que enviarAssinado() roda, entao sempre marca "quando o ultimo
-- documento assinado de fato chegou" - criado_em so' marca quando o lider
-- comecou a preencher, o que pode ser dias antes de ele voltar assinado.
-- reaberta_em e' so' um marcador de exibicao (quantas vezes este pedido ja
-- voltou pro lider), nunca entra no calculo de prazo. expurgado_em marca
-- quando o pdf_assinado_path foi apagado fisicamente (ver ArquivoPrivadoService
-- e ModeloDocumentoAdminController::expurgar()) - a linha nunca e' apagada.
CREATE TABLE IF NOT EXISTS requerimentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    modelo_documento_id INT UNSIGNED NOT NULL,
    etapa_id INT UNSIGNED NOT NULL,
    trilha_id INT UNSIGNED NOT NULL,
    concurso_id INT UNSIGNED NOT NULL,
    equipe_id INT UNSIGNED NOT NULL,
    participante_id INT UNSIGNED NOT NULL,
    necessidade TEXT NOT NULL,
    pdf_assinado_path VARCHAR(255) NULL,
    pdf_assinado_nome_original VARCHAR(255) NULL,
    expurgado_em DATETIME NULL,
    status ENUM('aguardando_assinatura', 'recebido', 'escalado', 'esclarecimento_solicitado', 'aprovado', 'recusado', 'revogado') NOT NULL DEFAULT 'aguardando_assinatura',
    responsavel_atual_usuario_id INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    enviado_em DATETIME NULL,
    reaberta_em DATETIME NULL,
    CONSTRAINT fk_requerimentos_modelo FOREIGN KEY (modelo_documento_id) REFERENCES modelos_documento (id),
    CONSTRAINT fk_requerimentos_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id),
    CONSTRAINT fk_requerimentos_trilha FOREIGN KEY (trilha_id) REFERENCES trilhas (id),
    CONSTRAINT fk_requerimentos_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    CONSTRAINT fk_requerimentos_equipe FOREIGN KEY (equipe_id) REFERENCES equipes (id),
    CONSTRAINT fk_requerimentos_participante FOREIGN KEY (participante_id) REFERENCES participantes (id),
    CONSTRAINT fk_requerimentos_responsavel FOREIGN KEY (responsavel_atual_usuario_id) REFERENCES usuarios (id),
    KEY idx_requerimentos_equipe (equipe_id),
    KEY idx_requerimentos_status (status),
    KEY idx_requerimentos_responsavel (responsavel_atual_usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Historico de respostas - "desfecho" e' a diferenca central em relacao a
-- duvida_respostas: toda resposta aqui carrega uma de quatro vias, que
-- tambem e' o proprio valor gravado em requerimentos.status logo em
-- seguida (aprovado/recusado/revogado sao definitivos;
-- esclarecimento_solicitado devolve pro lider). assinatura_conferida so'
-- e' exigida (=1) quando desfecho='aprovado' - confirmacao humana
-- obrigatoria de que a assinatura foi validada no site oficial do gov.br,
-- nunca dispensada mesmo quando ha extracao automatica de dado do
-- certificado (ver plano da Fase 30, secao "Conferencia da assinatura").
-- anexo_expurgado_em e' proprio de cada resposta (nao do requerimento pai)
-- porque um requerimento pode ganhar respostas novas, com anexo novo,
-- depois de um expurgo anterior no mesmo requerimento - a data de expurgo
-- de um arquivo velho nao pode valer pra um arquivo que nem existia ainda.
CREATE TABLE IF NOT EXISTS requerimento_respostas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requerimento_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    desfecho ENUM('aprovado', 'recusado', 'esclarecimento_solicitado', 'revogado') NOT NULL,
    resposta TEXT NOT NULL,
    assinatura_conferida TINYINT(1) NOT NULL DEFAULT 0,
    anexo_path VARCHAR(255) NULL,
    anexo_nome_original VARCHAR(255) NULL,
    anexo_expurgado_em DATETIME NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_requerimento_respostas_requerimento FOREIGN KEY (requerimento_id) REFERENCES requerimentos (id),
    CONSTRAINT fk_requerimento_respostas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    KEY idx_requerimento_respostas_requerimento (requerimento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Identica a duvida_escalonamentos: historico acumulado, sem UNIQUE KEY.
CREATE TABLE IF NOT EXISTS requerimento_escalonamentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    requerimento_id INT UNSIGNED NOT NULL,
    de_usuario_id INT UNSIGNED NOT NULL,
    para_usuario_id INT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_requerimento_escalonamentos_requerimento FOREIGN KEY (requerimento_id) REFERENCES requerimentos (id),
    CONSTRAINT fk_requerimento_escalonamentos_de FOREIGN KEY (de_usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_requerimento_escalonamentos_para FOREIGN KEY (para_usuario_id) REFERENCES usuarios (id),
    KEY idx_requerimento_escalonamentos_requerimento_criado (requerimento_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
