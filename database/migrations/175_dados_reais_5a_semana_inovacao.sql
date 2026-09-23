-- Fase 49, revisado na Fase 51: dados reais do edital que rege a submissao
-- de Trabalhos da 5a Semana de Inovacao. A transcricao original saiu da
-- minuta; em 22/09/2026 o edital foi publicado como EDITAL NPI N. 10, DE 22
-- DE SETEMBRO DE 2026 (SEI 0020809-46.2026.8.23.8000, documento 2927999,
-- arquivo /home/f3011432/Documentos/NPI/SGSI/5SI_Edital_Submissao_Trabalhos.pdf),
-- e o texto publicado foi conferido frase a frase contra a minuta: mudaram a
-- data de abertura das submissoes (21 para 22/09), a descricao do Eixo 3 e a
-- redacao do item 7.5 (de "os 24 com maior pontuacao" para "ate 24, por
-- ordem decrescente de pontuacao"). Eixos 1, 2 e 4, naturezas, criterios,
-- nota de corte e cascata de desempate seguem identicos. evento
-- id 1 e' o unico evento real do sistema (semeado com id explicito pela
-- migration 119), por isso as referencias a evento_id = 1 abaixo sao
-- seguras em qualquer ambiente.
--
-- Sobrescreve (DELETE + INSERT) qualquer configuracao/catalogo anterior
-- do evento 1 nas tabelas abaixo, a pedido explicito do usuario - nao e'
-- valor fixo do motor, continua 100% editavel depois pelas telas
-- administrativas (Trabalhos > Configuracoes/Criterios), e uma edicao
-- futura que reaproveite este motor recebe sua propria migration/tela,
-- nunca herda estes valores.

DELETE FROM trabalho_regras_desempate WHERE evento_id = 1;
DELETE FROM trabalho_criterios WHERE evento_id = 1;
DELETE FROM trabalho_eixos_tematicos WHERE evento_id = 1;
DELETE FROM trabalho_naturezas WHERE evento_id = 1;

-- Eixos tematicos (item 4.1 do edital).
INSERT INTO trabalho_eixos_tematicos (evento_id, nome, descricao, ordem) VALUES
(1, 'Eixo 1 | Tecnologia, Algoritmos e Inteligência Artificial', 'Trabalhos que abordem o uso de inteligência artificial, ciência de dados, algoritmos e automação na transformação digital de organizações públicas e privadas, bem como seus reflexos na cultura digital, na gestão da informação e na prestação de serviços à sociedade.', 0),
(1, 'Eixo 2 | Ética e Direitos Fundamentais', 'Trabalhos que discutam questões de ética, privacidade e proteção de dados, acesso à Justiça, direitos fundamentais, acessibilidade, inclusão, cidadania e suas interseccionalidades.', 1),
(1, 'Eixo 3 | Desafios da Amazônia', 'Trabalhos que tratem de questões próprias da realidade amazônica, como sustentabilidade, desenvolvimento regional, diversidade, territórios, dinâmicas de fronteira e migração, além dos direitos, saberes e protagonismo dos povos originários e das comunidades tradicionais.', 2),
(1, 'Eixo 4 | Inovação, Sociedade e Transformação', 'Trabalhos que explorem a inovação como vetor de transformação social e organizacional, abrangendo inovação pública e social, gestão, organizações, trabalho, educação, ciência, cultura, comunicação, criatividade e empreendedorismo.', 3);

-- Naturezas do trabalho (item 5.5 do edital).
INSERT INTO trabalho_naturezas (evento_id, nome, descricao, ordem) VALUES
(1, 'Relato de experiência', 'Descrição analítica de prática, projeto, ferramenta ou método implementado, com contexto, desafios, resultados e lições aprendidas.', 0),
(1, 'Resultado de pesquisa', 'Estudo concluído ou em estágio avançado, com metodologia científica definida, análise de dados e discussão de resultados.', 1),
(1, 'Reflexão teórica', 'Ensaio de caráter conceitual que promova análise crítica, proposição ou integração de teorias e modelos relacionados aos eixos temáticos.', 2),
(1, 'Proposta de solução', 'Apresentação de ideia, protótipo ou modelo de intervenção voltado a problema concreto, com fundamentação e indicação de viabilidade.', 3);

-- Criterios de avaliacao (item 7.2 do edital): 0 a 2 pontos cada, soma
-- ate 10 - nomes curtos (rotulo de tela) com a redacao completa do edital
-- na descricao.
INSERT INTO trabalho_criterios (evento_id, nome, descricao, nota_maxima, ordem) VALUES (1, 'Aderência ao eixo temático', 'Aderência ao eixo temático e aos objetivos do evento.', 2.00, 0);
SET @criterio_a = LAST_INSERT_ID();
INSERT INTO trabalho_criterios (evento_id, nome, descricao, nota_maxima, ordem) VALUES (1, 'Originalidade', 'Originalidade e caráter inovador da abordagem.', 2.00, 1);
SET @criterio_b = LAST_INSERT_ID();
INSERT INTO trabalho_criterios (evento_id, nome, descricao, nota_maxima, ordem) VALUES (1, 'Relevância', 'Relevância e potencial de contribuição para o setor público, o sistema de justiça ou a sociedade.', 2.00, 2);
SET @criterio_c = LAST_INSERT_ID();
INSERT INTO trabalho_criterios (evento_id, nome, descricao, nota_maxima, ordem) VALUES (1, 'Consistência metodológica', 'Consistência metodológica e fundamentação teórica.', 2.00, 3);
SET @criterio_d = LAST_INSERT_ID();
INSERT INTO trabalho_criterios (evento_id, nome, descricao, nota_maxima, ordem) VALUES (1, 'Clareza e formatação', 'Clareza, organização do texto e observância das normas de formatação.', 2.00, 4);
SET @criterio_e = LAST_INSERT_ID();

-- Desempate em cascata (item 7.6 do edital, nesta ordem exata): maior
-- pontuação no critério "originalidade" (alínea b), depois "relevância"
-- (alínea c), depois "aderência ao eixo temático" (alínea a), depois data
-- e horário de submissão mais antigos.
INSERT INTO trabalho_regras_desempate (evento_id, tipo, criterio_id, ordem, direcao) VALUES
(1, 'criterio', @criterio_b, 0, 'desc'),
(1, 'criterio', @criterio_c, 1, 'desc'),
(1, 'criterio', @criterio_a, 2, 'desc'),
(1, 'data_submissao', NULL, 3, 'asc');

-- Configuracao do processo (itens 1.3, 3.3, 3.4, 5.6, 7 e 11 do edital).
-- metodos_submissao_json/extensoes_editavel_json seguem o item 5.6 ao pe'
-- da letra (2 arquivos Word, .doc ou .docx); tamanho_maximo_mb nao e'
-- especificado no edital, mantido no padrao do sistema (15MB), editavel
-- a qualquer momento pela tela de Configuracoes sem precisar de nova
-- migration.
-- Fase 49B (achado do usuario): criterios_resumo_html e' o texto que o
-- avaliador consulta dentro da tela de avaliacao (painel "Consulta ao
-- edital"). Texto real cadastrado pelo usuario pela propria tela admin
-- (aba "Criterios de avaliacao"), transcricao literal do item 7 "DA
-- AVALIACAO E SELECAO" do edital, colado do editor de origem (mantem a
-- formatacao rica original) - nunca montado em tempo de exibicao a
-- partir de trabalho_criterios (decisao explicita do usuario): o valor
-- mora aqui, pronto, e a tela so' le.
SET @criterios_resumo_html = '<p dir="ltr" style="line-height:1.2;margin-top:15pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7 DA AVALIAÇÃO E SELEÇÃO</span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.1 A avaliação será realizada </span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">às cegas</span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">, com base exclusivamente na versão não identificada, e cada trabalho </span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:#ffff00;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;"></span><span style="background-color: rgb(255, 255, 255);"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:#ffff00;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">será analisado por 2 (dois) avaliadores</span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">.</span></span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;"></span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.2 Cada trabalho receberá nota de 0 (zero) a 10 (dez), resultante da soma dos seguintes critérios, pontuados de 0 (zero) a 2 (dois) cada:</span></p><p dir="ltr" style="line-height:1.5;margin-left: 21.25pt;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">a) aderência ao eixo temático e aos objetivos do evento;</span></p><p dir="ltr" style="line-height:1.5;margin-left: 21.25pt;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">b) originalidade e caráter inovador da abordagem;</span></p><p dir="ltr" style="line-height:1.5;margin-left: 21.25pt;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">c) relevância e potencial de contribuição para o setor público, o sistema de justiça ou a sociedade;</span></p><p dir="ltr" style="line-height:1.5;margin-left: 21.25pt;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">d) consistência metodológica e fundamentação teórica;</span></p><p dir="ltr" style="line-height:1.5;margin-left: 21.25pt;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">e) clareza, organização do texto e observância das normas de formatação.</span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.3 A nota final corresponderá à média aritmética das notas atribuídas pelos avaliadores.</span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.4 Somente serão considerados aprovados os trabalhos que obtiverem </span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">nota final superior a 6,0 (seis)</span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">.</span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.5 Entre os trabalhos aprovados, serão selecionados para apresentação </span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">até 24 (vinte e quatro)</span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">, por ordem decrescente de pontuação.</span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.6 Em caso de empate, serão utilizados, sucessivamente, os seguintes critérios: maior pontuação no critério da alínea “b”, maior pontuação no critério da alínea “c”, maior pontuação no critério da alínea “a” e data e horário de submissão mais antigos.</span></p><p><span id="docs-internal-guid-e431cb63-7fff-4ba9-8686-d3c7e2c2c11f"></span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.7 Os avaliadores poderão indicar ajustes pontuais de forma a serem incorporados à versão para publicação, sem alteração do mérito do trabalho.</span></p>';

INSERT INTO evento_trabalhos_config (
    evento_id, data_abertura_submissao, data_fim_submissao, data_inicio_avaliacao, data_fim_avaliacao,
    quantidade_maxima_autores, permite_multiplos_trabalhos_por_pessoa, quantidade_avaliadores_por_trabalho,
    sigilo_cego, metodo_agregacao_nota, metodos_submissao_json, extensoes_editavel_json, tamanho_maximo_mb,
    exige_telefone_contato, nota_corte_aprovacao, regra_selecao_tipo, regra_selecao_valor, status,
    criterios_resumo_html
) VALUES (
    1, '2026-09-22 00:00:00', '2026-10-16 23:59:00', '2026-10-19 00:00:00', '2026-10-26 23:59:00',
    2, 0, 2,
    1, 'media_aritmetica', '["documento_editavel"]', '["doc","docx"]', 15,
    1, 6.00, 'numero_fixo', 24, 'publicado',
    @criterios_resumo_html
) ON DUPLICATE KEY UPDATE
    data_abertura_submissao = VALUES(data_abertura_submissao),
    data_fim_submissao = VALUES(data_fim_submissao),
    data_inicio_avaliacao = VALUES(data_inicio_avaliacao),
    data_fim_avaliacao = VALUES(data_fim_avaliacao),
    quantidade_maxima_autores = VALUES(quantidade_maxima_autores),
    permite_multiplos_trabalhos_por_pessoa = VALUES(permite_multiplos_trabalhos_por_pessoa),
    quantidade_avaliadores_por_trabalho = VALUES(quantidade_avaliadores_por_trabalho),
    sigilo_cego = VALUES(sigilo_cego),
    metodo_agregacao_nota = VALUES(metodo_agregacao_nota),
    metodos_submissao_json = VALUES(metodos_submissao_json),
    extensoes_editavel_json = VALUES(extensoes_editavel_json),
    tamanho_maximo_mb = VALUES(tamanho_maximo_mb),
    exige_telefone_contato = VALUES(exige_telefone_contato),
    nota_corte_aprovacao = VALUES(nota_corte_aprovacao),
    regra_selecao_tipo = VALUES(regra_selecao_tipo),
    regra_selecao_valor = VALUES(regra_selecao_valor),
    status = VALUES(status),
    criterios_resumo_html = VALUES(criterios_resumo_html);

-- ---------------------------------------------------------------------------
-- Fase 51: conteudo real da pagina publica da 5a Semana de Inovacao.
--
-- Tudo abaixo e' dado de conteudo, nao regra de motor: sai do edital n. 10 e
-- da especificacao aprovada pela equipe, e continua 100% editavel pelas telas
-- (Evento > Cabecalho/Quadros/Faixas/Blocos/Secoes da pagina). Uma edicao
-- futura recebe a sua propria migration ou cadastra pelas telas, nunca herda
-- estes valores.
--
-- O que NAO entra aqui, de proposito: imagens (a pasta de envios nao e'
-- versionada, entao logo, fundos e arte decorativa sao enviados pelo Admin
-- depois, ver DeployFase51.md) e os codigos de presenca das atividades (sao
-- gerados na execucao, nunca escritos em arquivo de um repositorio publico).
-- A pagina tambem nasce NAO publicada: quem publica e' o Admin, na aba
-- Cabecalho, depois de conferir o conteudo.
-- ---------------------------------------------------------------------------

INSERT INTO evento_configuracao_visual (evento_id, publicado, cabecalho_titulo_html, quadros_avanco_automatico)
VALUES (1, 0, '<p>5ª Semana de Inovação do Poder Judiciário de Roraima</p>', 0)
ON DUPLICATE KEY UPDATE quadros_avanco_automatico = VALUES(quadros_avanco_automatico);

-- Tipos de atividade (etiquetas coloridas da programacao).
DELETE FROM evento_atividade_tipos WHERE evento_id = 1;
INSERT INTO evento_atividade_tipos (evento_id, nome, cor, ordem) VALUES
(1, 'Oficina', '#006699', 0),
(1, 'Palestra', '#ea5a43', 1),
(1, 'Sessão solene', '#141413', 2),
(1, 'Cultural', '#cbd744', 3),
(1, 'Experiência', '#8e44ad', 4),
(1, 'Apresentação', '#0f8a5f', 5),
(1, 'Sessão de banners', '#b8860b', 6);

-- Quadros de apresentacao (carrossel), item 3 da especificacao.
DELETE FROM evento_slides WHERE evento_id = 1;
INSERT INTO evento_slides
    (evento_id, cor_fundo, duracao_ms, efeito_transicao, etiqueta_texto, etiqueta_cor_fundo, etiqueta_cor_texto,
     titulo_html, cta_titulo, cta_link, cta_cor_fundo, cta_cor_texto, cta2_titulo, cta2_link, ordem, ativo)
VALUES
(1, '#cbd744', 9000, 'fade', 'ABERTURA · 04/11', '#141413', '#ffffff',
 '<p>A inovação continua transformando a forma de fazer Justiça.</p><p>Venha experimentar, aprender e cocriar soluções que unem tecnologia, inovação e humanização para uma Justiça mais próxima do cidadão.</p>',
 'Confira a programação', '#secao-programacao', '#141413', '#ffffff', NULL, NULL, 0, 1),
(1, '#cbd744', 9000, 'fade', 'PRAZO ATÉ 16/10', '#ea5a43', '#ffffff',
 '<p>Submeta seu trabalho para a 5ª Semana de Inovação</p><p>Resumos expandidos aprovados são apresentados em banner durante o evento e publicados nos Anais digitais.</p>',
 'Enviar meu trabalho', 'trabalho/formulario/1', '#141413', '#ffffff', NULL, NULL, 1, 1),
(1, '#cbd744', 9000, 'fade', '05/11 · 10H30', '#006699', '#ffffff',
 '<p>Conheça os vencedores do 5º Prêmio de Inovação</p><p>Divulgação do resultado e apresentação dos três primeiros colocados.</p>',
 'Ver destaques do dia', '#secao-destaques', '#141413', '#ffffff', NULL, NULL, 2, 1),
(1, '#cbd744', 9000, 'fade', 'ATIVIDADES', '#141413', '#ffffff',
 '<p>Karaokê, Batalha de Prompts e muito mais</p><p>Duas manhãs e duas tardes de aprendizado, troca e diversão na EJURR.</p>',
 'Ver a programação', '#secao-programacao', '#141413', '#ffffff', 'Inscreva-se', 'eventoInscricao/index/1', 3, 1);

-- Faixa de respiro visual (item 4 da especificacao).
DELETE FROM evento_banners WHERE evento_id = 1;
INSERT INTO evento_banners (evento_id, cor_fundo, conteudo_html, conteudo_alinhamento, ordem, ativo)
VALUES (1, '#ffffff',
 '<p><strong>TECNOLOGIA E INOVAÇÃO: ENTRE ALGORITMOS E EMPATIA</strong></p><p>5ª Semana de Inovação do Poder Judiciário de Roraima · NPI</p>',
 'centro', 0, 1);

-- Componentes da pagina. Cada instancia guarda o proprio id numa variavel,
-- porque a ordem da pagina (evento_secoes_ordem, no fim deste arquivo)
-- precisa apontar para eles.
DELETE FROM evento_secoes_ordem WHERE evento_id = 1;
DELETE FROM evento_secao_contagem WHERE evento_id = 1;
DELETE FROM evento_secao_cronograma WHERE evento_id = 1;
DELETE FROM evento_secao_cartoes WHERE evento_id = 1;
DELETE FROM evento_secao_destaques WHERE evento_id = 1;
DELETE FROM evento_secao_programacao WHERE evento_id = 1;
DELETE FROM evento_secao_faq WHERE evento_id = 1;
DELETE FROM evento_secao_local WHERE evento_id = 1;
DELETE FROM evento_blocos_conteudo WHERE evento_id = 1;

-- Contagem regressiva (item 5 da especificacao).
INSERT INTO evento_secao_contagem (evento_id, etiqueta, titulo, data_alvo, cor_fundo, cor_texto, cor_circulo)
VALUES (1, 'CONTAGEM REGRESSIVA', 'Faltam poucos dias para a 5ª Semana de Inovação', '2026-11-04 08:30:00', '#f4f7d9', '#141413', '#141413');
SET @secao_contagem = LAST_INSERT_ID();

INSERT INTO evento_secao_contagem_itens (secao_id, texto, data_referencia, cor_marcador, ordem) VALUES
(@secao_contagem, 'Submissões abertas até', '2026-10-16', '#ea5a43', 0),
(@secao_contagem, 'Início da Semana de Inovação', '2026-11-04', '#006699', 1),
(@secao_contagem, 'Entrega do Prêmio de Inovação', '2026-11-06', '#cbd744', 2);

-- Bloco de destaque da submissao (item 6 da especificacao).
INSERT INTO evento_blocos_conteudo
    (evento_id, titulo, conteudo_html, cor_fundo, cor_texto, cta_titulo, cta_link, cta_alinhamento, cta2_titulo, cta2_link, secao_ancora, ordem, ativo)
VALUES (1, 'Submeta seu trabalho para a 5ª Semana de Inovação',
 '<p>A submissão é aberta a qualquer pessoa interessada, do quadro do Tribunal ou não. Os trabalhos aprovados são apresentados presencialmente em banner e publicados nos Anais digitais do evento.</p><p>Dúvidas: npi@tjrr.jus.br</p>',
 '#ea5a43', '#141413', 'Enviar meu trabalho', 'trabalho/formulario/1', 'esquerda', NULL, NULL, 'submissao', 0, 1);
SET @bloco_submissao = LAST_INSERT_ID();

-- Cronograma de submissao (item 11 do edital).
INSERT INTO evento_secao_cronograma (evento_id, etiqueta, titulo, cor_fundo, cor_texto)
VALUES (1, 'EDITAL NPI Nº 10/2026', 'Cronograma de submissão', '#ffffff', '#141413');
SET @secao_cronograma = LAST_INSERT_ID();

INSERT INTO evento_secao_cronograma_itens (secao_id, periodo_texto, descricao, data_referencia, cor, ordem) VALUES
(@secao_cronograma, '22/09/2026', 'Publicação do edital e abertura das submissões', '2026-09-22', '#cbd744', 0),
(@secao_cronograma, '16/10/2026, até 23h59', 'Prazo final para submissão dos trabalhos', '2026-10-16', '#ea5a43', 1),
(@secao_cronograma, '19 a 26/10/2026', 'Avaliação dos trabalhos', '2026-10-19', '#006699', 2),
(@secao_cronograma, '27/10/2026', 'Divulgação do resultado e do modelo de banner', '2026-10-27', '#006699', 3),
(@secao_cronograma, 'Até 03/11/2026', 'Confirmação de participação pelos autores aprovados', '2026-11-03', '#141413', 4),
(@secao_cronograma, '04 a 06/11/2026', 'Apresentação dos banners durante o evento', '2026-11-04', '#cbd744', 5);

-- Bloco "Sobre" (item 7 da especificacao).
INSERT INTO evento_blocos_conteudo
    (evento_id, titulo, conteudo_html, cor_fundo, cor_texto, cta_alinhamento, secao_ancora, ordem, ativo)
VALUES (1, 'Sobre a Semana de Inovação',
 '<p>Credenciamento, atividades, trabalho em rede e submissão de artigo científico. A iniciativa do Núcleo de Projetos e Inovação busca estimular a produção e o compartilhamento de conhecimento sobre inovação no setor público, aproximando o sistema de justiça da academia, de outras instituições e da sociedade.</p>',
 '#ffffff', '#141413', 'esquerda', 'sobre', 1, 1);
SET @bloco_sobre = LAST_INSERT_ID();

-- Cartoes dos eixos tematicos: cada cartao aponta para o eixo ja cadastrado,
-- entao o texto completo continua vindo de um lugar so'.
INSERT INTO evento_secao_cartoes (evento_id, etiqueta, titulo, colunas, efeito_hover, efeito_abrir, efeito_fechar, cor_fundo, cor_texto)
VALUES (1, 'EIXOS TEMÁTICOS', 'Quatro caminhos para o seu trabalho', 4, 'elevar', 'deslizar', 'deslizar', '#ffffff', '#141413');
SET @secao_cartoes = LAST_INSERT_ID();

INSERT INTO evento_secao_cartoes_itens (secao_id, eixo_tematico_id, etiqueta, titulo, resumo, cor, ordem)
SELECT @secao_cartoes, e.id, 'EIXO 1', 'Tecnologia, Algoritmos e IA',
       'Inteligência artificial, ciência de dados e automação na transformação digital.', '#006699', 0
FROM trabalho_eixos_tematicos e WHERE e.evento_id = 1 AND e.nome LIKE 'Eixo 1%' LIMIT 1;

INSERT INTO evento_secao_cartoes_itens (secao_id, eixo_tematico_id, etiqueta, titulo, resumo, cor, ordem)
SELECT @secao_cartoes, e.id, 'EIXO 2', 'Ética e Direitos Fundamentais',
       'Privacidade, proteção de dados, acesso à Justiça, acessibilidade e inclusão.', '#ea5a43', 1
FROM trabalho_eixos_tematicos e WHERE e.evento_id = 1 AND e.nome LIKE 'Eixo 2%' LIMIT 1;

INSERT INTO evento_secao_cartoes_itens (secao_id, eixo_tematico_id, etiqueta, titulo, resumo, cor, ordem)
SELECT @secao_cartoes, e.id, 'EIXO 3', 'Desafios da Amazônia',
       'Sustentabilidade, territórios, povos originários e comunidades tradicionais.', '#0f8a5f', 2
FROM trabalho_eixos_tematicos e WHERE e.evento_id = 1 AND e.nome LIKE 'Eixo 3%' LIMIT 1;

INSERT INTO evento_secao_cartoes_itens (secao_id, eixo_tematico_id, etiqueta, titulo, resumo, cor, ordem)
SELECT @secao_cartoes, e.id, 'EIXO 4', 'Inovação, Sociedade e Transformação',
       'Inovação pública e social, gestão, educação, cultura e empreendedorismo.', '#cbd744', 3
FROM trabalho_eixos_tematicos e WHERE e.evento_id = 1 AND e.nome LIKE 'Eixo 4%' LIMIT 1;

-- Destaques da programacao (item 8 da especificacao). Modo digitado: as
-- Atividades ainda serao cadastradas pela equipe, e a pagina nao pode
-- depender disso para ir ao ar. Depois de cadastradas, basta trocar a fonte
-- da secao para "Atividades" na tela.
INSERT INTO evento_secao_destaques (evento_id, etiqueta, titulo, fonte, colunas, cor_fundo, cor_texto)
VALUES (1, 'NÃO PERCA', 'Destaques da programação', 'itens', 4, '#ffffff', '#141413');
SET @secao_destaques = LAST_INSERT_ID();

INSERT INTO evento_secao_destaques_itens (secao_id, titulo, quando_texto, local, descricao, ordem) VALUES
(@secao_destaques, '5º Prêmio de Inovação', '05/11 · 10h30 às 12h30', 'Sala 415 EJURR', 'Divulgação do resultado e apresentação dos três primeiros colocados.', 0),
(@secao_destaques, 'Karaokê', '05/11 · 10h às 10h30', 'Hall da EJURR', 'Experiência para descontrair entre as atividades da manhã.', 1),
(@secao_destaques, 'Batalha de Prompts', '05/11 · 16h às 16h30', 'Hall da EJURR', 'Desafio prático de inteligência artificial entre participantes.', 2),
(@secao_destaques, 'Sessão Solene de Abertura', '04/11 · a partir das 16h', NULL, 'Lançamento do Projeto de Inteligência Artificial do Comitê de IA.', 3);

-- Programacao completa (item 9 da especificacao), tambem no modo digitado.
INSERT INTO evento_secao_programacao (evento_id, etiqueta, titulo, fonte, mostrar_local, cor_fundo, cor_texto)
VALUES (1, 'PROGRAMAÇÃO', 'Confira a programação completa', 'itens', 1, '#ffffff', '#141413');
SET @secao_programacao = LAST_INSERT_ID();

INSERT INTO evento_secao_programacao_itens (secao_id, dia, turno, horario_texto, tipo_texto, titulo, local, ordem) VALUES
(@secao_programacao, '2026-11-04', 'manha', '08h30 às 12h30', 'Oficina', 'Desmistificando a IA: ferramentas e aplicações práticas para o cotidiano jurídico', 'Laboratório de Informática da EJURR', 0),
(@secao_programacao, '2026-11-04', 'manha', '08h30 às 12h30', 'Oficina', 'Justiça além do Tribunal: inovação aberta', 'Laboratório Inovajurr', 1),
(@secao_programacao, '2026-11-04', 'tarde', 'a partir das 16h', 'Sessão solene', 'Abertura e lançamento do Projeto de Inteligência Artificial', NULL, 2),
(@secao_programacao, '2026-11-04', 'tarde', 'a partir das 16h', 'Palestra', 'Inovação aberta e colaboração: construindo a Justiça do futuro juntos', NULL, 3),
(@secao_programacao, '2026-11-04', 'tarde', 'a partir das 16h', 'Cultural', 'Apresentação cultural e coffee break', 'Área externa do auditório do Fórum Cível', 4),
(@secao_programacao, '2026-11-05', 'manha', '08h30 às 10h', 'Palestra', 'A ética dos algoritmos: garantindo imparcialidade e transparência na Justiça digital', 'Sala 415 EJURR', 5),
(@secao_programacao, '2026-11-05', 'manha', '10h às 10h30', 'Experiência', 'Karaokê', 'Hall da EJURR', 6),
(@secao_programacao, '2026-11-05', 'manha', '10h30 às 12h30', 'Apresentação', 'Divulgação do resultado do 5º Prêmio de Inovação', 'Sala 415 EJURR', 7),
(@secao_programacao, '2026-11-05', 'tarde', '14h às 18h', 'Palestra', 'Design de serviços centrado no usuário: construindo soluções com empatia', 'Laboratório Inovajurr', 8),
(@secao_programacao, '2026-11-05', 'tarde', '14h às 16h', 'Palestra', 'Agentes de IA: eficiência e o papel do humano na automatização de fluxos de trabalho', 'Sala 415 EJURR', 9),
(@secao_programacao, '2026-11-05', 'tarde', '16h às 16h30', 'Experiência', 'Batalha de Prompts', 'Hall da EJURR', 10),
(@secao_programacao, '2026-11-05', 'tarde', '16h às 18h', 'Palestra', 'Cocriando o futuro: como as parcerias estratégicas constroem a inovação', 'Sala 415 EJURR', 11),
(@secao_programacao, '2026-11-06', 'manha', 'turno matutino', 'Sessão de banners', 'Apresentação dos trabalhos aprovados, conforme escala divulgada junto ao resultado', 'Sede Administrativa', 12);

-- Local e acesso (item 10 da especificacao). O endereco de incorporacao do
-- mapa fica em branco: quem cadastra escolhe se quer mapa de terceiros na
-- pagina, pela tela.
INSERT INTO evento_secao_local (evento_id, etiqueta, titulo, endereco, descricao_html, cor_fundo, cor_texto)
VALUES (1, 'LOCAL E ACESSO', 'Sede Administrativa e EJURR',
 'Av. Cap. Ene Garcez, nº 1696, Bairro Centro, Boa Vista/RR',
 '<p>Contato: npi@tjrr.jus.br</p>', '#cbd744', '#141413');
SET @secao_local = LAST_INSERT_ID();

-- Perguntas frequentes (item 11 da especificacao).
INSERT INTO evento_secao_faq (evento_id, etiqueta, titulo, cor_fundo, cor_texto)
VALUES (1, 'DÚVIDAS FREQUENTES', 'Perguntas frequentes', '#ffffff', '#141413');
SET @secao_faq = LAST_INSERT_ID();

INSERT INTO evento_secao_faq_itens (secao_id, pergunta, resposta_html, ativo, ordem) VALUES
(@secao_faq, 'Quem pode submeter trabalhos?', '<p>Qualquer pessoa interessada, pertencente ou não ao quadro do Tribunal, conforme o item 3.1 do edital.</p>', 1, 0),
(@secao_faq, 'A participação tem algum custo?', '<p>Não. A submissão e a participação no evento são gratuitas.</p>', 1, 1),
(@secao_faq, 'Como funciona a apresentação em banner?', '<p>Os trabalhos aprovados são apresentados presencialmente, em sessão de banners no turno matutino, entre 4 e 6 de novembro, conforme escala divulgada junto ao resultado. Ao menos um dos autores permanece junto ao banner.</p>', 1, 2),
(@secao_faq, 'Até quando confirmo minha participação?', '<p>Até 3 de novembro de 2026. A ausência de confirmação é considerada desistência.</p>', 1, 3),
(@secao_faq, 'Com quem falo em caso de dúvida?', '<p>Pelo endereço npi@tjrr.jus.br, conforme o item 12.6 do edital.</p>', 1, 4);

-- Chamada final de inscricao (item 12 da especificacao).
INSERT INTO evento_blocos_conteudo
    (evento_id, titulo, conteudo_html, cor_fundo, cor_texto, cta_titulo, cta_link, cta_alinhamento, secao_ancora, ordem, ativo)
VALUES (1, 'Inscreva-se na 5ª Semana de Inovação',
 '<p>Participação gratuita, aberta ao público em geral.</p>',
 '#141413', '#ffffff', 'Inscreva-se agora', 'eventoInscricao/index/1', 'centro', 'inscricao', 2, 1);
SET @bloco_inscricao = LAST_INSERT_ID();

-- Ordem da pagina, liga/desliga e menu do cabecalho (item 1 da
-- especificacao: a ordem dos blocos, de cima para baixo).
INSERT INTO evento_secoes_ordem (evento_id, tipo, referencia_id, ordem, ativo, mostrar_no_menu, rotulo_menu) VALUES
(1, 'quadros', NULL, 0, 1, 0, NULL),
(1, 'faixas', NULL, 1, 1, 0, NULL),
(1, 'contagem', @secao_contagem, 2, 1, 0, NULL),
(1, 'bloco', @bloco_submissao, 3, 1, 1, 'Submeta seu Trabalho'),
(1, 'cronograma', @secao_cronograma, 4, 1, 0, NULL),
(1, 'bloco', @bloco_sobre, 5, 1, 1, 'Sobre'),
(1, 'cartoes', @secao_cartoes, 6, 1, 0, NULL),
(1, 'destaques', @secao_destaques, 7, 1, 1, 'Prêmio de Inovação'),
(1, 'programacao', @secao_programacao, 8, 1, 1, 'Programação'),
(1, 'local', @secao_local, 9, 1, 0, NULL),
(1, 'faq', @secao_faq, 10, 1, 0, NULL),
(1, 'bloco', @bloco_inscricao, 11, 1, 0, NULL);

-- Declaracoes que o autor aceita ao submeter (Fase 51). A primeira repete o
-- aceite do canal alternativo; as outras duas separam o que o edital trata
-- em itens proprios (tratamento de dados pessoais e publicacao nos Anais).
DELETE FROM evento_trabalho_termos WHERE evento_id = 1;
INSERT INTO evento_trabalho_termos (evento_id, rotulo, texto_html, obrigatorio, ativo, ordem) VALUES
(1, 'Normas do edital e ciência dos coautores',
 '<p>Declaro que li e aceito integralmente as normas do edital, que todos os coautores conhecem e concordam com o conteúdo do trabalho e que a versão para avaliação não contém identificação de autoria.</p>', 1, 1, 0),
(1, 'Autorização de publicação nos Anais',
 '<p>Autorizo, sem ônus, a publicação e a divulgação do trabalho pelo Tribunal, inclusive nos Anais digitais do evento, com o devido crédito aos autores.</p>', 1, 1, 1),
(1, 'Tratamento de dados pessoais',
 '<p>Estou ciente de que os dados pessoais informados serão tratados exclusivamente para as finalidades deste edital, em conformidade com a Lei Federal n. 13.709, de 14 de agosto de 2018 (Lei Geral de Proteção de Dados Pessoais), conforme o item 12.3 do edital.</p>', 1, 1, 2);
