-- "Congelamento" do resultado de uma etapa depois que o Admin confirma e publica:
-- o ranking nao muda mais sozinho se uma nota for corrigida depois, e novas notas
-- ficam bloqueadas para aquela submissao/etapa ate uma reabertura explicita.
CREATE TABLE IF NOT EXISTS resultados_etapa (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    submissao_id INT UNSIGNED NOT NULL,
    etapa_id INT UNSIGNED NOT NULL,
    ne DECIMAL(6,2) NOT NULL,
    classificado TINYINT(1) NOT NULL DEFAULT 0,
    publicado_por INT UNSIGNED NULL,
    publicado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_resultados_etapa_submissao FOREIGN KEY (submissao_id) REFERENCES submissoes (id),
    CONSTRAINT fk_resultados_etapa_etapa FOREIGN KEY (etapa_id) REFERENCES etapas (id),
    CONSTRAINT fk_resultados_etapa_publicado_por FOREIGN KEY (publicado_por) REFERENCES usuarios (id),
    UNIQUE KEY uq_resultados_etapa (submissao_id, etapa_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
