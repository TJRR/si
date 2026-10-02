-- Fase 60 (pendencia 12): texto editavel dos avisos por correio eletronico
-- exclusivos do Evento, um por aviso. Os avisos do Concurso nao entram aqui.
--
-- Sem linha, o aviso sai com o texto padrao do proprio codigo; a tabela
-- nasce vazia e so' a tela grava. Apagar a linha volta ao texto padrao.
CREATE TABLE IF NOT EXISTS evento_modelos_aviso (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(60) NOT NULL,
    assunto VARCHAR(200) NOT NULL,
    corpo_html MEDIUMTEXT NOT NULL,
    atualizado_por INT UNSIGNED NULL,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_modelos_aviso_chave (chave),
    CONSTRAINT fk_evento_modelos_aviso_atualizado_por FOREIGN KEY (atualizado_por) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
