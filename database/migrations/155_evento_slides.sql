-- Fase 50 (correcao de arquitetura): slideshow proprio de cada Evento,
-- tabela dedicada (evento_id NOT NULL, nunca compartilhada com o Concurso -
-- ver decisao de isolamento no plano da fase). Mesmas colunas de `slides`
-- apos as migrations 064/078/085/087, com evento_id no lugar de
-- concurso_id.
--
-- Fase 51 (colunas acrescentadas na origem, premissa 8 de Premissas.md):
-- etiqueta_* e' o selo colorido acima do titulo do quadro (ex.: "PRAZO ATE
-- 16/10"), que o Concurso nao tem; cta2_* e' o segundo botao, porque a
-- identidade visual aprovada pede um par (acao principal mais acao
-- secundaria) no mesmo quadro. O avanco automatico do carrossel e' por
-- evento e mora em evento_configuracao_visual.
CREATE TABLE IF NOT EXISTS evento_slides (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    imagem_desktop_path VARCHAR(255) NULL,
    imagem_mobile_path VARCHAR(255) NULL,
    imagem_alt VARCHAR(255) NULL,
    cor_fundo CHAR(7) NULL,
    duracao_ms INT UNSIGNED NOT NULL DEFAULT 7000,
    efeito_transicao ENUM('fade','slide','zoom') NOT NULL DEFAULT 'fade',
    overlay_efeito ENUM('nenhum','escurecer','vinheta','pontos','linhas','halftone','trama') NOT NULL DEFAULT 'nenhum',
    overlay_cor CHAR(7) NULL,
    overlay_opacidade TINYINT UNSIGNED NOT NULL DEFAULT 40,
    etiqueta_texto VARCHAR(60) NULL,
    etiqueta_cor_fundo VARCHAR(7) NULL,
    etiqueta_cor_texto VARCHAR(7) NULL,
    titulo_html TEXT NULL,
    separador_cor VARCHAR(7) NULL,
    cta_titulo VARCHAR(150) NULL,
    cta_link VARCHAR(255) NULL,
    cta_target ENUM('_self','_blank') NOT NULL DEFAULT '_self',
    cta_cor_fundo VARCHAR(7) NULL,
    cta_cor_texto VARCHAR(7) NULL,
    cta_tamanho ENUM('pequeno','medio','grande') NOT NULL DEFAULT 'medio',
    cta_efeito_hover ENUM('nenhum','escurecer','clarear','escala','borda','iluminar','inverter') NOT NULL DEFAULT 'nenhum',
    cta_animacao_entrada VARCHAR(50) NULL,
    cta2_titulo VARCHAR(150) NULL,
    cta2_link VARCHAR(255) NULL,
    cta2_target ENUM('_self','_blank') NOT NULL DEFAULT '_self',
    cta2_cor_fundo VARCHAR(7) NULL,
    cta2_cor_texto VARCHAR(7) NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_slides_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_slides_evento_ordem (evento_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
