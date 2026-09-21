-- Fase 49: motor de submissao/avaliacao de Trabalhos (artigos/resumos
-- expandidos) do Evento, sem nenhuma dependencia de concursos/etapas
-- (decisao de arquitetura numero 5 do plano mestre da Semana de Inovacao).
-- Toda regra de negocio especifica de uma edicao vira configuracao aqui,
-- nunca constante fixa no codigo: quantidade de autores, deduplicacao de
-- pessoa, metodo de agregacao de nota, obrigatoriedade de telefone,
-- metodos/extensoes de submissao aceitos, regra de selecao dos aprovados.
-- 1:1 com eventos (cada evento tem, no maximo, uma configuracao de
-- Trabalhos).
CREATE TABLE IF NOT EXISTS evento_trabalhos_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    data_abertura_submissao DATETIME NULL,
    data_fim_submissao DATETIME NULL,
    data_inicio_avaliacao DATETIME NULL,
    data_fim_avaliacao DATETIME NULL,
    quantidade_maxima_autores TINYINT UNSIGNED NOT NULL DEFAULT 2,
    permite_multiplos_trabalhos_por_pessoa TINYINT(1) NOT NULL DEFAULT 0,
    quantidade_avaliadores_por_trabalho TINYINT UNSIGNED NOT NULL DEFAULT 2,
    sigilo_cego TINYINT(1) NOT NULL DEFAULT 1,
    metodo_agregacao_nota ENUM('media_aritmetica', 'mediana') NOT NULL DEFAULT 'media_aritmetica',
    metodos_submissao_json JSON NOT NULL,
    extensoes_editavel_json JSON NULL,
    tamanho_maximo_mb INT UNSIGNED NOT NULL DEFAULT 15,
    exige_telefone_contato TINYINT(1) NOT NULL DEFAULT 1,
    nota_corte_aprovacao DECIMAL(5,2) NULL,
    regra_selecao_tipo ENUM('numero_fixo', 'percentual', 'todos_aprovados') NOT NULL DEFAULT 'todos_aprovados',
    regra_selecao_valor DECIMAL(8,2) NULL,
    status ENUM('rascunho', 'publicado', 'encerrado') NOT NULL DEFAULT 'rascunho',
    -- Fase 49B (achado do usuario): resumo dos criterios de avaliacao
    -- (nome, faixa de nota, como interpretar cada um), editavel pela
    -- organizacao via WYSIWYG na propria aba de Configuracoes - consultado
    -- pelo avaliador num painel dentro da tela de avaliacao, SEM expor o
    -- edital inteiro. Texto solto (nao extraido automaticamente do PDF do
    -- edital, decisao explicita do usuario); vazio ate o Admin preencher,
    -- a tela de avaliacao usa um resumo montado a partir de
    -- trabalho_criterios como reserva enquanto isso.
    criterios_resumo_html MEDIUMTEXT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_trabalhos_config_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    UNIQUE KEY uq_evento_trabalhos_config_evento (evento_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
