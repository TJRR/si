-- Fase 54: montagem automatica do volume dos Anais, segunda forma de
-- produzir uma versao alem do envio do PDF pronto (Fase 53). O autor
-- principal envia pelo aplicativo o PDF final do trabalho dentro do prazo
-- configurado; o Administrador cadastra os dados editoriais e as comissoes,
-- ordena os trabalhos e pede a geracao; a rotina agendada
-- database/gerar_anais.php junta tudo e grava o resultado como versao em
-- rascunho (evento_anais_versoes). A publicacao continua na aba Anais.
--
-- Tudo aditivo: so' tabelas novas. Nenhuma tabela da 177 nem do Concurso e'
-- alterada.

-- evento_anais_montagem: uma linha por evento (nasce no primeiro
-- salvamento). Textos editoriais, capa enviada (area privada) e prazo do
-- PDF final dos autores. A linha tambem serve de trava por evento para o
-- pedido de geracao (um pedido em andamento por vez).
CREATE TABLE IF NOT EXISTS evento_anais_montagem (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    subtitulo VARCHAR(200) NULL,
    local_ano VARCHAR(120) NULL,
    organizadores_html TEXT NULL,
    ficha_catalografica_html TEXT NULL,
    expediente_html MEDIUMTEXT NULL,
    apresentacao_html MEDIUMTEXT NULL,
    capa_path VARCHAR(255) NULL,
    capa_nome_original VARCHAR(255) NULL,
    prazo_pdf_final DATETIME NULL,
    instrucoes_pdf_final_html TEXT NULL,
    mensagem_aviso_pdf_final_html TEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_anais_montagem_evento (evento_id),
    CONSTRAINT fk_evento_anais_montagem_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Comissoes do volume (comissao organizadora, cientifica...), na ordem em
-- que aparecem nas paginas iniciais.
CREATE TABLE IF NOT EXISTS evento_anais_comissoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_evento_anais_comissoes_ordem (evento_id, ordem),
    CONSTRAINT fk_evento_anais_comissoes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Membros de cada comissao. Texto livre de proposito (nome, funcao e
-- instituicao como devem sair impressos): membro de comissao nem sempre tem
-- conta no sistema. Remover a comissao remove os membros.
CREATE TABLE IF NOT EXISTS evento_anais_comissao_membros (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    comissao_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    funcao VARCHAR(120) NULL,
    instituicao VARCHAR(150) NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_evento_anais_comissao_membros_ordem (comissao_id, ordem),
    CONSTRAINT fk_evento_anais_comissao_membros_comissao FOREIGN KEY (comissao_id) REFERENCES evento_anais_comissoes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- PDF final de cada trabalho (enviado pelo autor principal) e a posicao do
-- trabalho no volume. ordem nula = ainda sem ordem manual (vale a ordem
-- padrao: eixo tematico e titulo). Quem consta nos Anais continua sendo
-- decidido por evento_anais_exclusoes (Fase 53); uma linha aqui nao inclui
-- ninguem por si so'.
CREATE TABLE IF NOT EXISTS evento_anais_trabalhos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    trabalho_id INT UNSIGNED NOT NULL,
    ordem INT UNSIGNED NULL,
    arquivo_path VARCHAR(255) NULL,
    nome_original VARCHAR(255) NULL,
    paginas INT UNSIGNED NULL,
    tamanho_bytes BIGINT UNSIGNED NULL,
    sha256 CHAR(64) NULL,
    enviado_por INT UNSIGNED NULL,
    enviado_em DATETIME NULL,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_anais_trabalhos_trabalho (trabalho_id),
    KEY idx_evento_anais_trabalhos_ordem (evento_id, ordem),
    CONSTRAINT fk_evento_anais_trabalhos_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_anais_trabalhos_trabalho FOREIGN KEY (trabalho_id) REFERENCES trabalhos (id) ON DELETE CASCADE,
    CONSTRAINT fk_evento_anais_trabalhos_enviado_por FOREIGN KEY (enviado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Fila dos pedidos de geracao. O servidor web so' registra o pedido; a
-- rotina de linha de comando reserva o mais antigo, gera e marca concluida
-- (com a versao criada) ou falhou (com o motivo).
CREATE TABLE IF NOT EXISTS evento_anais_geracoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    situacao ENUM('pendente', 'processando', 'concluida', 'falhou') NOT NULL DEFAULT 'pendente',
    mensagem TEXT NULL,
    versao_id INT UNSIGNED NULL,
    solicitado_por INT UNSIGNED NULL,
    solicitado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    iniciado_em DATETIME NULL,
    concluido_em DATETIME NULL,
    KEY idx_evento_anais_geracoes_situacao (situacao, solicitado_em),
    KEY idx_evento_anais_geracoes_evento (evento_id),
    CONSTRAINT fk_evento_anais_geracoes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_anais_geracoes_versao FOREIGN KEY (versao_id) REFERENCES evento_anais_versoes (id) ON DELETE SET NULL,
    CONSTRAINT fk_evento_anais_geracoes_solicitado_por FOREIGN KEY (solicitado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
