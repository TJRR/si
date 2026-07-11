ALTER TABLE formularios_dinamicos
    MODIFY COLUMN status ENUM('rascunho', 'publicado', 'despublicado', 'arquivado') NOT NULL DEFAULT 'rascunho';
