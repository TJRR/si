-- Fase 56 (29/09/2026): Divulgacao - comprovacao de publicacao em redes
-- sociais, com pontuacao (secao 6 do documento da dinamica de pontos).
--
-- Tres acoes: publicar sobre o evento no Instagram, publicar no LinkedIn e
-- passar a acompanhar os canais do Tribunal. Nenhuma delas o sistema
-- consegue apurar sozinho, entao a pessoa envia a prova (imagem da tela ou
-- endereco da publicacao) e o credito e' AUTOMATICO no envio, por decisao
-- do dono: a conferencia humana existe depois do fato, quando ha' suspeita,
-- e o Administrador anula com justificativa. O ranking geral e' da Fase 58:
-- aqui so' se grava o fato, a prova e os pontos vigentes no momento,
-- congelados.
--
-- Tudo aditivo: tres tabelas novas. Nenhuma tabela do Concurso e' tocada e
-- nenhum ENUM existente e' alterado (Divulgacao nao e' secao da pagina
-- publica, entao evento_secoes_ordem fica como esta'). Nada de configuracao
-- e' semeado aqui: toda linha nasce no primeiro salvamento da tela
-- administrativa, e evento sem linha equivale a modulo desligado.

-- ---------------------------------------------------------------------
-- evento_divulgacao_config: o modulo no evento, no molde de
-- evento_conexoes_config (Fase 55).
--
-- data_inicio / data_fim: janela propria das comprovacoes, porque
-- divulgacao acontece tambem na vespera (chamada para o evento) e depois do
-- encerramento (registro do que foi publicado). As duas em branco significam
-- "use as datas do evento", que e' o comportamento de Conexoes.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evento_divulgacao_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 0,
    data_inicio DATE NULL,
    data_fim DATE NULL,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_divulgacao_config_evento (evento_id),
    CONSTRAINT fk_evento_divulgacao_config_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- evento_divulgacao_redes: uma linha por rede que vale no evento.
--
-- Valor POR REDE, e nao por tipo de acao, porque o documento ja' trata as
-- redes de forma diferente (5 pontos por publicacao no Instagram, 10 no
-- LinkedIn). O mesmo vale para o teto, que por isso mora nesta linha.
--
-- publicacao_teto_dia / publicacao_teto_evento: os dois, e nao um, porque o
-- dono decidiu deixar a escolha com quem organiza - zero nos dois significa
-- sem limite, so' o diario limita por dia, so' o do evento limita no total,
-- os dois preenchidos valem juntos. "Acompanhar" nao tem teto porque ja' e'
-- limitado a uma vez por rede.
--
-- *_prova: o que aquela rede aceita como comprovacao. O documento pede
-- imagem da tela no Instagram e endereco da publicacao no LinkedIn, mas quem
-- organiza muda isso na tela, sem codigo novo.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evento_divulgacao_redes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    rede VARCHAR(20) NOT NULL,
    publicacao_ativa TINYINT(1) NOT NULL DEFAULT 0,
    publicacao_pontos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    publicacao_prova ENUM('imagem','endereco','ambos') NOT NULL DEFAULT 'ambos',
    publicacao_teto_dia SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    publicacao_teto_evento SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    acompanhar_ativa TINYINT(1) NOT NULL DEFAULT 0,
    acompanhar_pontos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    acompanhar_prova ENUM('imagem','endereco','ambos') NOT NULL DEFAULT 'imagem',
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_divulgacao_redes (evento_id, rede),
    CONSTRAINT fk_evento_divulgacao_redes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- evento_divulgacao_comprovacoes: o fato.
--
-- endereco_hash: resumo do endereco NORMALIZADO (esquema forcado para
-- https, dominio em minusculas sem www., barra final removida, parametros
-- de rastreamento conhecidos removidos, o resto ordenado). Sem isso, a mesma
-- publicacao reenviada com uma barra a mais ou com ?utm_source colado pelo
-- botao de compartilhar produziria resumo diferente e furaria a trava.
--
-- arquivo_sha256: resumo da imagem ja' reduzida, calculado ANTES de o
-- arquivo ir para a area privada, para que prova repetida nem chegue a criar
-- arquivo em disco. Sobrevive ao expurgo, entao a trava continua de pe'
-- depois de as imagens serem apagadas.
--
-- As duas chaves unicas tem escopo DIFERENTE, de proposito:
--   (evento_id, endereco_hash) - um endereco identifica uma publicacao
--   unica, e duas pessoas nao publicaram a mesma coisa, entao a trava vale
--   para o evento inteiro.
--   (evento_inscricao_id, arquivo_sha256) - duas pessoas enviando o mesmo
--   arquivo e' caso legitimo e frequente (o cartaz oficial da campanha,
--   baixado por ambas, e' identico byte a byte). Recusar ali seria falso
--   positivo E esconderia do Administrador justamente o caso que interessa a
--   ele. Entre pessoas diferentes, a coincidencia vira marca na tela
--   administrativa, para julgamento humano.
-- Coluna nula nao colide em chave unica no MySQL, entao comprovacao so' com
-- imagem e comprovacao so' com endereco convivem sem atrapalhar uma a outra.
--
-- anulado_em / anulado_por / motivo_anulacao: a anulacao nao apaga a linha.
-- Ela tira o registro de TODA soma e de TODO contador de teto (a consulta
-- filtra anulado_em IS NULL), o que devolve a vaga do teto e a possibilidade
-- de registrar "acompanhar" de novo: anulacao corrige, nao pune. O que nao
-- volta e' a prova - o resumo continua barrado, entao reenviar a mesma
-- imagem ou o mesmo endereco nunca devolve os pontos.
--
-- arquivo_removido_em: marca do expurgo pela tela administrativa. A linha,
-- os pontos e os resumos permanecem; so' o arquivo some.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evento_divulgacao_comprovacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    evento_inscricao_id INT UNSIGNED NOT NULL,
    rede VARCHAR(20) NOT NULL,
    tipo_acao ENUM('publicacao','acompanhar') NOT NULL,
    endereco VARCHAR(500) NULL,
    endereco_hash CHAR(64) NULL,
    arquivo_path VARCHAR(255) NULL,
    arquivo_nome VARCHAR(255) NULL,
    arquivo_sha256 CHAR(64) NULL,
    arquivo_bytes INT UNSIGNED NULL,
    arquivo_removido_em DATETIME NULL,
    pontos_creditados SMALLINT UNSIGNED NOT NULL,
    enviado_em DATETIME NOT NULL,
    anulado_em DATETIME NULL,
    anulado_por INT UNSIGNED NULL,
    motivo_anulacao VARCHAR(500) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_divulgacao_comprovacoes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_divulgacao_comprovacoes_inscricao FOREIGN KEY (evento_inscricao_id) REFERENCES evento_inscricoes (id),
    CONSTRAINT fk_evento_divulgacao_comprovacoes_anulado_por FOREIGN KEY (anulado_por) REFERENCES usuarios (id),
    UNIQUE KEY uq_evento_divulgacao_endereco (evento_id, endereco_hash),
    UNIQUE KEY uq_evento_divulgacao_arquivo_pessoa (evento_inscricao_id, arquivo_sha256),
    INDEX idx_evento_divulgacao_evento (evento_id),
    INDEX idx_evento_divulgacao_inscricao (evento_inscricao_id),
    INDEX idx_evento_divulgacao_arquivo_evento (evento_id, arquivo_sha256),
    INDEX idx_evento_divulgacao_teto (evento_inscricao_id, rede, tipo_acao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
