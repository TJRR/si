-- Fase 59: o certificado EMITIDO, com o documento guardado. Decisao do dono:
-- o PDF e' gerado uma vez, gravado, e todo pedido seguinte entrega o mesmo
-- arquivo. Presenca removida depois da emissao nao altera o que ja' foi
-- entregue; o caminho de correcao e' o cancelamento pelo Administrador.
--
-- Uma instrucao por arquivo, mesma regra da 185.
--
-- tipo: VARCHAR, e nao ENUM, pelo mesmo motivo escrito na 184 - tipo novo
-- nao deve exigir migration. Os tres desta fase:
--   evento        participacao no evento como um todo
--   atividade     uma atividade especifica (evento_atividades.emite_certificado)
--   apresentacao  apresentacao de trabalho no evento
--
-- As colunas de vinculo sao nulas fora da frente a que pertencem:
-- atividade_id so' no tipo atividade, trabalho_id/trabalho_autor_id so' no
-- tipo apresentacao, evento_inscricao_id so' quando a pessoa tem inscricao
-- (facilitador e avaliador nao tem), usuario_id nulo so' no coautor sem
-- conta, que existe de verdade: trabalho_autores.usuario_id e' preenchido
-- apenas para o autor principal (migration 151). Esse coautor alcanca o
-- documento dele apenas pela tela administrativa.
--
-- chave_unicidade e' o coracao do desenho. Chave unica sobre as colunas de
-- vinculo NAO funcionaria: tres delas sao nulas em cada frente e, no MySQL,
-- linha com coluna nula nao colide em UNIQUE - a trava valeria numa frente e
-- falharia nas outras duas, o que e' pior que nao ter trava (mesmo raciocinio
-- escrito na 184 sobre evento_bonus.tipo_atividade_id). Com uma coluna sempre
-- preenchida, montada pelo codigo de forma deterministica
-- ("evento:<usuario_id>", "atividade:<atividade_id>:<usuario_id>",
-- "apresentacao:<trabalho_autor_id>"), a garantia volta a ser do banco.
--
-- E' ela que torna a emissao idempotente e dispensa transacao e bloqueio de
-- linha: no estado 23000, quem perdeu a corrida devolve a linha que ja'
-- existe, como EventoCheckinRepository::registrar() ja' faz. Duplo clique do
-- participante, ou participante emitindo enquanto o Administrador processa um
-- lote, terminam com um documento so', e fica o arquivo da primeira gravacao.
--
-- condicoes: as condicoes que DE FATO qualificaram a pessoa, no instante da
-- emissao ("participante", "facilitador", "avaliador", ou a combinacao
-- delas). "participante" entra so' quando a regua da configuracao foi
-- cumprida; "facilitador" e "avaliador" entram pela designacao. Guardar aqui
-- congela a RAZAO, nao so' o resultado - sem isso, o Administrador que mudar
-- a regua depois nao consegue mais explicar por que aquela pessoa recebeu.
--
-- nome / documento / tipo_documento / carga_horaria_minutos / periodo_inicio /
-- periodo_fim: retrato do que foi impresso. O documento fica aqui para
-- auditoria e para a reimpressao identica, e NUNCA aparece na pagina publica
-- de conferencia; no PDF ele sai apenas se o texto do Administrador usar a
-- palavra chave [[pessoa.documento]].
--
-- carga_horaria_minutos em minutos, nula quando o documento nao declara horas
-- (apresentacao de trabalho, e avaliador sem numero declarado).
--
-- codigo_verificacao: dez caracteres do alfabeto de CodigoUnicoService, nao
-- seis. Os de seis sao de uso presencial e lidos por camera; este fica
-- impresso num documento que circula fora, e a pagina publica de conferencia
-- aceita tentativa digitada.
--
-- arquivo_path / tamanho_bytes / sha256: molde de evento_anais_versoes (177).
-- O caminho e' relativo a' area privada (storage/uploads), servido por
-- ArquivoPrivadoService::servir() depois da conferencia de quem pede.
--
-- emitido_por NULO significa EMITIDO PELO PROPRIO INTERESSADO, e preenchido
-- significa emitido pela organizacao - mesma distincao de
-- evento_bonus_creditos.anulado_por.
CREATE TABLE IF NOT EXISTS evento_certificados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    tipo VARCHAR(20) NOT NULL,
    chave_unicidade VARCHAR(60) NOT NULL,
    usuario_id INT UNSIGNED NULL,
    evento_inscricao_id INT UNSIGNED NULL,
    atividade_id INT UNSIGNED NULL,
    trabalho_id INT UNSIGNED NULL,
    trabalho_autor_id INT UNSIGNED NULL,
    condicoes VARCHAR(100) NOT NULL DEFAULT '',
    nome VARCHAR(150) NOT NULL,
    documento VARCHAR(30) NULL,
    tipo_documento VARCHAR(30) NULL,
    carga_horaria_minutos SMALLINT UNSIGNED NULL,
    periodo_inicio DATE NULL,
    periodo_fim DATE NULL,
    codigo_verificacao CHAR(10) NOT NULL,
    arquivo_path VARCHAR(255) NOT NULL,
    tamanho_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
    sha256 CHAR(64) NULL,
    emitido_em DATETIME NOT NULL,
    emitido_por INT UNSIGNED NULL,
    cancelado_em DATETIME NULL,
    cancelado_por INT UNSIGNED NULL,
    motivo_cancelamento VARCHAR(500) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_certificados_chave (evento_id, chave_unicidade),
    UNIQUE KEY uq_evento_certificados_codigo (codigo_verificacao),
    KEY idx_evento_certificados_evento_tipo (evento_id, tipo),
    KEY idx_evento_certificados_usuario (usuario_id),
    KEY idx_evento_certificados_atividade (atividade_id),
    KEY idx_evento_certificados_trabalho (trabalho_id),
    CONSTRAINT fk_evento_certificados_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_certificados_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id),
    CONSTRAINT fk_evento_certificados_inscricao FOREIGN KEY (evento_inscricao_id) REFERENCES evento_inscricoes (id),
    CONSTRAINT fk_evento_certificados_atividade FOREIGN KEY (atividade_id) REFERENCES evento_atividades (id),
    CONSTRAINT fk_evento_certificados_trabalho FOREIGN KEY (trabalho_id) REFERENCES trabalhos (id),
    CONSTRAINT fk_evento_certificados_trabalho_autor FOREIGN KEY (trabalho_autor_id) REFERENCES trabalho_autores (id),
    CONSTRAINT fk_evento_certificados_emitido_por FOREIGN KEY (emitido_por) REFERENCES usuarios (id),
    CONSTRAINT fk_evento_certificados_cancelado_por FOREIGN KEY (cancelado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
