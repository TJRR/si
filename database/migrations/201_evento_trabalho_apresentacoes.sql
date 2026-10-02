-- Fase 59: a marca de que um trabalho foi EFETIVAMENTE APRESENTADO no
-- evento. A norma de submissao de trabalhos costuma condicionar o certificado
-- de apresentacao a esse fato, e ate' esta fase o sistema nao o registrava em
-- lugar nenhum: a unica marca proxima era o motivo em texto livre de
-- evento_anais_exclusoes (177).
--
-- Uma instrucao por arquivo, mesma regra da 185.
--
-- Molde de evento_anais_exclusoes: SO' SE GRAVA O FATO. Sem linha, o trabalho
-- nao foi apresentado, e nenhum autor dele tem certificado de apresentacao -
-- nao existe linha por trabalho para gerar nem sincronizar quando a selecao
-- muda.
--
-- UNIQUE (trabalho_id): a marca e' do trabalho, nao do autor. O certificado
-- sai para TODOS os autores, e nao apenas para quem ficou junto ao cartaz de
-- exposicao.
--
-- Desmarcar e' DELETE da linha, mesmo espirito do resto do sistema; a trilha
-- historica fica na Auditoria.
--
-- Nenhuma relacao com os Anais: o certificado atesta o fato de ter
-- apresentado, e a publicacao do volume e' editorial, posterior, e pode nao
-- acontecer. Usar esta marca na tela de exclusoes dos Anais ficou registrado
-- como pendencia.
CREATE TABLE IF NOT EXISTS evento_trabalho_apresentacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    trabalho_id INT UNSIGNED NOT NULL,
    apresentado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    apresentado_por INT UNSIGNED NULL,
    observacao VARCHAR(255) NULL,
    UNIQUE KEY uq_evento_trabalho_apresentacoes_trabalho (trabalho_id),
    KEY idx_evento_trabalho_apresentacoes_evento (evento_id),
    CONSTRAINT fk_evento_trabalho_apresentacoes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_trabalho_apresentacoes_trabalho FOREIGN KEY (trabalho_id) REFERENCES trabalhos (id) ON DELETE CASCADE,
    CONSTRAINT fk_evento_trabalho_apresentacoes_apresentado_por FOREIGN KEY (apresentado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
