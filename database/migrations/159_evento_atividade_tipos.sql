-- Fase 51: catalogo de tipos de atividade por evento (Oficina, Palestra,
-- Sessao solene, Cultural, Experiencia...), usado como etiqueta colorida nos
-- componentes Destaques e Programacao da pagina publica. Catalogo por evento,
-- nunca lista fixa em codigo, pelo mesmo motivo de trabalho_eixos_tematicos e
-- evento_perfis_organizacao: cada edicao usa os seus.
--
-- Migration nova (nao alteracao da 137, que cria evento_atividades) porque a
-- coluna tipo_id depende desta tabela, criada depois dela: a premissa 8 manda
-- corrigir na origem, e corrigir na origem aqui seria impossivel sem inverter
-- a ordem de criacao das duas tabelas.
CREATE TABLE IF NOT EXISTS evento_atividade_tipos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    nome VARCHAR(60) NOT NULL,
    cor VARCHAR(7) NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_atividade_tipos_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    INDEX idx_evento_atividade_tipos_evento_ordem (evento_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- destacar_na_pagina: a atividade entra na lista de destaques da pagina
-- publica quando o componente Destaques estiver no modo vinculado.
ALTER TABLE evento_atividades
    ADD COLUMN tipo_id INT UNSIGNED NULL AFTER nome,
    ADD COLUMN destacar_na_pagina TINYINT(1) NOT NULL DEFAULT 0 AFTER tipo_id,
    ADD CONSTRAINT fk_evento_atividades_tipo FOREIGN KEY (tipo_id) REFERENCES evento_atividade_tipos (id);
