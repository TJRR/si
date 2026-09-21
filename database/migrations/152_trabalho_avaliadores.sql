-- Fase 49: avaliador avulso de Trabalhos, isolado do perfil global
-- "avaliador" do Concurso de proposito - convidar alguem so para avaliar
-- Trabalhos de um Evento nunca pode dar acesso a avaliacao/* (Premio de
-- Inovacao em andamento). Autorizacao real e' conferida direto contra
-- trabalho_avaliadores/trabalho_designacoes, nunca via Auth::possuiPerfil().
-- Preservacao historica igual evento_atividade_facilitadores (Fase 48):
-- remover e' sempre UPDATE removido_em, nunca DELETE fisico.
CREATE TABLE IF NOT EXISTS trabalho_avaliadores (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    convidado_por INT UNSIGNED NULL,
    convidado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    removido_em DATETIME NULL,
    CONSTRAINT fk_trabalho_avaliadores_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_trabalho_avaliadores_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_trabalho_avaliadores_convidado_por FOREIGN KEY (convidado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Designacao de um avaliador avulso a um trabalho especifico (quem de
-- fato avalia aquele trabalho, dentre o grupo de trabalho_avaliadores
-- daquele evento).
CREATE TABLE IF NOT EXISTS trabalho_designacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trabalho_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    designado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    designado_por INT UNSIGNED NULL,
    CONSTRAINT fk_trabalho_designacoes_trabalho FOREIGN KEY (trabalho_id) REFERENCES trabalhos (id),
    CONSTRAINT fk_trabalho_designacoes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_trabalho_designacoes_designado_por FOREIGN KEY (designado_por) REFERENCES usuarios (id),
    UNIQUE KEY uq_trabalho_designacoes (trabalho_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nota lancada por um avaliador, por criterio, para o trabalho que lhe foi
-- designado. UPSERT (ON DUPLICATE KEY UPDATE) na camada de repositorio,
-- mesmo padrao de NotaLancadaRepository::salvar() do Concurso.
CREATE TABLE IF NOT EXISTS trabalho_notas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    designacao_id INT UNSIGNED NOT NULL,
    criterio_id INT UNSIGNED NOT NULL,
    nota DECIMAL(5,2) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_trabalho_notas_designacao FOREIGN KEY (designacao_id) REFERENCES trabalho_designacoes (id),
    CONSTRAINT fk_trabalho_notas_criterio FOREIGN KEY (criterio_id) REFERENCES trabalho_criterios (id),
    UNIQUE KEY uq_trabalho_notas (designacao_id, criterio_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
