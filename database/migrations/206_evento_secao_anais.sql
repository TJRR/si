-- Fase 60 (pendencias 2 e 8): componente "Anais" da pagina publica do
-- Evento, no molde de evento_secao_estandes (179). Mostra o volume publicado
-- e, com mostrar_selecionados ligado, a relacao publica dos trabalhos
-- selecionados, que so' aparece com o resultado de Trabalhos publicado.
CREATE TABLE IF NOT EXISTS evento_secao_anais (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    etiqueta VARCHAR(60) NULL,
    titulo VARCHAR(150) NULL,
    descricao_html TEXT NULL,
    mostrar_selecionados TINYINT(1) NOT NULL DEFAULT 1,
    cor_fundo VARCHAR(7) NULL,
    cor_texto VARCHAR(7) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_secao_anais_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_secao_anais_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
