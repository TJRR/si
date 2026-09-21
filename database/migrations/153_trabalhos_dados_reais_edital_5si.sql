-- Fase 49: dados reais do edital que rege a submissao de Trabalhos da 5a
-- Semana de Inovacao (EDITAL NPI/5SIPJRR N. [XX], DE 21 DE SETEMBRO DE
-- 2026), transcritos da minuta
-- /home/f3011432/Documentos/NPI/SGSI/MinutaEditalSubmissaoTrabalhos5aSemanaInovacao.pdf,
-- relida por completo antes desta migration para conferencia palavra por
-- palavra (o usuario vai rodar esta migration tambem em producao). evento
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
(1, 'Eixo 3 | Desafios da Amazônia', 'Trabalhos que tratem de questões de sustentabilidade, desenvolvimento regional, diversidade, territórios, fronteiras, desafios amazônicos, migração, povos originários e comunidades tradicionais.', 2),
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
SET @criterios_resumo_html = '<p dir="ltr" style="line-height:1.2;margin-top:15pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7 DA AVALIAÇÃO E SELEÇÃO</span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.1 A avaliação será realizada </span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">às cegas</span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">, com base exclusivamente na versão não identificada, e cada trabalho </span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:#ffff00;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;"></span><span style="background-color: rgb(255, 255, 255);"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:#ffff00;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">será analisado por 2 (dois) avaliadores</span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">.</span></span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;"></span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.2 Cada trabalho receberá nota de 0 (zero) a 10 (dez), resultante da soma dos seguintes critérios, pontuados de 0 (zero) a 2 (dois) cada:</span></p><p dir="ltr" style="line-height:1.5;margin-left: 21.25pt;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">a) aderência ao eixo temático e aos objetivos do evento;</span></p><p dir="ltr" style="line-height:1.5;margin-left: 21.25pt;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">b) originalidade e caráter inovador da abordagem;</span></p><p dir="ltr" style="line-height:1.5;margin-left: 21.25pt;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">c) relevância e potencial de contribuição para o setor público, o sistema de justiça ou a sociedade;</span></p><p dir="ltr" style="line-height:1.5;margin-left: 21.25pt;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">d) consistência metodológica e fundamentação teórica;</span></p><p dir="ltr" style="line-height:1.5;margin-left: 21.25pt;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">e) clareza, organização do texto e observância das normas de formatação.</span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.3 A nota final corresponderá à média aritmética das notas atribuídas pelos avaliadores.</span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.4 Somente serão considerados aprovados os trabalhos que obtiverem </span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">nota final superior a 6,0 (seis)</span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">.</span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.5 Entre os trabalhos aprovados, serão selecionados para apresentação os </span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:700;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">24 (vinte e quatro) com maior pontuação</span><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">.</span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.6 Em caso de empate, serão utilizados, sucessivamente, os seguintes critérios: maior pontuação no critério da alínea “b”, maior pontuação no critério da alínea “c”, maior pontuação no critério da alínea “a” e data e horário de submissão mais antigos.</span></p><p><span id="docs-internal-guid-e431cb63-7fff-4ba9-8686-d3c7e2c2c11f"></span></p><p dir="ltr" style="line-height:1.5;text-align: justify;margin-top:0pt;margin-bottom:6pt;"><span style="font-size:11pt;font-family:Arial,sans-serif;color:#000000;background-color:transparent;font-weight:400;font-style:normal;font-variant:normal;text-decoration:none;vertical-align:baseline;white-space:pre;white-space:pre-wrap;">7.7 Os avaliadores poderão indicar ajustes pontuais de forma a serem incorporados à versão para publicação, sem alteração do mérito do trabalho.</span></p>';

INSERT INTO evento_trabalhos_config (
    evento_id, data_abertura_submissao, data_fim_submissao, data_inicio_avaliacao, data_fim_avaliacao,
    quantidade_maxima_autores, permite_multiplos_trabalhos_por_pessoa, quantidade_avaliadores_por_trabalho,
    sigilo_cego, metodo_agregacao_nota, metodos_submissao_json, extensoes_editavel_json, tamanho_maximo_mb,
    exige_telefone_contato, nota_corte_aprovacao, regra_selecao_tipo, regra_selecao_valor, status,
    criterios_resumo_html
) VALUES (
    1, '2026-09-21 00:00:00', '2026-10-16 23:59:00', '2026-10-19 00:00:00', '2026-10-26 23:59:00',
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
