-- Fase 19 (#84): imagem de fundo propria do cabecalho publico (independente
-- do Slideshow) + logo clara opcional pra usar sobre essa imagem, ambas por
-- concurso (mesmo padrao de logo_path). Sem imagem cadastrada, o cabecalho
-- continua identico ao comportamento atual.
ALTER TABLE configuracoes_visuais
    ADD COLUMN cabecalho_imagem_path VARCHAR(255) NULL AFTER logo_path,
    ADD COLUMN cabecalho_logo_claro_path VARCHAR(255) NULL AFTER cabecalho_imagem_path;
