-- Fase 50 (correcao de arquitetura): ordem por arrasto das secoes da pagina
-- publica de cada Evento.
--
-- Fase 51 (reescrita na origem, premissa 8 de Premissas.md): deixou de ser
-- "ordem dos blocos" e virou a ordem de TODA a pagina. Cada linha e' uma
-- secao: os Quadros de apresentacao e as Faixas (uma linha cada, sem
-- referencia, porque sao listas unicas por evento), cada Bloco de conteudo e
-- cada instancia de componente (Contagem regressiva, Cronograma, Cartoes,
-- Destaques, Programacao, Perguntas frequentes, Local). O mesmo tipo pode
-- entrar varias vezes, desde que com referencia diferente. Alem da ordem, a
-- linha guarda se a secao esta ativa e se entra no menu do cabecalho, com o
-- rotulo que aparece la' - o menu deixou de ser montado a partir de
-- evento_blocos_conteudo.mostrar_no_menu e passou a sair daqui, na mesma
-- ordem da pagina.
--
-- A referencia e' polimorfica, entao nao ha' chave estrangeira nem
-- ON DELETE CASCADE: quem apaga um bloco ou um componente tambem apaga a
-- linha de ordem correspondente (EventoSecaoOrdemRepository::removerItem()),
-- sempre na mesma transacao.
CREATE TABLE IF NOT EXISTS evento_secoes_ordem (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    tipo ENUM(
        'quadros','faixas','bloco','contagem','cronograma',
        'cartoes','destaques','programacao','faq','local'
    ) NOT NULL,
    referencia_id INT UNSIGNED NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    mostrar_no_menu TINYINT(1) NOT NULL DEFAULT 0,
    rotulo_menu VARCHAR(60) NULL,
    CONSTRAINT fk_evento_secoes_ordem_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    UNIQUE KEY uq_evento_secoes_ordem_item (evento_id, tipo, referencia_id),
    INDEX idx_evento_secoes_ordem_evento (evento_id, ordem)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
