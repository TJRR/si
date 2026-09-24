-- Fase 50 (correcao de arquitetura): cabecalho da pagina publica propria de
-- cada Evento. Tabela satelite 1 linha por evento (mesmo padrao de upsert de
-- blocos_concurso/evento_trabalhos_config), com os mesmos 7 campos de
-- cabecalho que configuracoes_visuais ja tem para o Concurso (migrations
-- 086/090/092/093/094). Rodape/Tema continuam globais e compartilhados -
-- nao entram aqui.
-- publicado (novo, sem equivalente no Concurso): a pagina publica do evento
-- (evento/index/{id}) so responde enquanto isto estiver em 1 - nasce em 0
-- para o Admin poder configurar Cabecalho/Slideshow/Faixas/Blocos antes de
-- expor o link publicamente.
--
-- Fase 51 (colunas acrescentadas na origem, premissa 8 de Premissas.md):
-- logo_path/logo_alt sao a logo oficial daquele evento, que vence a logo do
-- tema de cor dentro da pagina publica dele (identidade visual aprovada nao
-- pode variar conforme o tema escolhido por quem visita); sem logo
-- cadastrada, a do tema continua valendo. fonte_titulo/fonte_texto sao as
-- fontes da pagina publica daquele evento (lista fechada, ver
-- EventoConfiguracaoVisualRepository::FONTES); vazias, vale a fonte do site.
-- O carrossel do Evento segue exatamente o metodo da home do Concurso
-- (tempo e efeito por quadro, sempre avancando): nao ha opcao propria aqui.
CREATE TABLE IF NOT EXISTS evento_configuracao_visual (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    publicado TINYINT(1) NOT NULL DEFAULT 0,
    cabecalho_imagem_path VARCHAR(255) NULL,
    cabecalho_logo_claro_path VARCHAR(255) NULL,
    cabecalho_titulo_html TEXT NULL,
    cabecalho_efeito_transicao ENUM('onda','diagonal_esquerda','diagonal_direita') NOT NULL DEFAULT 'onda',
    cabecalho_overlay_opacidade TINYINT UNSIGNED NOT NULL DEFAULT 50,
    cabecalho_imagem_posicao ENUM(
        'superior_esquerda','superior_centro','superior_direita',
        'centro_esquerda','centro_centro','centro_direita',
        'inferior_esquerda','inferior_centro','inferior_direita'
    ) NOT NULL DEFAULT 'superior_centro',
    cabecalho_efeito_entrada ENUM('nenhum','fade','subir','zoom') NOT NULL DEFAULT 'nenhum',
    logo_path VARCHAR(255) NULL,
    logo_alt VARCHAR(255) NULL,
    fonte_titulo VARCHAR(40) NULL,
    fonte_texto VARCHAR(40) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_configuracao_visual_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    UNIQUE KEY uq_evento_configuracao_visual_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
