<?php

/**
 * Carrega no banco os dados "grossos" (sem arquivo) das edições anteriores
 * do Prêmio de Inovação (1ª a 4ª, 2022-2025), extraídos de
 * "Informações Prêmios de Inovação.pdf" (fonte da verdade, consolidada pelo
 * usuário) e dos documentos oficiais anexos (editais, resultados finais em
 * PDF, DJE), em /home/f3011432/Code/NPI/SGSI/LevantamentoEdicoesAnteriores/.
 *
 * O que este script cria: Concurso (status=encerrado), Trilhas, Eventos do
 * Cronograma, Prêmios (modo geral), Equipes e a publicação de Resultados por
 * Trilha (resultados_trilha, via ResultadoTrilhaRepository::publicar() — o
 * mesmo método usado pela apuração real, sem precisar rodar inscrição/
 * submissão/avaliação de verdade).
 *
 * O que este script NÃO faz (fica para o guia manual, que exige arquivo em
 * mãos): upload de PDF de edital/resultado (tela Documentos), upload da foto
 * de destaque de cada vencedor (tela Resultados > editar destaque), upload
 * de galeria (tela Mídia), upload de imagem de prêmio (tela Prêmios).
 *
 * Modelagem das edições 3ª e 4ª: os resultados oficiais dessas duas edições
 * não têm vencedor por eixo/trilha temático, só uma classificação geral
 * (confirmado no "Resultado Final" em PDF de cada uma). Por isso as trilhas
 * temáticas (10 na 3ª, 7 na 4ª) são criadas vazias, como estrutura/contexto,
 * e todo o resultado fica numa trilha extra "Classificação Geral".
 *
 * Uso:
 *   php database/carregar_edicoes_anteriores.php --usuario-id=1
 *   php database/carregar_edicoes_anteriores.php --usuario-id=1 --edicao=2022,2023
 *   php database/carregar_edicoes_anteriores.php --usuario-id=1 --confirmar
 *
 * Por padrão roda em modo consulta (dry-run): só mostra o que seria feito.
 * --usuario-id é o seu próprio id de usuário (visível em Meu Perfil), usado
 * como "publicado_por" nos resultados. --edicao filtra uma ou mais edições
 * (2022,2023,2024,2025); sem essa flag, processa as 4.
 *
 * Idempotente para concurso/trilha/equipe/resultado (roda de novo sem
 * duplicar, casando por nome). Eventos do cronograma e Prêmios só são
 * criados se a edição ainda não tiver nenhum cadastrado — se quiser
 * recriá-los, apague antes pela tela.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\ConcursoRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\EventoCronogramaRepository;
use App\Repositories\PremioRepository;
use App\Repositories\ResultadoTrilhaRepository;
use App\Repositories\TrilhaRepository;

// ---------------------------------------------------------------------
// Prêmios: mesma estrutura textual (Notebook/Tablet/Assistente virtual) nas
// 4 edições, conforme documentado nos editais/páginas públicas.
// ---------------------------------------------------------------------
$PREMIOS_PADRAO = [
    ['posicao' => 1, 'descricao' => 'Notebook (um por integrante da equipe)'],
    ['posicao' => 2, 'descricao' => 'Tablet (um por integrante da equipe)'],
    ['posicao' => 3, 'descricao' => 'Assistente virtual (um por integrante da equipe)'],
];

// ---------------------------------------------------------------------
// Dados grossos das 4 edições.
// ---------------------------------------------------------------------
$EDICOES = [
    '2022' => [
        'nome' => '1º Prêmio de Inovação do TJRR',
        'descricao' => 'Iniciativa do Poder Judiciário de Roraima para estimular a cultura da '
            . 'inovação, alinhada ao Planejamento Estratégico do TJRR e às diretrizes do CNJ. '
            . '74 pessoas inscritas, divididas em 15 projetos, em duas modalidades: Iniciativas '
            . 'ou Práticas Inovadoras e Ideias Estruturadas.',
        'data_inicio' => '2022-05-30',
        'data_fim' => '2022-10-19',
        'trilhas' => [
            'Iniciativas' => [
                ['nome_equipe' => 'Metodologia de Correição – Verificação', 'nf' => 3, 'colocacao' => 1, 'resumo' => null],
                ['nome_equipe' => 'Visual Law nas MPUs', 'nf' => 2, 'colocacao' => 2, 'resumo' => null],
                ['nome_equipe' => 'Desafio Justiça na Medida Certa – Quem Perde, Ganha! – 2ª Edição', 'nf' => 1, 'colocacao' => 3, 'resumo' => 'Material completo: https://drive.google.com/drive/folders/11x7XPKzKrOz2_05eW8K1lT4tCAOCR75R'],
            ],
            'Ideias Estruturadas' => [
                ['nome_equipe' => 'Processamento Negativo dos Crimes de Dano na Reparação dos Prejuízos Suportados pela Mulher Vítima de Violência Doméstica', 'nf' => 3, 'colocacao' => 1, 'resumo' => null],
                ['nome_equipe' => 'Atuação do Poder Judiciário de Roraima no Atendimento a Pessoas em Situação de Rua e suas Interseccionalidades', 'nf' => 2, 'colocacao' => 2, 'resumo' => null],
                ['nome_equipe' => 'Criação do Setor de Cobrança de Recursos Extraorçamentários – Custas, Taxas, Multas Judiciais e Administrativas', 'nf' => 1, 'colocacao' => 3, 'resumo' => null],
            ],
        ],
        'premios' => $PREMIOS_PADRAO,
        'eventos' => [
            ['titulo' => 'Abertura das inscrições', 'descricao' => null, 'data_inicio' => '2022-05-30 00:00:00', 'data_fim' => null],
            ['titulo' => 'Encerramento das inscrições', 'descricao' => null, 'data_inicio' => '2022-06-30 23:59:59', 'data_fim' => null],
            ['titulo' => 'Resultado da avaliação final', 'descricao' => null, 'data_inicio' => '2022-10-04 00:00:00', 'data_fim' => null],
            ['titulo' => 'Solenidade de premiação', 'descricao' => null, 'data_inicio' => '2022-10-19 00:00:00', 'data_fim' => null],
        ],
    ],

    '2023' => [
        'nome' => '2º Prêmio de Inovação do TJRR',
        'descricao' => 'Iniciativa para incentivar de maneira recorrente a cultura da inovação no '
            . 'âmbito do Poder Judiciário de Roraima, com duas modalidades: Iniciativas e Ideias '
            . 'Estruturadas. Classificação geral da edição, cruzando as duas modalidades: '
            . '1º Automajus, 2º Alvitre, 3º Investidor Anjo.',
        'data_inicio' => '2023-05-02',
        'data_fim' => '2023-10-27', // data de assinatura do Certificado Menção Honrosa (1817243)
        // Notas (campo 'nf') vêm de notas.pdf ("TOTAL"). A colocação 1º/2º/3º segue
        // exclusivamente "Informações Prêmios de Inovação.pdf" (fonte da verdade,
        // confirmada pelo usuário) — repare que a ordem por nota não bate exatamente
        // com a colocação oficial (o TOTAL de notas.pdf parece ser só uma etapa de
        // avaliação, não o cálculo final). nf sem colocacao = não confirmado no pódio.
        'trilhas' => [
            'Iniciativas' => [
                ['nome_equipe' => 'Alvitre - Sistema de Saneamento de Dados', 'nf' => 9.43, 'colocacao' => 1, 'resumo' => null],
                ['nome_equipe' => 'Atendimento Itinerante Waimiri Atroari Hitxatxa Tyke Kara', 'nf' => 9.86, 'colocacao' => 2, 'resumo' => null],
                ['nome_equipe' => 'Automação do Cadastro do Registro de Imóveis', 'nf' => 9.14, 'colocacao' => 3, 'resumo' => null],
                ['nome_equipe' => 'Projeto Rede Viva', 'nf' => 8.86, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Espaços Seguros para Mulheres em Situação de Violência Doméstica nos Postos Avançados de Atendimento do TJRR', 'nf' => 8.86, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'A Arte Liberta', 'nf' => 8.71, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Protocolo de Pós-Acolhimento Institucional do Jovem: Garantindo Dignidade ao Egresso do Acolhimento Institucional', 'nf' => 8.71, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Construção do Mapa da Rede de Serviços Socioassistencial e de Saúde Articulado ao Sistema Prisional e Socioeducativo em Boa Vista-RR', 'nf' => 8.00, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Planner Processual para Audiências e Sessões de Julgamento', 'nf' => 7.71, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Citação Presente', 'nf' => 7.71, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Projeto Redialogar: Requalificando o Diálogo da Rede de Proteção e Concretização de Direitos da Infância e Juventude do Estado de Roraima', 'nf' => 7.71, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Núcleo Interprofissional Forense', 'nf' => 7.57, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Manual de Diretrizes para Atendimento de Pessoas com Surdez', 'nf' => 7.29, 'colocacao' => null, 'resumo' => 'Nome completo: Manual de Diretrizes Operacionais para Atuação de Tradutores e Intérpretes de Libras, Operadores do Direito e Demais Servidores do Tribunal no Atendimento de Pessoas com Surdez.'],
                ['nome_equipe' => 'Implementação da Metodologia BIM como Ferramenta de Inovação na Gestão de Projetos e Obras do TJRR', 'nf' => 7.00, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Ações Socioambientais com Famílias de Oleiros na Vila Vintém, em Roraima', 'nf' => 6.71, 'colocacao' => null, 'resumo' => null],
            ],
            'Ideias Estruturadas' => [
                ['nome_equipe' => 'Automajus: Democratizando a Automação no Poder Judiciário', 'nf' => 8.29, 'colocacao' => 1, 'resumo' => null],
                ['nome_equipe' => 'Investidor Anjo - Para Mulheres em Situação de Violência Doméstica que Buscam Autonomia Econômica', 'nf' => 8.71, 'colocacao' => 2, 'resumo' => null],
                ['nome_equipe' => 'Gamejus', 'nf' => 9.29, 'colocacao' => 3, 'resumo' => null],
                ['nome_equipe' => 'Portal Poupa Tempo Jus', 'nf' => 8.57, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Diminuição do Tempo do Processo em Varas do Júri', 'nf' => 8.29, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Aprendejus - O Uso Adequado das Tecnologias do Poder Judiciário', 'nf' => 8.14, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Ensino de Direito Constitucional e Direitos Humanos nas Escolas Públicas de Roraima', 'nf' => 8.14, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Base Colaborativa de Estudos de Contratações', 'nf' => 8.00, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Estruturação do Programa de Gestão Documental', 'nf' => 7.00, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Projeto Mentora - Melhoria da Performance Individual de Servidores', 'nf' => 6.71, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Capacitação Online para Servidores Cedidos ao TJRR', 'nf' => 6.71, 'colocacao' => null, 'resumo' => null],
                ['nome_equipe' => 'Seleção por Competência (Seleção de Servidores para Funções e Cargos em Comissão)', 'nf' => 6.00, 'colocacao' => null, 'resumo' => null],
            ],
        ],
        'premios' => $PREMIOS_PADRAO,
        'eventos' => [
            ['titulo' => 'Abertura das inscrições', 'descricao' => 'Edital de Abertura nº 01/2023', 'data_inicio' => '2023-05-02 08:00:00', 'data_fim' => null],
            ['titulo' => 'Encerramento das inscrições', 'descricao' => null, 'data_inicio' => '2023-05-31 23:59:00', 'data_fim' => null],
        ],
    ],

    '2024' => [
        'nome' => '3º Prêmio de Inovação do TJRR',
        'descricao' => 'Concurso estruturado em três fases (construção de ideias, estruturação de '
            . 'iniciativas e apresentações orais), organizado em 10 eixos temáticos. Diferente das '
            . 'edições anteriores, não houve vencedor por eixo: a equipe mais bem classificada de '
            . 'cada eixo avançava, junto às demais melhores, até o limite de 15 equipes, avaliadas '
            . 'em conjunto na fase final — resultando numa classificação geral única.',
        'data_inicio' => '2024-04-19',
        'data_fim' => '2024-10-21',
        'trilhas' => [
            // Eixos temáticos oficiais (estrutura/contexto da edição — sem resultado
            // próprio, ver nota de modelagem no topo do arquivo).
            'Desburocratização e Gestão Processual' => [],
            'Equidade Racial' => [],
            'Acesso à Justiça' => [],
            'Justiça e Cidadania' => [],
            'Sustentabilidade e Meio Ambiente' => [],
            'Gestão de Pessoas' => [],
            'Combate à Violência Doméstica' => [],
            'Tecnologia da Informação e Comunicação' => [],
            'Infância e Juventude' => [],
            'Sistema Carcerário, Execução Penal e Medidas Socioeducativas' => [],
            // Resultado Final oficial (DJE 25/10/2024, Anexo Único) — nf = "Média Final 3".
            'Classificação Geral' => [
                ['nome_equipe' => 'Arauto', 'nf' => 8.45, 'colocacao' => 1, 'resumo' => 'Integrador assíncrono de aplicações, serviços e pessoas.'],
                ['nome_equipe' => 'Fala Maria', 'nf' => 8.44, 'colocacao' => 2, 'resumo' => 'Equipe: Coordenadoria de Violência Doméstica (CEVID).'],
                ['nome_equipe' => 'ConectaJúri', 'nf' => 8.42, 'colocacao' => 3, 'resumo' => 'Software de comunicação anônima para o Tribunal do Júri.'],
                ['nome_equipe' => 'Dia "S"', 'nf' => 7.78, 'colocacao' => 4, 'resumo' => 'Equipe: CriAcesso.'],
                ['nome_equipe' => 'Reúne', 'nf' => 7.75, 'colocacao' => 5, 'resumo' => 'Equipe: Themis. Plataforma de rede de apoio a mulheres e meninas em situação de violência doméstica.'],
                ['nome_equipe' => 'Mapa do Acolhimento', 'nf' => 7.69, 'colocacao' => 6, 'resumo' => 'Equipe: Equipe Multidisciplinar dos Juizados de Violência Doméstica.'],
                ['nome_equipe' => 'Valoração Ambiental em Crimes de Poluição Hídrica', 'nf' => 7.46, 'colocacao' => 7, 'resumo' => 'Equipe: Valolare Ambiental.'],
                ['nome_equipe' => 'Aplicativo Guardião da Infância e Juventude', 'nf' => 7.39, 'colocacao' => 8, 'resumo' => 'Equipe: Mapa Cidadão.'],
                ['nome_equipe' => 'SimplesJudi', 'nf' => 7.37, 'colocacao' => 9, 'resumo' => 'Equipe: Mariana & Matheus.'],
                ['nome_equipe' => 'Controle Inteligente de Movimentação de Pessoas no TJRR', 'nf' => 7.32, 'colocacao' => 10, 'resumo' => 'Equipe: InovaID.'],
                ['nome_equipe' => 'Reintegrathon', 'nf' => 7.19, 'colocacao' => 11, 'resumo' => null],
                ['nome_equipe' => 'Inovação e Proteção', 'nf' => 7.11, 'colocacao' => 12, 'resumo' => null],
                ['nome_equipe' => 'POMARR - Portal de Mandados do Tribunal de Justiça de Roraima', 'nf' => 7.05, 'colocacao' => 13, 'resumo' => 'Equipe: JAD 4.0 - Justiça Avançada e Digital 4.0.'],
                ['nome_equipe' => 'Justiça Amiga', 'nf' => 6.99, 'colocacao' => 14, 'resumo' => null],
            ],
        ],
        'premios' => $PREMIOS_PADRAO,
        'eventos' => [
            ['titulo' => 'Inscrições', 'descricao' => null, 'data_inicio' => '2024-04-19 00:00:00', 'data_fim' => '2024-05-17 23:59:59'],
            ['titulo' => 'Resultado da avaliação preliminar', 'descricao' => null, 'data_inicio' => '2024-08-26 00:00:00', 'data_fim' => null],
            ['titulo' => 'Apresentação das iniciativas', 'descricao' => null, 'data_inicio' => '2024-10-07 00:00:00', 'data_fim' => '2024-10-11 23:59:59'],
            ['titulo' => 'Avaliação final', 'descricao' => null, 'data_inicio' => '2024-10-14 00:00:00', 'data_fim' => '2024-10-16 23:59:59'],
            ['titulo' => 'Divulgação do resultado', 'descricao' => null, 'data_inicio' => '2024-10-18 00:00:00', 'data_fim' => null],
            ['titulo' => 'Publicação do resultado final e entrega da premiação', 'descricao' => null, 'data_inicio' => '2024-10-21 00:00:00', 'data_fim' => null],
        ],
    ],

    '2025' => [
        'nome' => '4º Prêmio de Inovação do TJRR',
        'descricao' => 'Estímulo à cultura da inovação no âmbito do Poder Judiciário de Roraima, '
            . 'organizado em 7 trilhas temáticas. 47 equipes inscritas, das quais 32 submeteram '
            . 'ideias e 12 avançaram para a apresentação oral (fase 2). Assim como na 3ª edição, '
            . 'não houve vencedor por trilha: o resultado final foi uma classificação geral única.',
        'data_inicio' => '2025-08-18',
        'data_fim' => '2025-10-17',
        'trilhas' => [
            'Gestão Processual' => [],
            'Gestão de Pessoas' => [],
            'Cooperação Judiciária' => [],
            'Acesso à Justiça' => [],
            'Equidade Racial' => [],
            'Sustentabilidade e Meio Ambiente' => [],
            'Combate ao Assédio e à Discriminação' => [],
            // Resultado Final oficial (publicado 20/10/2025) — nf = "média geral", só
            // divulgada para o top 8. As demais equipes abaixo (finalistas da fase 2
            // sem colocação numérica confirmada, e as que só submeteram ideia sem
            // avançar) entram com nf=0 e colocacao=null, só para registrar a
            // participação — não representam nota real nem posição no ranking.
            'Classificação Geral' => [
                ['nome_equipe' => 'CO2necta', 'nf' => 6.582, 'colocacao' => 1, 'resumo' => 'Trilha: Sustentabilidade e Meio Ambiente. Conectando inovação e sustentabilidade para transformar o futuro do meio ambiente e do Judiciário.'],
                ['nome_equipe' => 'CompraMatch', 'nf' => 6.526, 'colocacao' => 2, 'resumo' => 'Trilha: Cooperação Judiciária. Também referida como "ColaboraJus": complexidade na orquestração de compras compartilhadas.'],
                ['nome_equipe' => 'CEVID', 'nf' => 6.417, 'colocacao' => 3, 'resumo' => 'Trilha: Gestão Processual.'],
                ['nome_equipe' => 'LiciTech', 'nf' => 6.394, 'colocacao' => 4, 'resumo' => 'Trilha: Gestão Processual. Sistema Inteligente de Apoio à Análise e Formalização de Preços em licitações e contratos.'],
                ['nome_equipe' => 'F.A.C.E.', 'nf' => 6.377, 'colocacao' => 5, 'resumo' => 'Trilha: Gestão Processual. Fiscalização Automatizada de Comparecimento Eletrônico.'],
                ['nome_equipe' => 'InformArtizar', 'nf' => 6.351, 'colocacao' => 6, 'resumo' => 'Trilha: Gestão Processual. Também referido como "BNP Smart": divulgação de precedentes vinculantes.'],
                ['nome_equipe' => 'Línguas Indígenas Para a Justiça', 'nf' => 6.176, 'colocacao' => 7, 'resumo' => 'Cadastro de intérpretes e tradutores de línguas indígenas.'],
                ['nome_equipe' => 'In Dubio Pro Duo', 'nf' => 6.160, 'colocacao' => 8, 'resumo' => 'Também referido como "Justiça na Palma": dificuldade do cidadão em compreender e acompanhar processos judiciais.'],
                // Finalistas da fase 2 (apresentação oral) sem colocação numérica confirmada:
                ['nome_equipe' => 'Justus IA', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Finalista da fase 2 (apresentação oral). Assistente Virtual para Atermação e Triagem nos Juizados Especiais Cíveis.'],
                ['nome_equipe' => 'Robô Clóvis', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Finalista da fase 2 (apresentação oral). Plataforma de Automação e Celeridade para a Justiça.'],
                ['nome_equipe' => 'Wazari', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Finalista da fase 2 (apresentação oral). Correição judicial realizada de forma manual e por amostragem.'],
                ['nome_equipe' => 'Predial 4.0', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Finalista da fase 2 (apresentação oral). Plano de Gestão Estratégica - Manutenção Predial.'],
                ['nome_equipe' => 'CEVID 2', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Finalista da fase 2 (apresentação oral). Também referida como "AURA": prevenção ao assédio moral, sexual e à discriminação.'],
                // Demais equipes que submeteram ideia (não avançaram à fase 2):
                ['nome_equipe' => 'Inovalex', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Também referida como "Justiça Conectada IA": sistema centralizado para pedidos de cooperação entre órgãos do sistema de justiça.'],
                ['nome_equipe' => 'Lírios da Inovação', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Também referida como "Visualize": dificuldade de servidores e estagiários em compreender e executar processos administrativos.'],
                ['nome_equipe' => 'Alice', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Assistente Estatístico Processual: disponibilidade de dados estatísticos processuais.'],
                ['nome_equipe' => 'Nêmesis', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Radar de Fluxo Inteligente: redução de atrasos operacionais em processos administrativos.'],
                ['nome_equipe' => 'As Inovadoras', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Também referida como "Justicia es para todos": dificuldade de imigrantes venezuelanos no acesso à justiça.'],
                ['nome_equipe' => 'Ouvidor IA', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Atendente Virtual Judicial com IA: dificuldade dos cidadãos em obter informações sobre seus processos.'],
                ['nome_equipe' => 'Justiça Que Orienta', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Otimização do tempo de espera com ações educativas.'],
                ['nome_equipe' => 'Ação Roraima', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Também referida como "Servidores Verdes": criação de uma cultura ambiental contínua no TJRR.'],
                ['nome_equipe' => 'Nexus', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Também referida como "SynapseFiscal": morosidade e ineficiência na gestão de processos de Execução Fiscal.'],
                ['nome_equipe' => 'Time Setin Tjce', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Também referida como "DataJust": integração entre fontes de dados administrativos e judiciais.'],
                ['nome_equipe' => 'Sara No Pje', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Inteligência Artificial Integrada para Acelerar Atos Judiciais.'],
                ['nome_equipe' => 'Paradigma Shift', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Também referida como "SAAV": Sistema de Auditoria Algorítmica de Vieses em decisões judiciais.'],
                ['nome_equipe' => 'Data Justice', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Também referida como "Meu Direito RR": pessoas que não conhecem seus direitos básicos.'],
                ['nome_equipe' => 'Geração Signus', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Também referida como "Melius servitium": melhoria do atendimento para pessoas surdas.'],
                ['nome_equipe' => 'AmigaJus', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Aumento de casos de violência, negligência e exploração contra crianças e adolescentes.'],
                ['nome_equipe' => 'Talia', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Sistema de Transcrição e Tradução Automática Multilíngue para Audiências Judiciais.'],
                ['nome_equipe' => 'Justiça na Palma da Sua Mão com o TJCE Mobile', 'nf' => 0, 'colocacao' => null, 'resumo' => 'Dificuldade de acesso da população aos serviços judiciais.'],
            ],
        ],
        'premios' => $PREMIOS_PADRAO,
        'eventos' => [
            ['titulo' => 'Divulgação do edital', 'descricao' => null, 'data_inicio' => '2025-08-11 00:00:00', 'data_fim' => '2025-08-15 23:59:59'],
            ['titulo' => 'Inscrições', 'descricao' => null, 'data_inicio' => '2025-08-18 00:00:00', 'data_fim' => '2025-08-31 23:59:59'],
            ['titulo' => 'Submissão das ideias', 'descricao' => null, 'data_inicio' => '2025-08-27 00:00:00', 'data_fim' => '2025-09-22 23:59:59'],
            ['titulo' => 'Avaliação documental', 'descricao' => null, 'data_inicio' => '2025-09-25 00:00:00', 'data_fim' => '2025-10-03 23:59:59'],
            ['titulo' => 'Resultado preliminar', 'descricao' => null, 'data_inicio' => '2025-10-09 00:00:00', 'data_fim' => null],
            ['titulo' => 'Resultado final da avaliação da Fase 1', 'descricao' => null, 'data_inicio' => '2025-10-10 00:00:00', 'data_fim' => null],
            ['titulo' => 'Apresentações dos finalistas', 'descricao' => null, 'data_inicio' => '2025-10-13 00:00:00', 'data_fim' => '2025-10-14 23:59:59'],
            ['titulo' => 'Premiação', 'descricao' => null, 'data_inicio' => '2025-10-17 00:00:00', 'data_fim' => null],
        ],
    ],
];

// ---------------------------------------------------------------------
// Validação de tamanho contra os limites reais do schema, ANTES de tocar no
// banco. Sem isso, um nome longo demais só aparece como PDOException no meio
// de uma transação já em andamento — deixando concursos/trilhas/equipes já
// gravados e outros ainda por gravar (foi exatamente o que aconteceu com um
// nome_equipe de 176 caracteres na primeira versão deste script).
// ---------------------------------------------------------------------
$LIMITES = [
    'concursos.nome' => 150,
    'trilhas.nome' => 150,
    'equipes.nome_equipe' => 150,
    'eventos_cronograma.titulo' => 150,
    'premios.descricao' => 65535, // TEXT, na prática sem limite prático
];

$errosValidacao = [];

foreach ($EDICOES as $chaveEdicao => $dadosEdicao) {
    if (mb_strlen($dadosEdicao['nome']) > $LIMITES['concursos.nome']) {
        $errosValidacao[] = "concursos.nome (" . mb_strlen($dadosEdicao['nome']) . " car.) em '$chaveEdicao': {$dadosEdicao['nome']}";
    }

    foreach ($dadosEdicao['trilhas'] as $nomeTrilha => $resultados) {
        if (mb_strlen($nomeTrilha) > $LIMITES['trilhas.nome']) {
            $errosValidacao[] = "trilhas.nome (" . mb_strlen($nomeTrilha) . " car.) em '$chaveEdicao': $nomeTrilha";
        }

        foreach ($resultados as $resultado) {
            $nomeEquipe = isset($resultado['nome_equipe']) ? $resultado['nome_equipe'] : '';
            if (mb_strlen($nomeEquipe) > $LIMITES['equipes.nome_equipe']) {
                $errosValidacao[] = "equipes.nome_equipe (" . mb_strlen($nomeEquipe) . " car.) em '$chaveEdicao' / '$nomeTrilha': $nomeEquipe";
            }
        }
    }

    foreach ($dadosEdicao['eventos'] as $evento) {
        if (mb_strlen($evento['titulo']) > $LIMITES['eventos_cronograma.titulo']) {
            $errosValidacao[] = "eventos_cronograma.titulo (" . mb_strlen($evento['titulo']) . " car.) em '$chaveEdicao': {$evento['titulo']}";
        }
    }
}

if (!empty($errosValidacao)) {
    echo "Validação falhou — corrija o array \$EDICOES antes de rodar (nada foi tocado no banco):\n\n";
    foreach ($errosValidacao as $erro) {
        echo "  - $erro\n";
    }
    exit(1);
}

// ---------------------------------------------------------------------
// Leitura dos argumentos.
// ---------------------------------------------------------------------
$confirmar = in_array('--confirmar', $argv, true);
$usuarioId = null;
$edicoesFiltro = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--usuario-id=') === 0) {
        $usuarioId = (int) substr($arg, strlen('--usuario-id='));
    } elseif (strpos($arg, '--edicao=') === 0) {
        $edicoesFiltro = explode(',', substr($arg, strlen('--edicao=')));
    }
}

if ($usuarioId === null || $usuarioId <= 0) {
    echo "Uso: php database/carregar_edicoes_anteriores.php --usuario-id=1 [--edicao=2022,2023] [--confirmar]\n";
    exit(1);
}

$chaves = $edicoesFiltro !== null ? $edicoesFiltro : array_keys($EDICOES);

echo $confirmar
    ? "Modo APLICAR: os dados abaixo serão gravados no banco.\n\n"
    : "Modo consulta (dry-run). Nada será alterado. Repita com --confirmar para aplicar.\n\n";

$concursos = new ConcursoRepository();
$trilhas = new TrilhaRepository();
$equipes = new EquipeRepository();
$resultadosTrilha = new ResultadoTrilhaRepository();
$eventos = new EventoCronogramaRepository();
$premios = new PremioRepository();

foreach ($chaves as $chave) {
    if (!isset($EDICOES[$chave])) {
        echo "Edição '$chave' não reconhecida, pulando.\n";
        continue;
    }

    $dados = $EDICOES[$chave];
    echo "=== {$dados['nome']} ===\n";

    // --- Concurso (idempotente por nome exato) ---
    $concursoExistente = null;
    foreach ($concursos->listar() as $c) {
        if ($c['nome'] === $dados['nome']) {
            $concursoExistente = $c;
            break;
        }
    }

    if ($concursoExistente !== null) {
        $concursoId = (int) $concursoExistente['id'];
        echo "  Concurso já existe (id $concursoId), reaproveitando.\n";
    } elseif ($confirmar) {
        $concursoId = $concursos->criar($dados['nome'], $dados['descricao'], $dados['data_inicio'], $dados['data_fim'], 'encerrado');
        echo "  Concurso criado (id $concursoId), status=encerrado.\n";
    } else {
        $concursoId = null;
        echo "  [dry-run] Concurso seria criado, status=encerrado.\n";
    }

    // --- Trilhas ---
    $trilhaIdPorNome = [];
    $trilhasExistentes = $concursoId !== null ? $trilhas->listarPorConcurso($concursoId) : [];

    $ordem = 0;
    foreach ($dados['trilhas'] as $nomeTrilha => $resultados) {
        $trilhaExistente = null;
        foreach ($trilhasExistentes as $t) {
            if ($t['nome'] === $nomeTrilha) {
                $trilhaExistente = $t;
                break;
            }
        }

        if ($trilhaExistente !== null) {
            $trilhaId = (int) $trilhaExistente['id'];
            echo "  Trilha '$nomeTrilha' já existe (id $trilhaId).\n";
        } elseif ($confirmar && $concursoId !== null) {
            $trilhaId = $trilhas->criar($concursoId, $nomeTrilha, null, $ordem, 1);
            echo "  Trilha '$nomeTrilha' criada (id $trilhaId).\n";
        } else {
            $trilhaId = null;
            echo "  [dry-run] Trilha '$nomeTrilha' seria criada.\n";
        }

        $trilhaIdPorNome[$nomeTrilha] = $trilhaId;
        $ordem++;
    }

    // --- Eventos do cronograma (só cria se a edição ainda não tiver nenhum) ---
    if (!empty($dados['eventos'])) {
        $temEventos = $concursoId !== null && count($eventos->listarPorConcurso($concursoId)) > 0;

        if ($temEventos) {
            echo "  Cronograma já tem eventos cadastrados, pulando.\n";
        } else {
            foreach ($dados['eventos'] as $i => $evento) {
                if ($confirmar && $concursoId !== null) {
                    $eventoId = $eventos->criar($concursoId, [
                        'etapa_id' => null,
                        'titulo' => $evento['titulo'],
                        'descricao' => $evento['descricao'],
                        'data_inicio' => $evento['data_inicio'],
                        'data_fim' => $evento['data_fim'],
                        'ordem' => $i,
                    ]);
                    echo "  Evento '{$evento['titulo']}' criado (id $eventoId).\n";
                } else {
                    echo "  [dry-run] Evento '{$evento['titulo']}' seria criado ({$evento['data_inicio']}).\n";
                }
            }
        }
    }

    // --- Prêmios gerais (só cria se a edição ainda não tiver nenhum) ---
    $temPremios = $concursoId !== null && count($premios->listarPorConcurso($concursoId)) > 0;

    if ($temPremios) {
        echo "  Prêmios já cadastrados, pulando.\n";
    } else {
        foreach ($dados['premios'] as $premio) {
            if ($confirmar && $concursoId !== null) {
                $premioId = $premios->criar($concursoId, [
                    'trilha_id' => null,
                    'posicao' => $premio['posicao'],
                    'descricao' => $premio['descricao'],
                    'imagem_path' => null,
                    'imagem_alt' => null,
                ]);
                echo "  Prêmio {$premio['posicao']}º criado (id $premioId): {$premio['descricao']}\n";
            } else {
                echo "  [dry-run] Prêmio {$premio['posicao']}º seria criado: {$premio['descricao']}\n";
            }
        }
    }

    // --- Equipes + publicação de resultados_trilha ---
    foreach ($dados['trilhas'] as $nomeTrilha => $resultados) {
        if (empty($resultados)) {
            continue;
        }

        $trilhaId = $trilhaIdPorNome[$nomeTrilha];
        $linhas = [];

        foreach ($resultados as $resultado) {
            if (empty($resultado['nome_equipe'])) {
                echo "  [AVISO] Trilha '$nomeTrilha': resultado sem nome_equipe, pulando.\n";
                continue;
            }

            $equipeExistente = $trilhaId !== null ? $equipes->buscarPorTrilhaENome($trilhaId, $resultado['nome_equipe']) : null;

            if ($equipeExistente !== null) {
                $equipeId = (int) $equipeExistente['id'];
                echo "  Equipe '{$resultado['nome_equipe']}' já existe (id $equipeId).\n";
            } elseif ($confirmar && $trilhaId !== null) {
                $equipeId = $equipes->criar($trilhaId, $resultado['nome_equipe'], 'Edição histórica (dado migrado)', null);
                echo "  Equipe '{$resultado['nome_equipe']}' criada (id $equipeId).\n";
            } else {
                $equipeId = null;
                $coloc = $resultado['colocacao'] !== null ? "{$resultado['colocacao']}º" : 'sem colocação';
                echo "  [dry-run] Equipe '{$resultado['nome_equipe']}' seria criada, $coloc.\n";
            }

            if ($equipeId !== null) {
                $linhas[] = [
                    'equipe_id' => $equipeId,
                    'nf' => $resultado['nf'],
                    'colocacao' => $resultado['colocacao'],
                ];
            }
        }

        if (!empty($linhas) && $confirmar && $trilhaId !== null) {
            $resultadosTrilha->publicar($trilhaId, $linhas, $usuarioId);
            echo "  Resultado da trilha '$nomeTrilha' publicado (" . count($linhas) . " equipe(s)).\n";

            foreach ($resultados as $resultado) {
                if (empty($resultado['resumo']) || empty($resultado['nome_equipe'])) {
                    continue;
                }
                $equipeAtual = $equipes->buscarPorTrilhaENome($trilhaId, $resultado['nome_equipe']);
                if ($equipeAtual !== null) {
                    $linhaResultado = null;
                    foreach ($resultadosTrilha->listarPorTrilha($trilhaId) as $linha) {
                        if ((int) $linha['equipe_id'] === (int) $equipeAtual['id']) {
                            $linhaResultado = $linha;
                            break;
                        }
                    }
                    if ($linhaResultado !== null) {
                        $resultadosTrilha->atualizarDestaque((int) $linhaResultado['id'], sanitizarHtmlRico($resultado['resumo']), null, null);
                        echo "  Resumo de destaque preenchido para '{$resultado['nome_equipe']}'.\n";
                    }
                }
            }
        }
    }

    echo "\n";
}

echo $confirmar
    ? "Concluído. Confira em Admin > Concursos e em /si/index.php?r=edicoes.\n"
    : "Dry-run concluído. Repita com --confirmar para aplicar.\n";
