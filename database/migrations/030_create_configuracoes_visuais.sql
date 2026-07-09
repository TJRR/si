CREATE TABLE IF NOT EXISTS configuracoes_visuais (
    id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
    cor_primaria_inicio VARCHAR(7) NOT NULL DEFAULT '#FF6600',
    cor_primaria_fim VARCHAR(7) NOT NULL DEFAULT '#FF9955',
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO configuracoes_visuais (id, cor_primaria_inicio, cor_primaria_fim)
VALUES (1, '#FF6600', '#FF9955');
