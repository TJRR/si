-- Fase 55 (28/09/2026): conexao entre dois participantes de um Evento
-- ("fazer amizade" do documento da dinamica de pontos). Uma pessoa le o
-- codigo do cracha da outra no aplicativo e as DUAS pontuam, com uma linha
-- so' por dupla. O ranking geral e' da Fase 58: aqui so' se grava o fato e
-- os pontos vigentes no momento, congelados.
--
-- Tudo aditivo: duas tabelas novas. Nenhuma tabela do Concurso e' tocada,
-- nenhum ENUM existente e' alterado (Conexoes nao e' secao da pagina
-- publica, entao evento_secoes_ordem fica como esta') e nenhuma tabela de
-- tentativas nasce aqui: a tela reaproveitada ("Ler codigo" virou "Conectar
-- com participante") ja usa evento_leituras_codigo_falhas, da Fase 43.

-- inscricao_menor_id / inscricao_maior_id: a conexao e' simetrica ("A
-- conectou com B" e "B conectou com A" sao o mesmo fato), entao o par e'
-- gravado sempre em ordem canonica, o menor id primeiro. E' isso que faz a
-- chave unica funcionar nos dois sentidos de leitura e que resolve o caso
-- real de duas pessoas apontando a camera uma para a outra ao mesmo tempo:
-- a segunda gravacao leva erro 23000 e vira "voces ja estavam conectados".
--
-- iniciador_inscricao_id: quem leu o codigo. E' registro historico, nunca
-- regra - os dois lados tem exatamente os mesmos direitos sobre a conexao.
--
-- pontos_creditados_menor / pontos_creditados_maior: dois campos, e nao um,
-- porque o teto de conexoes que pontuam e' POR PESSOA - na mesma conexao um
-- lado pode estar abaixo do teto e o outro ja ter estourado. Congelam o
-- valor vigente no instante da leitura, mesma regra de
-- evento_estande_visitas.pontos_creditados (Fase 54): mudar a configuracao
-- depois nao altera o que ja foi creditado.
--
-- A tabela guarda so' numeros, pontos e data. Nenhum nome, contato, foto ou
-- marca de visibilidade e' copiado para ca': o que cada pessoa mostra a
-- outra e' lido de usuarios_perfil no momento da exibicao (migration 182),
-- para que esconder um dado depois valha tambem para conexoes ja' feitas.
CREATE TABLE IF NOT EXISTS evento_conexoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    inscricao_menor_id INT UNSIGNED NOT NULL,
    inscricao_maior_id INT UNSIGNED NOT NULL,
    iniciador_inscricao_id INT UNSIGNED NOT NULL,
    pontos_creditados_menor SMALLINT UNSIGNED NOT NULL,
    pontos_creditados_maior SMALLINT UNSIGNED NOT NULL,
    conectado_em DATETIME NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_evento_conexoes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_conexoes_menor FOREIGN KEY (inscricao_menor_id) REFERENCES evento_inscricoes (id),
    CONSTRAINT fk_evento_conexoes_maior FOREIGN KEY (inscricao_maior_id) REFERENCES evento_inscricoes (id),
    CONSTRAINT fk_evento_conexoes_iniciador FOREIGN KEY (iniciador_inscricao_id) REFERENCES evento_inscricoes (id),
    UNIQUE KEY uq_evento_conexoes_par (inscricao_menor_id, inscricao_maior_id),
    INDEX idx_evento_conexoes_evento (evento_id),
    INDEX idx_evento_conexoes_menor (inscricao_menor_id),
    INDEX idx_evento_conexoes_maior (inscricao_maior_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Configuracao de Conexoes por evento, no molde de evento_estandes_config
-- (Fase 54): a linha nasce no primeiro salvamento da tela administrativa,
-- nada e' semeado aqui, e evento sem linha equivale a modulo desligado.
--
-- ativo = 0 nao aceita conexao nova. ativo = 1 com pontos_por_conexao = 0 e'
-- combinacao legitima: registra o encontro sem creditar ponto (evento sem a
-- dinamica de pontos, mas com a lista de conexoes).
--
-- teto_conexoes_pontuadas = 0 significa sem limite. Acima do teto a conexao
-- continua sendo registrada, com zero ponto para quem ja' estourou.
CREATE TABLE IF NOT EXISTS evento_conexoes_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 0,
    pontos_por_conexao SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    teto_conexoes_pontuadas SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_conexoes_config_evento (evento_id),
    CONSTRAINT fk_evento_conexoes_config_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
