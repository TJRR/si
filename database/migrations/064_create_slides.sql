-- Fase 18 (3.2 Slideshow): slides do slideshow principal da home publica,
-- escopados por concurso. Efeitos de hover/animacao sao enums fechados
-- (mesma lista da especificacao-home.md), sem Alpine.js - aplicados via
-- CSS/JS vanilla no frontend.
CREATE TABLE IF NOT EXISTS slides (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NOT NULL,
    imagem_desktop_path VARCHAR(255) NOT NULL,
    imagem_mobile_path VARCHAR(255) NULL,
    imagem_alt VARCHAR(255) NOT NULL,
    titulo_html TEXT NULL,
    separador_cor VARCHAR(7) NULL,
    cta_titulo VARCHAR(150) NULL,
    cta_link VARCHAR(255) NULL,
    cta_target ENUM('_self', '_blank') NOT NULL DEFAULT '_self',
    cta_cor_fundo VARCHAR(7) NULL,
    cta_cor_texto VARCHAR(7) NULL,
    cta_tamanho ENUM('pequeno', 'medio', 'grande') NOT NULL DEFAULT 'medio',
    cta_efeito_hover ENUM('nenhum', 'escurecer', 'clarear', 'escala', 'borda', 'iluminar', 'inverter') NOT NULL DEFAULT 'nenhum',
    cta_animacao_entrada VARCHAR(50) NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_slides_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
