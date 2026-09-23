-- Fase 48B: gestao de multiplos temas DE COR, valendo para o sistema
-- inteiro (site publico, painel administrativo e app de Evento), em vez da
-- configuracao unica global que existia em configuracoes_visuais. Tabela
-- chamada "temas_visuais" (nao "temas"), de proposito: "temas" ja existe no
-- dominio do Premio de Inovacao desde a Fase 17 (hierarquia Trilha->Tema->
-- Desafio, ver TemaRepository) - sao dois conceitos diferentes que so'
-- compartilham o nome em portugues.

CREATE TABLE IF NOT EXISTS temas_visuais (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(60) NOT NULL,
    cor_primaria_inicio VARCHAR(7) NOT NULL,
    cor_primaria_fim VARCHAR(7) NOT NULL,
    cor_secundaria VARCHAR(7) NOT NULL,
    cor_terciaria VARCHAR(7) NOT NULL,
    cor_destaque_app VARCHAR(7) NOT NULL,
    padrao TINYINT(1) NOT NULL DEFAULT 0,
    editavel TINYINT(1) NOT NULL DEFAULT 1,
    publicado TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE usuarios ADD COLUMN tema_visual_id INT UNSIGNED NULL AFTER foto_path;
ALTER TABLE usuarios ADD CONSTRAINT fk_usuarios_tema_visual FOREIGN KEY (tema_visual_id) REFERENCES temas_visuais (id);

-- Correcao de arquitetura (Fase 50): cor_terciaria/cor_destaque_app nunca
-- existiram em configuracoes_visuais nesta linha do tempo corrigida (ver
-- migrations que tinham os numeros 127/128, removidas) - nasceram direto
-- como colunas de temas_visuais aqui, entao o DROP COLUMN logo abaixo so'
-- cobre as 3 colunas que de fato vieram de configuracoes_visuais (producao,
-- migrations 030/036).
--
-- 5 temas propostos, carga inicial (nao um limite). cor_primaria_fim
-- calculada por clareamento de 33% da primaria em direcao ao branco (mesma
-- proporcao ja observada entre o laranja padrao anterior, #FF6600/#FF9955),
-- para o Admin nao precisar escolher essa cor no formulario de tema. Laranja
-- e' o tema padrao inicial (decisao do usuario).
INSERT INTO temas_visuais (nome, cor_primaria_inicio, cor_primaria_fim, cor_secundaria, cor_terciaria, cor_destaque_app, padrao, editavel, publicado) VALUES
('Laranja', '#F5761A', '#F8A366', '#FFA451', '#C25400', '#FFF4EA', 1, 0, 1),
('Roxo', '#6B3FA0', '#9C7EBF', '#9B6FD1', '#4A2570', '#F5EEFB', 0, 0, 1),
('Verde', '#2E8B57', '#73B18E', '#5FB37A', '#1D5C39', '#EAF7EF', 0, 0, 1),
('Vermelho', '#D6432E', '#E48173', '#E8735F', '#A32E1E', '#FDECEA', 0, 0, 1),
('Azul-petroleo', '#1B6E7D', '#669EA8', '#4FA1AE', '#0F4B55', '#EAF6F7', 0, 0, 1);

ALTER TABLE configuracoes_visuais
    DROP COLUMN cor_primaria_inicio,
    DROP COLUMN cor_primaria_fim,
    DROP COLUMN cor_secundaria;
