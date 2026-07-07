-- concurso_id NULL = papel global (ex.: Administrador do sistema todo).
-- concurso_id preenchido = papel restrito aquele concurso (ex.: Avaliador so no 5o Premio).
-- Atencao: o MySQL trata multiplos NULL como distintos nesta UNIQUE KEY, entao a
-- checagem de duplicidade de papel GLOBAL (concurso_id NULL) para o mesmo
-- usuario/perfil precisa ser feita tambem a nivel de aplicacao (repository),
-- nao apenas pela constraint.
CREATE TABLE IF NOT EXISTS usuario_perfil_concurso (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT UNSIGNED NOT NULL,
    perfil_id INT UNSIGNED NOT NULL,
    concurso_id INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_upc_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_upc_perfil FOREIGN KEY (perfil_id) REFERENCES perfis (id),
    CONSTRAINT fk_upc_concurso FOREIGN KEY (concurso_id) REFERENCES concursos (id),
    UNIQUE KEY uq_upc_usuario_perfil_concurso (usuario_id, perfil_id, concurso_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
