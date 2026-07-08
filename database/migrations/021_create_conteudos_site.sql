CREATE TABLE IF NOT EXISTS conteudos_site (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(80) NOT NULL,
    rotulo VARCHAR(150) NOT NULL,
    valor TEXT NULL,
    tipo ENUM('texto_curto', 'texto_longo') NOT NULL DEFAULT 'texto_curto',
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_conteudos_site_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO conteudos_site (chave, rotulo, valor, tipo) VALUES
    ('hero_titulo', 'Título principal (hero)', 'Prêmio de Inovação TJRR', 'texto_curto'),
    ('hero_subtitulo', 'Subtítulo/chamada (hero)', 'Faça parte da transformação!', 'texto_curto'),
    ('sobre_texto', 'Texto "Sobre o Prêmio / Semana de Inovação"', 'O Prêmio de Inovação do TJRR reconhece soluções tecnológicas e protótipos que contribuam para a modernização da Justiça em Roraima.', 'texto_longo'),
    ('premiacao_texto', 'Texto de premiação', 'Informações sobre premiação serão divulgadas em breve.', 'texto_longo'),
    ('contato_email', 'E-mail de contato', 'npi@tjrr.jus.br', 'texto_curto'),
    ('contato_telefone', 'Telefone de contato', '', 'texto_curto'),
    ('contato_endereco', 'Endereço de contato', '', 'texto_curto');
