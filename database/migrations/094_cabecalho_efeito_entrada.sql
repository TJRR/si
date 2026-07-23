-- Fase 20 (#114): efeito de entrada configuravel do titulo/conteudo do
-- cabecalho com imagem de fundo (fade/subir/zoom), mesmo padrao do
-- cabecalho_efeito_transicao (Fase 19). 'nenhum' e' o padrao - zero
-- mudanca visual pra quem nao mexer.
ALTER TABLE configuracoes_visuais
    ADD COLUMN cabecalho_efeito_entrada ENUM('nenhum','fade','subir','zoom') NOT NULL DEFAULT 'nenhum';
