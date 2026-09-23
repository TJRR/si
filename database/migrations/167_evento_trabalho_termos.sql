-- Fase 51: aceites formais da submissao de Trabalhos.
--
-- O formulario temporario do Google trazia uma declaracao unica, obrigatoria,
-- cobrindo normas do edital, ciencia dos coautores, ausencia de identificacao
-- na versao de avaliacao e autorizacao de publicacao. O formulario do sistema
-- passa a ter uma secao de aceites configuravel por evento, para cada edicao
-- definir os seus (inclusive o aceite regulamentar de tratamento de dados
-- pessoais, itens 12.3 e 12.4 do edital n. 10/2026, e a autorizacao de
-- publicacao nos Anais).
--
-- trabalho_termos_aceitos guarda o texto exato aceito, nao so' o id do termo:
-- o termo pode ser corrigido depois, e o que a pessoa aceitou naquele dia
-- precisa continuar recuperavel, palavra por palavra. termo_id fica nulo
-- quando o termo de origem nao existe mais ou quando o aceite veio de fora
-- (importacao do canal alternativo).
CREATE TABLE IF NOT EXISTS evento_trabalho_termos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    rotulo VARCHAR(150) NOT NULL,
    texto_html TEXT NOT NULL,
    obrigatorio TINYINT(1) NOT NULL DEFAULT 1,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_trabalho_termos_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_trabalho_termos_evento_ordem (evento_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trabalho_termos_aceitos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    trabalho_id INT UNSIGNED NOT NULL,
    termo_id INT UNSIGNED NULL,
    rotulo_snapshot VARCHAR(150) NOT NULL,
    texto_snapshot TEXT NOT NULL,
    origem ENUM('sistema','canal_alternativo') NOT NULL DEFAULT 'sistema',
    aceito_em DATETIME NOT NULL,
    CONSTRAINT fk_trabalho_termos_aceitos_trabalho FOREIGN KEY (trabalho_id) REFERENCES trabalhos (id),
    CONSTRAINT fk_trabalho_termos_aceitos_termo FOREIGN KEY (termo_id) REFERENCES evento_trabalho_termos (id),
    INDEX idx_trabalho_termos_aceitos_trabalho (trabalho_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
