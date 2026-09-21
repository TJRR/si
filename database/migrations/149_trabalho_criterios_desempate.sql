-- Fase 49: criterios de avaliacao e regras de desempate de Trabalhos,
-- configuraveis por evento pelo Admin - mesmo estilo de codigo de
-- criterios_avaliacao/regras_desempate do Concurso (fonte de inspiracao),
-- mas em tabelas proprias, sem nenhuma FK para etapas/concursos (decisao
-- de arquitetura numero 5 do plano mestre). nota_maxima de cada criterio
-- funciona como peso: quem cadastra um criterio com nota maxima maior ja
-- da mais peso a ele na soma, sem formula multiplicativa separada.
CREATE TABLE IF NOT EXISTS trabalho_criterios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao TEXT NULL,
    nota_maxima DECIMAL(5,2) NOT NULL DEFAULT 2.00,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trabalho_criterios_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS trabalho_regras_desempate (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    tipo ENUM('criterio', 'data_submissao') NOT NULL,
    criterio_id INT UNSIGNED NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    direcao ENUM('asc', 'desc') NOT NULL DEFAULT 'desc',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_trabalho_regras_desempate_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_trabalho_regras_desempate_criterio FOREIGN KEY (criterio_id) REFERENCES trabalho_criterios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
