-- Fase 60 (pendencia 3): trabalhos cujos autores ja receberam o aviso de
-- publicacao dos Anais, para o aviso de uma versao seguinte alcancar so' os
-- autores de trabalhos novos. Publicacao anterior a esta fase nao tem linha.
CREATE TABLE IF NOT EXISTS evento_anais_trabalhos_avisados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    trabalho_id INT UNSIGNED NOT NULL,
    versao_numero INT UNSIGNED NOT NULL,
    avisado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_anais_trabalhos_avisados (evento_id, trabalho_id),
    CONSTRAINT fk_evento_anais_trabalhos_avisados_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_anais_trabalhos_avisados_trabalho FOREIGN KEY (trabalho_id) REFERENCES trabalhos (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
