-- Vinculo de categoria por avaliador/concurso (Fase 10). Fica separada de
-- usuario_perfil_concurso -- que e generica para qualquer perfil -- porque
-- categoria e um conceito que so existe para o perfil avaliador.
CREATE TABLE IF NOT EXISTS avaliador_categorias (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    concurso_id INT UNSIGNED NOT NULL,
    categoria_avaliador_id INT UNSIGNED NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_avaliador_categorias_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_avaliador_categorias_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    CONSTRAINT fk_avaliador_categorias_categoria FOREIGN KEY (categoria_avaliador_id) REFERENCES categorias_avaliador (id),
    UNIQUE KEY uq_avaliador_categorias_usuario_concurso (usuario_id, concurso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
