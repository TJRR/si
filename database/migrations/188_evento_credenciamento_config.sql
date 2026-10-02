-- Fase 58: credenciamento no local (dinamica de pontos v2, secao 2): codigo
-- fixo impresso nas paredes do auditorio e no balcao, lido pelo proprio
-- participante no leitor de "Confirmar presenca". So' presencial, por
-- decisao do dono: nao ha' codigo de 5 caracteres para quem esta' remoto.
--
-- codigo: nulo ate' o Administrador ligar pela primeira vez; unico entre as
-- tres colunas lidas pelo mesmo leitor (atividade, competicao e esta), por
-- CodigoUnicoService::gerarCodigoFixoDoEvento().
--
-- leitura_inicio / leitura_fim: janela com data e hora. Em branco, valem os
-- dias do evento.
CREATE TABLE IF NOT EXISTS evento_credenciamento_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 0,
    codigo CHAR(6) NULL,
    leitura_inicio DATETIME NULL,
    leitura_fim DATETIME NULL,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_credenciamento_config_evento (evento_id),
    UNIQUE KEY uq_evento_credenciamento_config_codigo (codigo),
    CONSTRAINT fk_evento_credenciamento_config_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
