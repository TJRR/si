-- Fase 54 (27/09/2026): estandes de expositor e de patrocinador de um
-- Evento. Cada estande tem um codigo fixo (QR mais texto no cartaz) que o
-- participante le no aplicativo para registrar a visita e pontuar.
--
-- Tudo aditivo: tabelas novas e um valor novo no fim da lista de tipos de
-- evento_secoes_ordem (tabela da Fase 51, em producao), para o componente
-- "Estandes" da pagina publica, mesmo tipo de alteracao que a 177 fez em
-- evento_documentos. Nenhuma tabela do Concurso e' tocada.

-- codigo_estande: mesmo molde de evento_atividades.codigo_atividade
-- (Crockford Base32, 6 caracteres, gerado uma vez por CodigoUnicoService e
-- nunca regenerado). A chave unica (id, evento_id) existe so' para a chave
-- estrangeira composta de evento_estande_representantes.
CREATE TABLE IF NOT EXISTS evento_estandes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    categoria ENUM('expositor', 'patrocinador') NOT NULL DEFAULT 'expositor',
    descricao_html TEXT NULL,
    logotipo_path VARCHAR(255) NULL,
    logotipo_alt VARCHAR(255) NULL,
    pontos_visita SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    codigo_estande CHAR(6) NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_estandes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    UNIQUE KEY uq_evento_estandes_codigo (codigo_estande),
    UNIQUE KEY uq_evento_estandes_id_evento (id, evento_id),
    INDEX idx_evento_estandes_evento_ordem (evento_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Representante do estande: um por estande, e uma pessoa com no maximo um
-- estande por evento (em outro evento, pode representar outro). A chave
-- composta garante que evento_id e' sempre o evento do proprio estande.
CREATE TABLE IF NOT EXISTS evento_estande_representantes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    estande_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_estande_repr_estande_evento FOREIGN KEY (estande_id, evento_id) REFERENCES evento_estandes (id, evento_id),
    CONSTRAINT fk_evento_estande_repr_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    UNIQUE KEY uq_evento_estande_repr_estande (estande_id),
    UNIQUE KEY uq_evento_estande_repr_evento_usuario (evento_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Visita: mesmo desenho de evento_checkins (fato do evento, ligado a
-- inscricao). pontos_creditados congela o valor do estande no momento da
-- leitura; a chave unica permite uma visita por estande por participante.
CREATE TABLE IF NOT EXISTS evento_estande_visitas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    estande_id INT UNSIGNED NOT NULL,
    evento_inscricao_id INT UNSIGNED NOT NULL,
    pontos_creditados SMALLINT UNSIGNED NOT NULL,
    visitado_em DATETIME NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_estande_visitas_estande FOREIGN KEY (estande_id) REFERENCES evento_estandes (id),
    CONSTRAINT fk_evento_estande_visitas_inscricao FOREIGN KEY (evento_inscricao_id) REFERENCES evento_inscricoes (id),
    UNIQUE KEY uq_evento_estande_visitas (estande_id, evento_inscricao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limite de tentativas da leitura do codigo do estande, por usuario, como
-- evento_leituras_codigo_falhas (Fase 43): nao ha falha depois de achar um
-- estande real, entao nao existe coluna de estande aqui.
CREATE TABLE IF NOT EXISTS evento_estande_leituras_falhas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_estande_leituras_falhas_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE CASCADE,
    INDEX idx_estande_leituras_falhas_usuario_criado (usuario_id, criado_em)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuracao de Estandes por evento: texto editavel que acompanha o
-- convite ao representante. Nasce no primeiro salvamento da tela.
CREATE TABLE IF NOT EXISTS evento_estandes_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    mensagem_convite_html MEDIUMTEXT NULL,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_estandes_config_evento (evento_id),
    CONSTRAINT fk_evento_estandes_config_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Componente "Estandes" da pagina publica, no molde de evento_secao_local:
-- sem tabela de itens, porque os itens sao os estandes ativos do evento.
CREATE TABLE IF NOT EXISTS evento_secao_estandes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    etiqueta VARCHAR(60) NULL,
    titulo VARCHAR(150) NULL,
    descricao_html TEXT NULL,
    cor_fundo VARCHAR(7) NULL,
    cor_texto VARCHAR(7) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_secao_estandes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_secao_estandes_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valor novo no fim da lista de tipos de secao da pagina; os valores atuais
-- ficam como estao.
ALTER TABLE evento_secoes_ordem
    MODIFY COLUMN tipo ENUM(
        'quadros','faixas','bloco','contagem','cronograma',
        'cartoes','destaques','programacao','faq','local','estandes'
    ) NOT NULL;
