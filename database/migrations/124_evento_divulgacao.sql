-- Fase 40 (14/09/2026): bloco de divulgacao do Evento na home publica, sub-aba
-- "Divulgacao na home" do no do Evento (EventoAdminController::divulgacao()).
-- Tabela satelite propria (nao reaproveita blocos_conteudo) para nao acoplar
-- ao mecanismo de ordenacao por arraste (home_secoes_ordem) nem a listagem de
-- "Configuracoes > Blocos de conteudo" - a posicao deste bloco e' sempre fixa
-- (entre Slideshow e Banners), nunca reordenavel, e pertence ao Evento, nao a
-- Configuracoes. 1 registro por evento (UNIQUE). Sem cta_link: o botao e'
-- sempre fixo para a inscricao do proprio evento (ver
-- app/Views/home/_bloco_evento.php).
CREATE TABLE IF NOT EXISTS evento_divulgacao (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(150) NULL,
    conteudo_html MEDIUMTEXT NULL,
    imagem_path VARCHAR(255) NULL,
    imagem_alt VARCHAR(255) NULL,
    imagem_posicao ENUM('esquerda', 'direita') NOT NULL DEFAULT 'esquerda',
    cta_titulo VARCHAR(100) NULL,
    cta_alinhamento ENUM('esquerda', 'centro', 'direita') NOT NULL DEFAULT 'esquerda',
    ativo TINYINT(1) NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_divulgacao_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    UNIQUE KEY uq_evento_divulgacao_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
