-- Fase 53: Anais do Evento. O Administrador monta o volume inteiro fora do
-- servidor (capa, folha de rosto, ficha catalografica, expediente, comissoes,
-- sumario e trabalhos revisados, tudo num unico PDF) e o sistema recebe cada
-- versao desse arquivo, publica, mostra na pagina publica e no aplicativo do
-- participante e avisa os autores dos trabalhos incluidos. Nada e gerado nem
-- juntado aqui: a geracao automatica do volume fica registrada como divida
-- de funcionalidade, sem fase atribuida (Implantar.md, secao 13.12).
--
-- Tudo aditivo. Nenhuma tabela do Concurso e' tocada, e o unico ALTER em
-- tabela existente apenas acrescenta um valor ao fim da lista do ENUM de
-- evento_documentos (tabela criada na 171, em producao desde a Fase 51), sem
-- mexer nos valores atuais.
--
-- evento_anais_versoes: cada PDF enviado pelo Administrador. O arquivo fica
-- em storage/uploads/anais/{evento}/ (pasta privada) ate ser publicado;
-- publicar copia o arquivo para a pasta publica e cria uma versao do
-- Documento do Evento do tipo 'anais' (documento_id). Versao ja publicada
-- nunca e' apagada, so' arquivada pela versao seguinte.
CREATE TABLE IF NOT EXISTS evento_anais_versoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    numero INT UNSIGNED NOT NULL,
    arquivo_path VARCHAR(255) NOT NULL,
    nome_original VARCHAR(255) NULL,
    tamanho_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    sha256 CHAR(64) NULL,
    observacao VARCHAR(255) NULL,
    enviado_por INT UNSIGNED NULL,
    enviado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    publicado_em DATETIME NULL,
    documento_id INT UNSIGNED NULL,
    UNIQUE KEY uq_evento_anais_versoes_numero (evento_id, numero),
    CONSTRAINT fk_evento_anais_versoes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_anais_versoes_enviado_por FOREIGN KEY (enviado_por) REFERENCES usuarios (id) ON DELETE SET NULL,
    CONSTRAINT fk_evento_anais_versoes_documento FOREIGN KEY (documento_id) REFERENCES evento_documentos (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- evento_anais: uma linha por evento (nasce no primeiro salvamento da tela).
-- versao_publicada_id nulo = Anais nao publicados. documento_titulo guarda o
-- titulo usado na primeira publicacao: EventoDocumentoRepository agrupa as
-- versoes de um documento por tipo e titulo, entao o titulo do documento nao
-- muda depois disso, mesmo que a tela permita ajustar a descricao e o
-- identificador. mensagem_publicacao_html e' o texto editavel que
-- acompanha o e-mail de aviso aos autores.
CREATE TABLE IF NOT EXISTS evento_anais (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    identificador_tipo ENUM('nenhum', 'issn', 'isbn') NOT NULL DEFAULT 'nenhum',
    identificador VARCHAR(30) NULL,
    descricao VARCHAR(500) NULL,
    mensagem_publicacao_html MEDIUMTEXT NULL,
    documento_titulo VARCHAR(200) NULL,
    versao_publicada_id INT UNSIGNED NULL,
    publicado_em DATETIME NULL,
    publicado_por INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_anais_evento (evento_id),
    CONSTRAINT fk_evento_anais_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_anais_versao_publicada FOREIGN KEY (versao_publicada_id) REFERENCES evento_anais_versoes (id) ON DELETE SET NULL,
    CONSTRAINT fk_evento_anais_publicado_por FOREIGN KEY (publicado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- evento_anais_exclusoes: so' se grava quem SAI dos Anais. O padrao "todo
-- trabalho aprovado entra" fica implicito, sem linha por trabalho para gerar
-- nem sincronizar. O motivo e' livre e opcional (por exemplo, trabalho nao
-- apresentado, item 9.7 do edital de submissao).
CREATE TABLE IF NOT EXISTS evento_anais_exclusoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    trabalho_id INT UNSIGNED NOT NULL,
    motivo VARCHAR(255) NULL,
    criado_por INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_anais_exclusoes_trabalho (trabalho_id),
    KEY idx_evento_anais_exclusoes_evento (evento_id),
    CONSTRAINT fk_evento_anais_exclusoes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_anais_exclusoes_trabalho FOREIGN KEY (trabalho_id) REFERENCES trabalhos (id) ON DELETE CASCADE,
    CONSTRAINT fk_evento_anais_exclusoes_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documento do Evento do tipo 'anais': valor novo no fim da lista. A tela de
-- Documentos nao oferece nem lista este tipo (a publicacao e a versao so'
-- acontecem na sub-aba Anais de Trabalhos); ele so' existe para o botao da
-- pagina publica poder apontar para o volume publicado.
ALTER TABLE evento_documentos
    MODIFY COLUMN tipo ENUM('edital', 'edital_simples', 'anexo', 'retificacao', 'resultado_final', 'ata', 'anais') NOT NULL;
