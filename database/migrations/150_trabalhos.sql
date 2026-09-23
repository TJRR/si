-- Fase 49: submissao de Trabalhos propriamente dita. eixo_tematico_id e
-- natureza_id sao NULL (opcionais) de proposito: um evento sem nenhum
-- registro cadastrado em trabalho_eixos_tematicos/trabalho_naturezas nao
-- exige que o Admin crie um valor "Geral" ficticio so para o formulario
-- funcionar (a mesma armadilha ja rejeitada pela decisao de arquitetura
-- numero 5 do plano mestre, o "concurso tecnico disfarcado"). numero_sigilo
-- e' gerado sob demanda (nunca no momento da submissao), so quando
-- evento_trabalhos_config.sigilo_cego estiver ligado - mesmo padrao lazy
-- de SubmissaoRepository::garantirNumerosSigilo() do Concurso. nota_final
-- nao e' persistida aqui, e' sempre calculada em tempo real por
-- TrabalhoResultadoService a partir de trabalho_notas.
--
-- Fase 51 (colunas acrescentadas na origem, premissa 8 de Premissas.md):
-- desempate_criterio guarda, em texto congelado, qual regra decidiu o empate
-- com o trabalho imediatamente acima no resultado (o texto fica, mesmo que a
-- regra seja apagada depois); origem/origem_referencia identificam trabalho
-- recebido por canal alternativo (item 5.2 do edital: indisponibilidade da
-- plataforma), sendo origem_referencia o identificador da resposta de origem
-- e a trava real contra importar duas vezes a mesma linha (chave unica por
-- evento; nulo repete a vontade, MySQL nao conta NULL em UNIQUE).
CREATE TABLE IF NOT EXISTS trabalhos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    eixo_tematico_id INT UNSIGNED NULL,
    natureza_id INT UNSIGNED NULL,
    titulo VARCHAR(255) NOT NULL,
    telefone_contato VARCHAR(20) NULL,
    metodo_submissao VARCHAR(30) NOT NULL,
    conteudo_html MEDIUMTEXT NULL,
    link_avaliacao VARCHAR(500) NULL,
    link_publicacao VARCHAR(500) NULL,
    arquivo_avaliacao_path VARCHAR(255) NULL,
    arquivo_publicacao_path VARCHAR(255) NULL,
    numero_sigilo INT UNSIGNED NULL,
    status ENUM('submetido', 'desclassificado', 'aprovado', 'reprovado') NOT NULL DEFAULT 'submetido',
    motivo_desclassificacao TEXT NULL,
    selecionado TINYINT(1) NOT NULL DEFAULT 0,
    desempate_criterio VARCHAR(255) NULL,
    origem ENUM('sistema', 'canal_alternativo') NOT NULL DEFAULT 'sistema',
    origem_referencia VARCHAR(190) NULL,
    submetido_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_trabalhos_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_trabalhos_eixo_tematico FOREIGN KEY (eixo_tematico_id) REFERENCES trabalho_eixos_tematicos (id),
    CONSTRAINT fk_trabalhos_natureza FOREIGN KEY (natureza_id) REFERENCES trabalho_naturezas (id),
    UNIQUE KEY uq_trabalhos_numero_sigilo (evento_id, numero_sigilo),
    UNIQUE KEY uq_trabalhos_origem_referencia (evento_id, origem_referencia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
