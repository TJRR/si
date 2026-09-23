-- Fase 51: pastas na biblioteca de midias, com subpastas.
--
-- A biblioteca era uma lista unica, sem agrupamento: com o volume de imagens
-- da pagina do evento (quadros, faixas, blocos, cartoes, mapa) a lista deixa
-- de ser navegavel. pasta_pai_id aponta para a propria tabela, formando a
-- hierarquia; pasta nula em midias significa "raiz", entao nada do que ja
-- existe precisa ser movido para a estrutura nova funcionar.
--
-- Sem ON DELETE CASCADE de proposito: apagar pasta com conteudo dentro e'
-- decisao do Admin na tela (mover o conteudo antes ou recusar a exclusao),
-- nunca efeito colateral silencioso de uma chave estrangeira.
CREATE TABLE IF NOT EXISTS midia_pastas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(120) NOT NULL,
    pasta_pai_id INT UNSIGNED NULL,
    criado_por INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_midia_pastas_pai FOREIGN KEY (pasta_pai_id) REFERENCES midia_pastas (id),
    CONSTRAINT fk_midia_pastas_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios (id),
    INDEX idx_midia_pastas_pai (pasta_pai_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE midias
    ADD COLUMN pasta_id INT UNSIGNED NULL AFTER concurso_id,
    ADD CONSTRAINT fk_midias_pasta FOREIGN KEY (pasta_id) REFERENCES midia_pastas (id);
