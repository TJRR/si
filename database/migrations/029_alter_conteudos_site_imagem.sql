ALTER TABLE conteudos_site
    MODIFY COLUMN tipo ENUM('texto_curto', 'texto_longo', 'imagem') NOT NULL DEFAULT 'texto_curto',
    ADD COLUMN arquivo_path VARCHAR(255) NULL AFTER valor;

INSERT IGNORE INTO conteudos_site (chave, rotulo, valor, tipo) VALUES
    ('logo_site', 'Logo do site', NULL, 'imagem'),
    ('hero_imagem_fundo', 'Imagem de fundo do topo (hero)', NULL, 'imagem'),
    ('sobre_imagem', 'Imagem da secao "Sobre o Premio"', NULL, 'imagem'),
    ('premiacao_imagem', 'Imagem da secao "Premiacao"', NULL, 'imagem');

UPDATE conteudos_site SET valor = 'Prêmio de Inovação TJRR' WHERE chave = 'hero_titulo';
