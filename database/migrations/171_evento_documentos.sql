-- Reabertura da Fase 51: Documentos do Evento (editais, anexos,
-- retificacoes, resultados e atas), copia da funcao de Documentos do
-- Concurso (migrations 072 e 104) com evento_id no lugar de concurso_id e
-- sem trilha, que nao existe no Evento. Mesmo versionamento: um novo envio
-- com o mesmo tipo e titulo (mesmo grupo_documento) vira nova versao, a
-- anterior fica ativo = 0 e nunca e' apagada; publicado tira o documento da
-- pagina publica sem apagar nada; ordem e' a do arrastar-e-soltar da tela.
--
-- Primeiro uso: o botao "Acessar o Edital completo" da secao de submissao
-- (componente Cronograma, migration 161), que aponta para um documento
-- daqui. A chave estrangeira dessa coluna nasce nesta migration porque a
-- 161 roda antes desta tabela existir. ON DELETE SET NULL: remover o
-- documento (todas as versoes) so' desliga o botao, nunca trava a remocao.
CREATE TABLE IF NOT EXISTS evento_documentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    tipo ENUM('edital', 'edital_simples', 'anexo', 'retificacao', 'resultado_final', 'ata') NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    arquivo_path VARCHAR(255) NOT NULL,
    grupo_documento VARCHAR(160) NOT NULL,
    versao INT UNSIGNED NOT NULL DEFAULT 1,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    publicado TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_por INT UNSIGNED NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_documentos_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_documentos_criado_por FOREIGN KEY (criado_por) REFERENCES usuarios (id),
    KEY idx_evento_documentos_grupo (evento_id, grupo_documento),
    KEY idx_evento_documentos_ordem (evento_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE evento_secao_cronograma
    ADD CONSTRAINT fk_evento_secao_cronograma_documento
    FOREIGN KEY (botao1_documento_id) REFERENCES evento_documentos (id) ON DELETE SET NULL;

-- Reabertura da Fase 51, segunda rodada: o terceiro botao do Cronograma (modelo
-- do resumo expandido) tambem aponta para um Documento do Evento.
ALTER TABLE evento_secao_cronograma
    ADD CONSTRAINT fk_evento_secao_cronograma_documento3
    FOREIGN KEY (botao3_documento_id) REFERENCES evento_documentos (id) ON DELETE SET NULL;
