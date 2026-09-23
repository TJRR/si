-- Fase 48: modalidade de atividade (presencial/online/hibrido), segundo
-- codigo de confirmacao de presenca (para quem participa online), Perfis da
-- equipe de organizacao (cadastrados pelo Admin por evento, nunca lista fixa
-- no codigo) e cadastro de Facilitador (instrutor/professor/palestrante) por
-- atividade, exigido pelo layout da exportacao EJURR. modalidade_acesso
-- registra, por check-in, qual dos dois caminhos foi de fato usado -
-- necessario porque atividade hibrida aceita os dois ao mesmo tempo.
--
-- Correcao de arquitetura (Fase 50): esta migration reune o que antes eram
-- 3 migrations em sequencia (esta, mais as que existiam com os numeros 143 e
-- 144) - a primeira versao criava evento_atividade_facilitadores com um
-- campo "perfil" fixo e 7 colunas de dados de pessoa direto na tabela de
-- designacao, e as duas seguintes desfaziam isso pouco depois (perfil vira
-- tabela propria, dados de pessoa vao para usuarios_perfil). Como nada disso
-- chegou a producao, a tabela nasce aqui direto no formato final - nunca
-- existiu, em nenhum ambiente novo, no formato intermediario errado.
ALTER TABLE evento_atividades
    ADD COLUMN modalidade ENUM('presencial','online','hibrido') NOT NULL DEFAULT 'presencial' AFTER local,
    ADD COLUMN codigo_presenca_online CHAR(5) NULL UNIQUE AFTER codigo_atividade;

ALTER TABLE evento_checkins
    ADD COLUMN modalidade_acesso ENUM('presencial','online') NOT NULL DEFAULT 'presencial' AFTER checkin_em;

-- "Perfil" do Facilitador e cadastrado pelo Admin, por evento - mesmo
-- espirito de evento_campos_inscricao, nunca semear dado de negocio direto
-- no banco.
CREATE TABLE IF NOT EXISTS evento_perfis_organizacao (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_perfis_organizacao_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Documento/cargo/categoria profissional/orgao de origem/minicurriculo sao
-- atributos da PESSOA, nao da designacao de facilitador numa atividade
-- especifica - moram numa tabela ligada a usuarios (1:1), reaproveitavel por
-- qualquer contexto futuro (inclusive a fase, ainda sem numero definido, de
-- Cadastro do Perfil do Usuario). Foto continua em usuarios.foto_path (ja
-- existe desde a Fase 33, reaproveitado, nunca duplicado aqui).
CREATE TABLE IF NOT EXISTS usuarios_perfil (
    usuario_id INT UNSIGNED PRIMARY KEY,
    documento VARCHAR(30) NULL,
    tipo_documento VARCHAR(20) NULL DEFAULT 'CPF',
    cargo VARCHAR(150) NULL,
    categoria_profissional VARCHAR(150) NULL,
    orgao_origem VARCHAR(150) NULL,
    minicurriculo TEXT NULL,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_usuarios_perfil_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- removido_em: designacao de facilitador alimenta a exportacao EJURR (que
-- pode ser gerada em qualquer momento, inclusive apos a atividade
-- acontecer), entao "remover" nunca e' um DELETE fisico - mesmo espirito de
-- preservacao historica ja usado em evento_checkins (Fase 47). Dados de
-- pessoa (documento/cargo/...) vem de usuarios_perfil, nunca duplicados
-- aqui.
CREATE TABLE IF NOT EXISTS evento_atividade_facilitadores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    atividade_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    perfil_id INT UNSIGNED NOT NULL,
    designado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    removido_em DATETIME NULL,
    CONSTRAINT fk_evento_atividade_facilitadores_atividade FOREIGN KEY (atividade_id) REFERENCES evento_atividades (id),
    CONSTRAINT fk_evento_atividade_facilitadores_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_evento_atividade_facilitadores_perfil FOREIGN KEY (perfil_id) REFERENCES evento_perfis_organizacao (id),
    UNIQUE KEY uq_evento_atividade_facilitadores (atividade_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
