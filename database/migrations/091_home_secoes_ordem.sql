-- Fase 19 (#97): ordem das secoes do meio da home (entre Banners e o
-- rodape) passa a ser definida pelo Admin, inclusive das secoes fixas
-- (Trilhas/Cronograma/Desafios/FAQ), nao so' dos blocos de conteudo.
-- Semeia na ordem visual ATUAL (a que a sequencia de include de
-- home/index.php ja produzia), pra nao mudar nada visualmente no
-- primeiro deploy - so' depois o Admin reordena se quiser.
CREATE TABLE home_secoes_ordem (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('fixa','bloco') NOT NULL,
    chave_fixa ENUM('trilhas','cronograma','temas','faq') NULL,
    bloco_id INT UNSIGNED NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    UNIQUE KEY uq_home_secoes_chave_fixa (chave_fixa),
    UNIQUE KEY uq_home_secoes_bloco (bloco_id),
    CONSTRAINT fk_home_secoes_bloco FOREIGN KEY (bloco_id) REFERENCES blocos_conteudo (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO home_secoes_ordem (tipo, chave_fixa, ordem) VALUES ('fixa', 'trilhas', 0);
INSERT INTO home_secoes_ordem (tipo, bloco_id, ordem) SELECT 'bloco', id, 1 FROM blocos_conteudo WHERE chave = 'sobre';
INSERT INTO home_secoes_ordem (tipo, chave_fixa, ordem) VALUES ('fixa', 'cronograma', 2);
INSERT INTO home_secoes_ordem (tipo, chave_fixa, ordem) VALUES ('fixa', 'temas', 3);
INSERT INTO home_secoes_ordem (tipo, bloco_id, ordem) SELECT 'bloco', id, 4 FROM blocos_conteudo WHERE chave = 'premiacao';
INSERT INTO home_secoes_ordem (tipo, bloco_id, ordem) SELECT 'bloco', id, 5 + ordem FROM blocos_conteudo WHERE chave IS NULL;
INSERT INTO home_secoes_ordem (tipo, chave_fixa, ordem) VALUES ('fixa', 'faq', 999);
