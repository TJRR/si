-- Fase 18 (3.3 Banner/Hero): banners empilhaveis abaixo do slideshow,
-- escopados por concurso. cta_posicao usa a grade de 9 pontos da
-- especificacao-home.md (3 linhas x 3 colunas).
CREATE TABLE IF NOT EXISTS banners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    concurso_id INT UNSIGNED NOT NULL,
    imagem_desktop_path VARCHAR(255) NULL,
    imagem_mobile_path VARCHAR(255) NULL,
    imagem_alt VARCHAR(255) NULL,
    cor_fundo VARCHAR(7) NULL,
    conteudo_html TEXT NULL,
    cta_titulo VARCHAR(150) NULL,
    cta_destino_tipo ENUM('link_interno', 'externo', 'ancora', 'arquivo', 'video') NULL,
    cta_destino_valor VARCHAR(255) NULL,
    cta_posicao ENUM(
        'superior_esquerda', 'superior_centro', 'superior_direita',
        'centro_esquerda', 'centro_centro', 'centro_direita',
        'inferior_esquerda', 'inferior_centro', 'inferior_direita'
    ) NOT NULL DEFAULT 'centro_centro',
    cta_efeito_hover ENUM('nenhum', 'escurecer', 'clarear', 'escala', 'borda', 'iluminar', 'inverter') NOT NULL DEFAULT 'nenhum',
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_banners_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
