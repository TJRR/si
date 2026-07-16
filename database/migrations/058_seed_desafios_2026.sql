-- Fase 17 (Bug 2): os 33 desafios reais do 5º Prêmio de Inovação TJRR (edição
-- 2026), extraídos literalmente dos PDFs de desafios dos Editais 12/2026
-- (Externo) e 13/2026 (Interno) - ver /home/f3011432/Code/NPI/DesafiosFase17.md
-- para a extração completa e as notas de duplicação intencional entre trilhas.
-- tema_id resolvido por subselect (trilha_id + nome do tema, ja seedados em
-- seed_temas_2026.sql / migration 055), sem depender do numero exato do id.

-- ===================== Trilha Interna (trilha_id = 3) =====================

-- Tema 1 - Processos internos (11 desafios)
INSERT INTO desafios (tema_id, pergunta, ativo)
SELECT id, texto, 1 FROM temas
CROSS JOIN (
    SELECT 'Como podemos viabilizar o gerenciamento e aquisição de passagens aéreas por meio de credenciamento permanente de agências de viagens e companhias aéreas, para centralizar demandas e automatizar a busca, triagem e comparação de passagens em tempo real, garantindo a seleção pelo menor preço, celeridade na emissão e total rastreabilidade para o TJRR?' AS texto
    UNION ALL SELECT 'Como podemos otimizar e automatizar o processo de análise, conferência e validação dos requerimentos de conversão de férias no Setor de Monitoramento de Desempenho, eliminando o gargalo do processamento manual massivo de cerca de 800 pedidos dentro da janela crítica de apenas 4 dias?'
    UNION ALL SELECT 'Como podemos construir uma estratégia que propicie a elaboração, o fomento e o monitoramento contínuo de um banco de dados unificado para taxas de absenteísmo na SGP/SUBGEP, facilitando a integração de informações e o suporte preventivo à qualidade de vida dos servidores do TJRR?'
    UNION ALL SELECT 'Como podemos melhorar o monitoramento e a coleta unificada de dados de sustentabilidade no SSRS, de modo a mitigar o retrabalho no preenchimento de relatórios obrigatórios (PLS, Resolução CNJ nº 400/2021 e Inventário GEE) e viabilizar a rastreabilidade das ações de compensação ambiental no TJRR de maneira automatizada e padronizada?'
    UNION ALL SELECT 'Como podemos automatizar e integrar todo o ciclo de gestão das demandas da Subsecretaria de Sistemas (SubSi), desde o recebimento por diferentes canais (E-mail, SEI e Aranda) até a triagem, registro, implementação, acompanhamento, documentação e geração de informações gerenciais, eliminando atividades manuais repetitivas, aumentando a rastreabilidade e proporcionando inteligência estratégica para a tomada de decisão?'
    UNION ALL SELECT 'Como podemos aumentar o engajamento dos servidores nas ações de conscientização sobre segurança da informação, tornando essas iniciativas mais atrativas, efetivas e capazes de fortalecer a cultura de segurança no Tribunal de Justiça de Roraima?'
    UNION ALL SELECT 'Como podemos modernizar e integrar a gestão dos ativos patrimoniais e de Tecnologia da Informação do TJRR, garantindo rastreabilidade, atualização contínua das movimentações e confiabilidade do inventário institucional, reduzindo processos manuais e retrabalho entre as unidades responsáveis?'
    UNION ALL SELECT 'Como podemos, de forma automatizada e centralizada, mensurar e atribuir os custos de consumo de recursos (VMs, Clusters K8s, Deployments, Pods e Containers) para permitir uma gestão financeira transparente e eficiente da infraestrutura de TI do TJRR?'
    UNION ALL SELECT 'Como podemos propiciar a extração automatizada, integrada e centralizada de relatórios de dados funcionais e remuneratórios na Secretaria de Gestão de Pessoas, eliminando o uso de controles paralelos e otimizando o envio de informações obrigatórias ao Portal da Transparência do TJRR?'
    UNION ALL SELECT 'Como podemos dimensionar e alocar, de forma eficiente, a força de trabalho nas unidades de apoio direto e indireto do TJRR, desenvolvendo uma metodologia institucional integrada a uma ferramenta tecnológica que facilite a distribuição equilibrada de pessoal, cargos e funções, mitigando assimetrias e a sobrecarga de trabalho?'
    UNION ALL SELECT 'Como podemos gerir, de forma preventiva, integrada e sistematizada de informações de risco, os deslocamentos e missões institucionais no GABMIL, unindo inteligência e análise territorial para mitigar a exposição de magistrados e servidores no Estado de Roraima?'
) AS d
WHERE temas.trilha_id = 3 AND temas.nome = 'Processos internos (Tema 1)';

-- Tema 2 - Atividade finalística (4 desafios)
INSERT INTO desafios (tema_id, pergunta, ativo)
SELECT id, texto, 1 FROM temas
CROSS JOIN (
    SELECT 'Como podemos realizar a correição judicial do acervo processual das unidades judiciárias no sistema Projudi, superando as limitações da análise manual por amostragem e padronizando a detecção de movimentações inadequadas ou redundantes?' AS texto
    UNION ALL SELECT 'Como podemos estimular a resolução de conflitos por meios alternativos e reduzir o volume de novas petições, aliviando a carga de trabalho das secretarias e gabinetes para garantir uma resposta mais célere e efetiva ao cidadão?'
    UNION ALL SELECT 'Como podemos construir uma estratégia de automação para a execução de tarefas repetitivas e de baixa complexidade no sistema Projudi, visando dar fluidez ao alto volume de movimentações processuais, reduzir o tempo de tramitação e garantir melhor qualidade de vida e saúde mental para um quadro reduzido de servidores?'
    UNION ALL SELECT 'Como podemos melhorar a transcrição automatizada e prover tradução em tempo real de audiências judiciais multilíngues, visando ampliar o acesso à justiça, reduzir a morosidade processual e eliminar a dependência de serviços externos especializados?'
) AS d
WHERE temas.trilha_id = 3 AND temas.nome = 'Atividade finalística (Tema 2)';

-- Tema 3 - Cidadania e Justiça Inclusiva (2 desafios)
INSERT INTO desafios (tema_id, pergunta, ativo)
SELECT id, texto, 1 FROM temas
CROSS JOIN (
    SELECT 'Como podemos facilitar o processamento dos pedidos de autorização de viagem para menores, feito pelos pais e responsáveis, para que seja menos custoso a eles e ao judiciário?' AS texto
    UNION ALL SELECT 'Como podemos facilitar o acesso aos serviços de Justiça e cidadania para as comunidades do Baixo Rio Branco (atendidas pelas comarcas de Caracaraí e Rorainópolis), reduzindo os custos de deslocamento fluvial e simplificando a jornada do usuário por meio de ferramentas digitais acessíveis?'
) AS d
WHERE temas.trilha_id = 3 AND temas.nome = 'Cidadania e Justiça Inclusiva (Tema 3)';

-- ===================== Trilha Externa (trilha_id = 2) =====================

-- Tema 1 - Otimização e Celeridade da Prestação Jurisdicional (4 desafios)
INSERT INTO desafios (tema_id, pergunta, ativo)
SELECT id, texto, 1 FROM temas
CROSS JOIN (
    SELECT 'Como podemos realizar a correição judicial do acervo processual das unidades judiciárias no sistema Projudi, superando as limitações da análise manual por amostragem e padronizando a detecção de movimentações inadequadas ou redundantes?' AS texto
    UNION ALL SELECT 'Como podemos estimular a resolução de conflitos por meios alternativos e reduzir o volume de novas petições, aliviando a carga de trabalho das secretarias e gabinetes para garantir uma resposta mais célere e efetiva ao cidadão?'
    UNION ALL SELECT 'Como podemos automatizar a execução de tarefas repetitivas e de baixa complexidade no sistema Projudi, visando dar fluidez ao alto volume de movimentações processuais, reduzir o tempo de tramitação e garantir melhor qualidade de vida e saúde mental para um quadro reduzido de servidores?'
    UNION ALL SELECT 'Como podemos melhorar a transcrição automatizada e inserir tradução em tempo real de audiências judiciais multilíngues, visando ampliar o acesso à justiça, reduzir a morosidade processual e eliminar a dependência de serviços externos especializados?'
) AS d
WHERE temas.trilha_id = 2 AND temas.nome = 'Otimização e Celeridade da Prestação Jurisdicional (Tema 1)';

-- Tema 2 - Aprimoramento do Atendimento ao Cidadão e Acesso à Justiça (2 desafios)
INSERT INTO desafios (tema_id, pergunta, ativo)
SELECT id, texto, 1 FROM temas
CROSS JOIN (
    SELECT 'Como podemos facilitar o processamento dos pedidos de autorização de viagem para menores, feito pelos pais e responsáveis, para que seja menos custoso a eles e ao judiciário?' AS texto
    UNION ALL SELECT 'Como podemos facilitar o acesso aos serviços de Justiça e cidadania para as comunidades do Baixo Rio Branco (atendidas pelas comarcas de Caracaraí e Rorainópolis), reduzindo os custos de deslocamento fluvial e simplificando a jornada do usuário por meio de ferramentas digitais acessíveis?'
) AS d
WHERE temas.trilha_id = 2 AND temas.nome = 'Aprimoramento do Atendimento ao Cidadão e Acesso à Justiça (Tema 2)';

-- Tema 3 - Eficiência e Sustentabilidade da Gestão Administrativa (10 desafios)
INSERT INTO desafios (tema_id, pergunta, ativo)
SELECT id, texto, 1 FROM temas
CROSS JOIN (
    SELECT 'Como podemos viabilizar o gerenciamento e aquisição de passagens aéreas por meio de credenciamento permanente de agências de viagens e companhias aéreas, para centralizar demandas e automatizar a busca, triagem e comparação de passagens em tempo real, garantindo a seleção pelo menor preço, celeridade na emissão e total rastreabilidade para o TJRR?' AS texto
    UNION ALL SELECT 'Como podemos propiciar a elaboração, o fomento e o monitoramento contínuo de um banco de dados unificado para taxas de absenteísmo na SGP/SUBGEP, facilitando a integração de informações e o suporte preventivo à qualidade de vida dos servidores do TJRR?'
    UNION ALL SELECT 'Como podemos melhorar o monitoramento e a coleta unificada de dados de sustentabilidade no SSRS, de modo a mitigar o retrabalho no preenchimento de relatórios obrigatórios (PLS, Resolução CNJ nº 400/2021 e Inventário GEE) e viabilizar a rastreabilidade das ações de compensação ambiental no TJRR de maneira automatizada e padronizada?'
    UNION ALL SELECT 'Como podemos automatizar e integrar todo o ciclo de gestão das demandas da Subsecretaria de Sistemas (SubSi), desde o recebimento por diferentes canais (E-mail, SEI e Aranda) até a triagem, registro, implementação, acompanhamento, documentação e geração de informações gerenciais, eliminando atividades manuais repetitivas, aumentando a rastreabilidade e proporcionando inteligência estratégica para a tomada de decisão?'
    UNION ALL SELECT 'Como podemos modernizar e integrar a gestão dos ativos patrimoniais e de Tecnologia da Informação do TJRR, garantindo rastreabilidade, atualização contínua das movimentações e confiabilidade do inventário institucional, reduzindo processos manuais e retrabalho entre as unidades responsáveis?'
    UNION ALL SELECT 'Como podemos, de forma automatizada e centralizada, mensurar e atribuir os custos de consumo de recursos (VMs, Clusters K8s, Deployments, Pods e Containers) para permitir uma gestão financeira transparente e eficiente da infraestrutura de TI do TJRR?'
    UNION ALL SELECT 'Como podemos propiciar a extração automatizada, integrada e centralizada de relatórios de dados funcionais e remuneratórios na Secretaria de Gestão de Pessoas, eliminando o uso de controles paralelos e otimizando o envio de informações obrigatórias ao Portal da Transparência do TJRR?'
    UNION ALL SELECT 'Como podemos dimensionar e alocar, de forma eficiente, a força de trabalho nas unidades de apoio direto e indireto do TJRR, desenvolvendo uma metodologia institucional integrada a uma ferramenta tecnológica que facilite a distribuição equilibrada de pessoal, cargos e funções, mitigando assimetrias e a sobrecarga de trabalho?'
    UNION ALL SELECT 'Como podemos gerir, de forma preventiva, integrada e sistematizada de informações de risco, os deslocamentos e missões institucionais no GABMIL, unindo inteligência e análise territorial para mitigar a exposição de magistrados e servidores no Estado de Roraima?'
    UNION ALL SELECT 'Como podemos automatizar o acompanhamento do portfólio de projetos, ações, programas e desafios do NPI/TJRR, de modo que o registro de progresso de cada etapa seja feito uma única vez e alimente automaticamente o painel de Business Intelligence, reduzindo o esforço de atualização manual e assegurando que os dados apresentados à gestão reflitam o estado real e atualizado de cada iniciativa?'
) AS d
WHERE temas.trilha_id = 2 AND temas.nome = 'Eficiência e Sustentabilidade da Gestão Administrativa (Tema 3)';
