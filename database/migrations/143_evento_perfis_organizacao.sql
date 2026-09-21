-- Fase 48 (correcao pos-teste de fumaca): "Perfil" do Facilitador deixa de
-- ser lista fixa no codigo (instrutor/professor/palestrante/outro) e passa
-- a ser cadastrada pelo Admin, por evento - mesmo espirito de
-- evento_campos_inscricao, nunca semear dado de negocio direto no banco.
CREATE TABLE IF NOT EXISTS evento_perfis_organizacao (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    nome VARCHAR(100) NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_perfis_organizacao_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE evento_atividade_facilitadores
    DROP COLUMN perfil,
    ADD COLUMN perfil_id INT UNSIGNED NOT NULL AFTER usuario_id,
    ADD CONSTRAINT fk_evento_atividade_facilitadores_perfil FOREIGN KEY (perfil_id) REFERENCES evento_perfis_organizacao (id);
