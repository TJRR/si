-- Fase 50 (correcao de arquitetura): blocos de conteudo proprios de cada
-- Evento, tabela dedicada (evento_id NOT NULL). Mesmas colunas de
-- `blocos_conteudo` apos as migrations 080/088, com evento_id no lugar de
-- concurso_id, SEM `chave` (Evento nao tem blocos padrao fixos tipo
-- "Sobre"/"Premiacao" - todo bloco de Evento e livre) e SEM
-- `mostrar_no_rodape` (rodape e unico/compartilhado entre Concurso e
-- Evento, decisao confirmada no plano da fase - nao ha secao propria do
-- Evento no rodape para mostrar/esconder).
--
-- Fase 51 (ajustes na origem, premissa 8 de Premissas.md): cor_fundo e
-- cor_texto por bloco (a identidade visual aprovada alterna faixas de cor
-- entre as secoes, e o Concurso resolve isso so' com a cor do tema);
-- cta2_titulo/cta2_link para o par de botoes no mesmo bloco; e
-- mostrar_no_menu saiu daqui, porque o menu do cabecalho passou a ser
-- montado a partir de evento_secoes_ordem (migration 158), onde TODAS as
-- secoes da pagina, nao so' os blocos, decidem se entram no menu.
CREATE TABLE IF NOT EXISTS evento_blocos_conteudo (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    conteudo_html TEXT NULL,
    imagem_path VARCHAR(255) NULL,
    imagem_alt VARCHAR(255) NULL,
    imagem_posicao ENUM('esquerda','direita') NOT NULL DEFAULT 'esquerda',
    cor_fundo VARCHAR(7) NULL,
    cor_texto VARCHAR(7) NULL,
    cta_titulo VARCHAR(150) NULL,
    cta_link VARCHAR(255) NULL,
    cta_alinhamento ENUM('esquerda','centro','direita') NOT NULL DEFAULT 'esquerda',
    cta2_titulo VARCHAR(150) NULL,
    cta2_link VARCHAR(255) NULL,
    secao_ancora VARCHAR(60) NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_blocos_conteudo_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_blocos_conteudo_evento_ordem (evento_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
