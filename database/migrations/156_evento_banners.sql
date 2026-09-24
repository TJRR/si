-- Fase 50 (correcao de arquitetura): faixas (Banner/Hero) proprias de cada
-- Evento, tabela dedicada (evento_id NOT NULL). Mesmas colunas de `banners`
-- apos a migration 078, com evento_id no lugar de concurso_id.
--
-- Reabertura da Fase 51 (colunas acrescentadas na origem, premissa 8 de
-- Premissas.md): cor_texto, porque o texto da faixa era sempre branco e
-- sumia sobre fundo claro; formato 'bandeirinha' desenha o conteudo como a
-- fita da identidade visual (retangulo com ponta triangular a direita, na
-- cor de fundo cadastrada), sobre fundo branco.
CREATE TABLE IF NOT EXISTS evento_banners (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    imagem_desktop_path VARCHAR(255) NULL,
    imagem_mobile_path VARCHAR(255) NULL,
    imagem_alt VARCHAR(255) NULL,
    cor_fundo VARCHAR(7) NULL,
    conteudo_html TEXT NULL,
    conteudo_alinhamento ENUM('esquerda','centro','direita') NOT NULL DEFAULT 'centro',
    cor_texto VARCHAR(7) NULL,
    formato ENUM('retangulo','bandeirinha') NOT NULL DEFAULT 'retangulo',
    cta_titulo VARCHAR(150) NULL,
    cta_destino_tipo ENUM('link_interno','externo','ancora','arquivo','video') NULL,
    cta_destino_valor VARCHAR(255) NULL,
    cta_posicao ENUM(
        'superior_esquerda','superior_centro','superior_direita',
        'centro_esquerda','centro_centro','centro_direita',
        'inferior_esquerda','inferior_centro','inferior_direita'
    ) NOT NULL DEFAULT 'centro_centro',
    cta_efeito_hover ENUM('nenhum','escurecer','clarear','escala','borda','iluminar','inverter') NOT NULL DEFAULT 'nenhum',
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_banners_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_banners_evento_ordem (evento_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
