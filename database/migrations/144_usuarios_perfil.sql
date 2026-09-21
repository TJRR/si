-- Fase 48 (correcao pos-teste de fumaca): documento/cargo/categoria
-- profissional/orgao de origem/minicurriculo sao atributos da PESSOA, nao
-- da designacao de facilitador numa atividade especifica - moram numa
-- tabela ligada a usuarios (1:1), reaproveitavel por qualquer contexto
-- futuro (inclusive a fase, ainda sem numero definido, de Cadastro do
-- Perfil do Usuario). Antes de esta migration, esses campos estavam
-- duplicados em evento_atividade_facilitadores, errado: a mesma pessoa
-- teria que redigitar os mesmos dados a cada nova atividade em que fosse
-- facilitadora. Foto continua em usuarios.foto_path (ja existe desde a
-- Fase 33, reaproveitado, nunca duplicado aqui).
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

ALTER TABLE evento_atividade_facilitadores
    DROP COLUMN documento,
    DROP COLUMN tipo_documento,
    DROP COLUMN cargo,
    DROP COLUMN categoria,
    DROP COLUMN orgao_origem,
    DROP COLUMN minicurriculo,
    DROP COLUMN foto_path;
