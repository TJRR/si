-- Fase 39 (Semana de Inovacao, Bloco A): Evento e' entidade nova, sem FK
-- para concursos - usuarios/Auth ja nao dependem de concurso, entao a
-- inscricao de qualquer conta existente (equipe, avaliador ou visitante)
-- so' precisa de uma FK simples para usuarios.id, sem duplicar cadastro/
-- senha. UNIQUE (evento_id, usuario_id) impede inscricao duplicada da
-- mesma conta no mesmo evento.
--
-- Fase 49B (correcao na fonte, nada disto chegou a producao): a versao
-- original desta migration criava aqui `documento`/`tipo_documento`/
-- `cargo`/`categoria_profissional`/`tribunal_orgao_origem`/
-- `categoria_inscricao` como colunas proprias de evento_inscricoes -
-- duplicando dado que e' da PESSOA (usuarios_perfil, ver migration 144),
-- nao do vinculo dela com um evento especifico. `tipo_documento`/`cargo`/
-- `categoria_profissional`/`tribunal_orgao_origem`/`categoria_inscricao`
-- ja tinham sido corrigidos na migration 121 (viraram campo configuravel
-- em respostas_json, ou foram removidos de vez); `documento` continuava
-- fixo ali por engano. Corrigido agora, retroativo: evento_inscricoes
-- nunca guarda documento de identificacao - EventoInscricaoRepository::
-- inscrever() grava direto em usuarios_perfil (EventoInscricaoPublicaController
-- decide o rotulo/tipo escolhido no campo configuravel "Tipo de documento").
CREATE TABLE IF NOT EXISTS eventos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    status ENUM('ativo', 'encerrado') NOT NULL DEFAULT 'ativo',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS evento_inscricoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    inscrito_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_inscricoes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_inscricoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    UNIQUE KEY uq_evento_inscricoes (evento_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Semeia o evento real desta fase (04-06/11/2026) pra ja' existir algo pra
-- se inscrever sem depender de painel administrativo, que fica pra fase
-- futura do Bloco C (grade/atividades).
INSERT IGNORE INTO eventos (id, nome, descricao, data_inicio, data_fim, status)
VALUES (
    1,
    '5ª Semana de Inovação',
    'Semana de Inovação do TJRR: credenciamento, atividades, networking e submissão de artigo científico.',
    '2026-11-04',
    '2026-11-06',
    'ativo'
);
