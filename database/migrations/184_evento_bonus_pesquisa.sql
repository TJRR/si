-- Fase 57 (29/09/2026): Bonus automaticos e Pesquisa de satisfacao (secao 8
-- do documento da dinamica de pontos).
--
-- BONUS E' ENTIDADE CADASTRAVEL, nao um trio de regras fixas no codigo
-- (correcao do dono ao primeiro plano da fase). O Administrador cadastra
-- quantos bonus quiser no evento, com o nome que quiser, escolhendo o tipo
-- numa lista, informando o que aquele tipo exige e quantos pontos vale. A 5a
-- Semana de Inovacao vai ter os tres bonus do documento (Bingo da Inovacao
-- com 5 atividades e 15 pontos, presenca em 3 dias diferentes com 25 pontos,
-- e responder a' pesquisa com 20 pontos), mas quem os cria e' a tela, nunca
-- esta migration, e a proxima edicao pode ter outros sem uma linha de codigo
-- nova.
--
-- O limite entre cadastro e codigo: o que se cadastra e' o BONUS; o que o
-- codigo sabe fazer e' APURAR. Cada tipo corresponde a uma consulta escrita
-- em BonusApuracaoService. Um tipo novo e' uma entrada na constante TIPOS
-- mais um metodo no servico, sem migration.
--
-- Diferenca central em relacao as Fases 54, 55 e 56: aqui nao ha' nada novo
-- a ler nem a enviar. Os bonus de presenca saem de evento_checkins, que
-- existe desde a Fase 47 e que esta fase SO' LE, nunca altera. O ranking
-- geral continua sendo da Fase 58.
--
-- Tudo aditivo: sete tabelas novas mais um unico ALTER, que so' ACRESCENTA
-- colunas a evento_checkins (bloco E, correcao da pendencia 33: desfazer uma
-- presenca registrada por engano). Nenhuma tabela do Concurso e' tocada,
-- nenhum ENUM existente e' alterado (nenhum dos dois modulos e' secao da
-- pagina publica, entao evento_secoes_ordem fica como esta') e nenhuma
-- coluna existente muda de tipo. Nada e' semeado: evento sem bonus
-- cadastrado e' evento sem bonus, e o modulo nasce desligado.

-- ---------------------------------------------------------------------
-- evento_bonus_config: a chave geral do modulo no evento, no molde de
-- evento_conexoes_config (Fase 55). So' liga e desliga: o que vale em cada
-- evento esta' no catalogo evento_bonus, abaixo.
--
-- Desligar esconde tudo do participante e NAO apaga credito nenhum: a Fase
-- 58 soma o que estiver sem anulacao, independentemente desta chave.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evento_bonus_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 0,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_bonus_config_evento (evento_id),
    CONSTRAINT fk_evento_bonus_config_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- evento_bonus: o catalogo, uma linha por bonus cadastrado no evento.
--
-- tipo: VARCHAR, e nao ENUM, pelo mesmo motivo escrito na migration 121
-- (evento_campos_inscricao.tipo) - tipo novo nao deve exigir migration. Os
-- quatro desta fase, na constante BonusAdminController::TIPOS:
--   atividades_distintas  exige = quantas atividades diferentes
--   dias_distintos        exige = quantos dias diferentes
--   atividades_do_tipo    exige = quantas, mais tipo_atividade_id
--   responder_pesquisa    exige = 0 (nao pede numero)
--
-- exigencia: o numero que o tipo pede, zero nos tipos que nao pedem numero.
--
-- tipo_atividade_id: nulo em tres dos quatro tipos. Por isso a duplicata
-- exata (mesmo tipo, mesma exigencia, mesmo tipo de atividade) e' recusada
-- no repositorio, e nao por chave unica: no MySQL, linha com coluna nula nao
-- colide em UNIQUE, entao a chave funcionaria em atividades_do_tipo e
-- falharia nos outros tres, o que e' pior que nao ter trava nenhuma.
--
-- Dois bonus do mesmo tipo com exigencias diferentes ACUMULAM, por decisao
-- do dono: "5 atividades, 15 pontos" e "10 atividades, 30 pontos" sao duas
-- linhas, cada uma com o seu credito, e quem chega a 10 leva as duas. A
-- escada de metas e' cadastro, nao codigo.
--
-- ativo: bonus que ja' gerou credito nunca e' apagado (a chave estrangeira
-- de evento_bonus_creditos impede), e sim desativado - some do painel do
-- participante e da apuracao, e os creditos ja' dados continuam valendo.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evento_bonus (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    nome VARCHAR(150) NOT NULL,
    descricao VARCHAR(300) NULL,
    tipo VARCHAR(30) NOT NULL,
    exigencia SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    tipo_atividade_id INT UNSIGNED NULL,
    pontos SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_evento_bonus_evento_ordem (evento_id, ordem),
    CONSTRAINT fk_evento_bonus_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_bonus_tipo_atividade FOREIGN KEY (tipo_atividade_id) REFERENCES evento_atividade_tipos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- evento_bonus_creditos: o fato, com os pontos congelados.
--
-- Mesma regra de evento_estande_visitas.pontos_creditados (Fase 54),
-- evento_conexoes (55) e evento_divulgacao_comprovacoes (56): o valor e'
-- lido da configuracao no instante em que a condicao se cumpre e nunca
-- recalculado. Mudar os pontos depois nao altera quem ja' recebeu.
--
-- exigencia_atingida: quantas atividades ou dias o bonus exigia NAQUELE
-- instante. Congela a REGRA, e nao so' o valor - sem ela, o Administrador
-- que subir o Bingo de 5 para 7 atividades nao consegue mais explicar por
-- que aquela pessoa recebeu.
--
-- evento_id repetido de proposito, como na 183: os agregados do evento
-- inteiro nao precisam de juncao com evento_bonus nem com evento_inscricoes.
--
-- UNIQUE (evento_inscricao_id, bonus_id) e' o coracao do desenho: um credito
-- por pessoa por bonus, garantido pelo banco. E' ela que torna a apuracao
-- idempotente e dispensa transacao e bloqueio de linha no servico - duas
-- apuracoes simultaneas da mesma pessoa colidem, e a perdedora simplesmente
-- nao faz nada.
--
-- A chave vale INCLUSIVE para linha anulada, e isso e' o OPOSTO da Fase 56,
-- de proposito. La', a anulacao devolvia a vaga porque a pessoa podia enviar
-- prova nova. Aqui a apuracao roda a cada confirmacao de presenca: se a
-- anulacao liberasse credito novo, o sistema recreditaria o bonus na leitura
-- seguinte e desfaria a anulacao sozinho. Por isso a apuracao pula quem ja'
-- tem linha daquele bonus, anulada ou nao.
--
-- anulado_por NULO significa ANULADO PELO SISTEMA, e preenchido significa
-- anulado por uma pessoa. A distincao existe desde o bloco E, quando a
-- presenca deixou de ser eterna (pendencia 33: o Administrador pode marcar
-- como removida uma presenca registrada por engano). Dai as duas regras:
--
--   credito anulado POR PESSOA nunca volta sozinho, e o unico caminho de
--   volta e' a reversao, tambem por pessoa;
--
--   credito anulado PELO SISTEMA, por ter perdido a base quando uma presenca
--   foi removida, volta sozinho assim que a condicao e' cumprida de novo (a
--   presenca removida por engano e registrada outra vez, por exemplo). Quem
--   desfaz e' a propria apuracao, e nao o Administrador.
--
-- Sem essa distincao, remover uma presenca por engano custaria dois
-- consertos manuais em telas diferentes, e o segundo seria facil de esquecer.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evento_bonus_creditos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    bonus_id INT UNSIGNED NOT NULL,
    evento_inscricao_id INT UNSIGNED NOT NULL,
    pontos_creditados SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    exigencia_atingida SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    creditado_em DATETIME NOT NULL,
    anulado_em DATETIME NULL,
    anulado_por INT UNSIGNED NULL,
    motivo_anulacao VARCHAR(500) NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_bonus_creditos (evento_inscricao_id, bonus_id),
    INDEX idx_evento_bonus_creditos_evento_bonus (evento_id, bonus_id),
    CONSTRAINT fk_evento_bonus_creditos_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_bonus_creditos_bonus FOREIGN KEY (bonus_id) REFERENCES evento_bonus (id),
    CONSTRAINT fk_evento_bonus_creditos_inscricao FOREIGN KEY (evento_inscricao_id) REFERENCES evento_inscricoes (id),
    CONSTRAINT fk_evento_bonus_creditos_anulado_por FOREIGN KEY (anulado_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- evento_pesquisa_config: a pesquisa de satisfacao no evento.
--
-- Sem coluna de pontos: o que credita quem responde e' um bonus cadastrado
-- do tipo responder_pesquisa. A pesquisa funciona igual sem esse bonus,
-- apenas sem creditar nada.
--
-- data_inicio / data_fim: janela propria, no molde da 183. As duas em branco
-- significam "use as datas do evento". A pesquisa costuma abrir no ultimo
-- dia e ficar aberta depois do encerramento.
--
-- convite_enviado_em / convite_comunicacao_id: o convite reaproveita a fila
-- de correio eletronico das Fases 44 e 45 (evento_comunicacoes, processada
-- por database/processar_comunicacao_evento.php, 10 por minuto). A campanha
-- nasce com o tipo 'comunicado' porque evento_comunicacoes.tipo e' um ENUM
-- de tres valores e criar um tipo proprio exigiria ALTERAR ENUM EXISTENTE,
-- o que a regra do projeto proibe; por isso a tela descobre "quando foi o
-- ultimo convite" por estas duas colunas, e nao por filtro no tipo.
--
-- O aviso de anonimato NAO e' campo configuravel: e' texto fixo na view, para
-- nao poder ser apagado por engano.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evento_pesquisa_config (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 0,
    data_inicio DATE NULL,
    data_fim DATE NULL,
    titulo VARCHAR(150) NULL,
    texto_abertura_html MEDIUMTEXT NULL,
    convite_assunto VARCHAR(200) NULL,
    convite_corpo_html MEDIUMTEXT NULL,
    convite_enviado_em DATETIME NULL,
    convite_comunicacao_id INT UNSIGNED NULL,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_evento_pesquisa_config_evento (evento_id),
    CONSTRAINT fk_evento_pesquisa_config_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_pesquisa_config_comunicacao FOREIGN KEY (convite_comunicacao_id) REFERENCES evento_comunicacoes (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- evento_pesquisa_perguntas: molde de evento_campos_inscricao (121), com
-- tres diferencas justificadas.
--
-- 1. enunciado com 300, e nao os 150 do rotulo de campo: pergunta de
--    pesquisa e' frase, nao etiqueta.
-- 2. ativa: pergunta que ja' tem resposta nao pode ser apagada (a chave
--    estrangeira de evento_pesquisa_respostas impede) nem ter as opcoes
--    editadas (evento_pesquisa_respostas.valor_numero guarda a POSICAO da
--    opcao, entao reordenar a lista mudaria o significado do que ja' foi
--    respondido). "Remover" na tela vira desativar: some do formulario,
--    continua no resultado.
-- 3. ordem com o mesmo tipo de evento_campos_inscricao.ordem, para
--    reaproveitar a reordenacao por arrasto (assets/js/reordenar-arrastar.js).
--
-- tipo: VARCHAR pelo mesmo motivo da 121. Os quatro desta fase:
--   escala           1 a 5, com os extremos nomeados em config_json
--   lista_opcoes     uma escolha entre as opcoes de config_json
--   multipla_escolha varias opcoes, uma linha de resposta por opcao marcada
--   texto            texto livre
-- Os nomes lista_opcoes e texto repetem de proposito os da 121: mesmo
-- conceito, mesmo nome.
--
-- config_json: {"opcoes":[...]} nos dois tipos de opcao,
-- {"rotulo_minimo":"...","rotulo_maximo":"..."} na escala.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evento_pesquisa_perguntas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    ordem INT UNSIGNED NOT NULL DEFAULT 0,
    enunciado VARCHAR(300) NOT NULL,
    tipo VARCHAR(30) NOT NULL,
    obrigatoria TINYINT(1) NOT NULL DEFAULT 0,
    texto_ajuda VARCHAR(255) NULL,
    config_json JSON NULL,
    ativa TINYINT(1) NOT NULL DEFAULT 1,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_evento_pesquisa_perguntas_evento_ordem (evento_id, ordem),
    CONSTRAINT fk_evento_pesquisa_perguntas_evento FOREIGN KEY (evento_id) REFERENCES eventos (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- evento_pesquisa_respondentes: QUEM respondeu (nominal).
--
-- E' a porta; os pontos sao a linha de evento_bonus_creditos. Sao coisas
-- diferentes de proposito:
--   - a porta precisa existir mesmo sem bonus cadastrado, para que ninguem
--     responda duas vezes;
--   - anular o credito NAO pode reabrir a pesquisa, e com uma tabela so'
--     "ja' respondeu?" viraria "existe credito nao anulado?";
--   - o convite precisa de "quem ainda nao respondeu", consulta limpa numa
--     tabela pequena (o convite alcanca so' os inscritos, porque a campanha
--     de correio eletronico e' montada a partir de evento_inscricoes).
--
-- Chaveada por USUARIO, e nao por inscricao no evento (correcao do bloco E,
-- pendencia 34). Quem tem o que dizer sobre a organizacao nem sempre e'
-- inscrito: o facilitador que conduziu uma oficina e o avaliador avulso de
-- Trabalhos entram no aplicativo por caminhos proprios, sem nunca ter
-- passado pela inscricao (ver EventoAppController::index()). Chavear por
-- evento_inscricoes os deixava de fora da pesquisa por construcao, e nao
-- por decisao.
--
-- O CREDITO de pontos continua exigindo inscricao, porque ele vive em
-- evento_bonus_creditos.evento_inscricao_id: quem responde sem ser inscrito
-- responde e nao pontua, e a tela diz isso.
--
-- UNIQUE (evento_id, usuario_id) impoe "uma resposta por pessoa, sem
-- correcao" no banco, e nao no PHP. Agora precisa do evento_id na chave,
-- porque a mesma pessoa pode ter vinculo com mais de um evento.
--
-- Esta tabela NAO tem nenhum elo com evento_pesquisa_respostas. Nao ha'
-- chave estrangeira, coluna em comum nem caminho de juncao entre as duas.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evento_pesquisa_respondentes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    usuario_id INT UNSIGNED NOT NULL,
    respondido_em DATETIME NOT NULL,
    UNIQUE KEY uq_evento_pesquisa_respondentes (evento_id, usuario_id),
    INDEX idx_evento_pesquisa_respondentes_evento (evento_id),
    CONSTRAINT fk_evento_pesquisa_respondentes_evento FOREIGN KEY (evento_id) REFERENCES eventos (id),
    CONSTRAINT fk_evento_pesquisa_respondentes_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- evento_pesquisa_respostas: O QUE foi respondido (anonimo).
--
-- resposta_uid: 32 caracteres aleatorios (bin2hex(random_bytes(16)) no PHP),
-- iguais em todas as respostas de UM preenchimento. Agrupa as respostas de
-- uma pessoa entre si, o que o relatorio precisa para cruzar perguntas, sem
-- dizer de quem sao.
--
-- valor_numero: a nota na escala (1 a 5), a POSICAO da opcao escolhida ou
-- marcada (a partir de 1), ou zero no texto livre.
--
-- A CHAVE PRIMARIA (resposta_uid, pergunta_id, valor_numero) faz tres coisas
-- de uma vez:
--   (a) NAO EXISTE coluna de numeracao automatica nesta tabela, entao a
--       ordem de chegada nao e' recuperavel a partir dela;
--   (b) o InnoDB agrupa fisicamente pela chave primaria, que comeca por um
--       valor aleatorio, entao a ordem fisica tambem nao diz nada;
--   (c) a multipla escolha ganha varias linhas por pergunta de graca, e a
--       mesma opcao marcada duas vezes fica impossivel.
--
-- respondido_em e' DATE, e nao DATETIME, E ESTA TABELA NAO TEM criado_em.
-- A ausencia e' DELIBERADA, e e' a unica quebra de convencao desta fase:
-- data e hora ao segundo, dos dois lados, seria praticamente uma chave de
-- ligacao entre quem respondeu e o que foi respondido. Pelo mesmo motivo, a
-- gravacao desta tabela NUNCA passa por Auditoria::registrar(), que carimba
-- usuario, endereco de rede e instante. NAO "corrigir" nenhuma das duas
-- coisas em fase futura: seria destruir o anonimato em silencio.
--
-- O que o desenho garante: nenhuma tela, consulta, exportacao ou perfil do
-- sistema liga uma resposta a uma pessoa. O que nao garante: quem tem acesso
-- ao registro binario do banco ou ao registro de acesso do servidor web ve
-- as duas gravacoes na mesma requisicao (pendencia 36).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS evento_pesquisa_respostas (
    resposta_uid CHAR(32) NOT NULL,
    pergunta_id INT UNSIGNED NOT NULL,
    valor_numero SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    valor_texto TEXT NULL,
    respondido_em DATE NOT NULL,
    PRIMARY KEY (resposta_uid, pergunta_id, valor_numero),
    INDEX idx_evento_pesquisa_respostas_pergunta (pergunta_id, valor_numero),
    CONSTRAINT fk_evento_pesquisa_respostas_pergunta FOREIGN KEY (pergunta_id) REFERENCES evento_pesquisa_perguntas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- BLOCO E (correcao da pendencia 33): desfazer uma presenca registrada por
-- engano.
--
-- Ate' aqui, evento_checkins (Fase 47) nao tinha nenhum caminho de
-- correcao pela interface: presenca lida por engano ficava para sempre, e
-- com os bonus desta fase ela passou a poder gerar credito de pontos.
--
-- Marcacao, nunca DELETE, pelo mesmo motivo escrito na migration 142 para
-- evento_atividade_facilitadores: a presenca alimenta a coluna "Categoria"
-- das duas exportacoes EJURR, que podem ser geradas a qualquer momento,
-- inclusive depois da atividade; apagar de verdade tornaria um arquivo ja'
-- entregue irreproduzivel. A propria 142 cita evento_checkins como
-- precedente de preservacao historica, e aqui a coerencia se fecha.
--
-- A chave UNIQUE (atividade_id, evento_inscricao_id) impediria a pessoa de
-- confirmar presenca outra vez depois de uma remocao por engano, entao
-- EventoCheckinRepository::registrar() REATIVA a linha marcada em vez de
-- inserir outra, no molde de EventoAtividadeFacilitadorRepository::criar().
--
-- motivo_remocao com 500 caracteres, o mesmo tamanho das justificativas de
-- anulacao das Fases 56 e 57: e' texto que o participante le.
--
-- ALTER puramente aditivo, numa tabela do Evento. Nenhuma coluna existente
-- muda de tipo e nenhum dado e' reescrito.
--
-- Ele fica no FIM do arquivo de proposito, pelo mesmo motivo registrado na
-- migration 182: comando de definicao de dados no MySQL confirma sozinho, e
-- a transacao que database/migrate.php abre nao o cobre. Os sete CREATE
-- TABLE acima usam IF NOT EXISTS e podem rodar de novo sem dano; o ALTER
-- nao pode (o MySQL 5.7 nao aceita IF NOT EXISTS em ADD COLUMN). Com ele
-- por ultimo, uma falha em qualquer ponto anterior deixa o arquivo inteiro
-- reexecutavel.
-- ---------------------------------------------------------------------
ALTER TABLE evento_checkins
    ADD COLUMN removido_em DATETIME NULL AFTER modalidade_acesso,
    ADD COLUMN removido_por INT UNSIGNED NULL AFTER removido_em,
    ADD COLUMN motivo_remocao VARCHAR(500) NULL AFTER removido_por,
    ADD CONSTRAINT fk_evento_checkins_removido_por FOREIGN KEY (removido_por) REFERENCES usuarios (id);
