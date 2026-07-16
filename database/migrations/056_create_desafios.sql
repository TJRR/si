-- Fase 17 (Bug 2): nivel "Desafio", filho de "Tema" - cada Tema do edital tem
-- varios Desafios (a "pergunta desafio" que a equipe escolhe). Guarda o texto
-- integral da pergunta (decisao ja confirmada: nao usar codigo/numero, e' o
-- que o avaliador le e compara com a resposta da equipe).
CREATE TABLE IF NOT EXISTS desafios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tema_id INT UNSIGNED NOT NULL,
    pergunta TEXT NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_desafios_tema FOREIGN KEY (tema_id) REFERENCES temas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
