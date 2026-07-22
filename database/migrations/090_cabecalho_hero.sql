-- Fase 19 (#84 v3): titulo/slogan do hero do cabecalho, editor rico -
-- decisao confirmada com o usuario: um unico campo (sem uploads
-- dedicados de ilustracao), admin insere texto e imagens livremente ali
-- dentro usando o proprio editor rico.
ALTER TABLE configuracoes_visuais
    ADD COLUMN cabecalho_titulo_html TEXT NULL;
