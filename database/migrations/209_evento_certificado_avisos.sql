-- Fase 60 (pendencia 44): documentos de certificado ja avisados, um por
-- documento, para que fechar e reabrir a emissao, ou mudar a regua, nunca
-- reenvie o aviso. chave_documento segue o formato de
-- evento_certificados.chave_unicidade (199).
CREATE TABLE IF NOT EXISTS evento_certificado_avisos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    chave_documento VARCHAR(80) NOT NULL,
    avisado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_certificado_avisos (evento_id, chave_documento),
    KEY idx_evento_certificado_avisos_usuario (usuario_id),
    CONSTRAINT fk_evento_certificado_avisos_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_certificado_avisos_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
