<?php

/**
 * Fase 19 (#104): atribuicao de icones a Temas e Desafios, feita direto no
 * banco dev durante a fase de teste (nao veio via migration/codigo) -
 * precisa ser repetida em producao. PHP puro (nao SQL cru via `mysql`
 * client) porque o servidor de producao nao tem cliente mysql instalado -
 * mesma razao de todo script em database/ ja ser PHP (migrate.php,
 * excluir_usuario.php, etc).
 *
 * Casa por NOME do tema / TEXTO da pergunta do desafio (nao por id
 * autoincrement, que pode divergir entre dev e producao). Se algum
 * nome/pergunta tiver sido editado em producao antes deste deploy, o
 * item correspondente simplesmente nao casa com nenhuma linha (sem erro,
 * so' fica sem contar como aplicado - ver aviso no fim).
 *
 * Uso:
 *   php database/atualizar_icones_temas_desafios.php               (dry-run)
 *   php database/atualizar_icones_temas_desafios.php --confirmar   (aplica)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$confirmar = in_array('--confirmar', $argv, true);
$pdo = Database::conexao();

// [nome do tema, icone]
$temas = [
    ['Otimização e Celeridade da Prestação Jurisdicional (Tema 1)', 'tecnologia'],
    ['Aprimoramento do Atendimento ao Cidadão e Acesso à Justiça (Tema 2)', 'atendimento'],
    ['Eficiência e Sustentabilidade da Gestão Administrativa (Tema 3)', 'gestao'],
    ['Processos internos (Tema 1)', 'gestao'],
    ['Atividade finalística (Tema 2)', 'juridico'],
    ['Cidadania e Justiça Inclusiva (Tema 3)', 'acessibilidade'],
];

// [nome do tema pai, pergunta do desafio, icone]
$desafios = [
    ['Processos internos (Tema 1)', 'Como podemos viabilizar o gerenciamento e aquisição de passagens aéreas por meio de credenciamento permanente de agências de viagens e companhias aéreas, para centralizar demandas e automatizar a busca, triagem e comparação de passagens em tempo real, garantindo a seleção pelo menor preço, celeridade na emissão e total rastreabilidade para o TJRR?', 'viagem'],
    ['Processos internos (Tema 1)', 'Como podemos otimizar e automatizar o processo de análise, conferência e validação dos requerimentos de conversão de férias no Setor de Monitoramento de Desempenho, eliminando o gargalo do processamento manual massivo de cerca de 800 pedidos dentro da janela crítica de apenas 4 dias?', 'pessoas'],
    ['Processos internos (Tema 1)', 'Como podemos construir uma estratégia que propicie a elaboração, o fomento e o monitoramento contínuo de um banco de dados unificado para taxas de absenteísmo na SGP/SUBGEP, facilitando a integração de informações e o suporte preventivo à qualidade de vida dos servidores do TJRR?', 'dados'],
    ['Processos internos (Tema 1)', 'Como podemos melhorar o monitoramento e a coleta unificada de dados de sustentabilidade no SSRS, de modo a mitigar o retrabalho no preenchimento de relatórios obrigatórios (PLS, Resolução CNJ nº 400/2021 e Inventário GEE) e viabilizar a rastreabilidade das ações de compensação ambiental no TJRR de maneira automatizada e padronizada?', 'sustentabilidade'],
    ['Processos internos (Tema 1)', 'Como podemos automatizar e integrar todo o ciclo de gestão das demandas da Subsecretaria de Sistemas (SubSi), desde o recebimento por diferentes canais (E-mail, SEI e Aranda) até a triagem, registro, implementação, acompanhamento, documentação e geração de informações gerenciais, eliminando atividades manuais repetitivas, aumentando a rastreabilidade e proporcionando inteligência estratégica para a tomada de decisão?', 'gestao'],
    ['Processos internos (Tema 1)', 'Como podemos aumentar o engajamento dos servidores nas ações de conscientização sobre segurança da informação, tornando essas iniciativas mais atrativas, efetivas e capazes de fortalecer a cultura de segurança no Tribunal de Justiça de Roraima?', 'seguranca'],
    ['Processos internos (Tema 1)', 'Como podemos modernizar e integrar a gestão dos ativos patrimoniais e de Tecnologia da Informação do TJRR, garantindo rastreabilidade, atualização contínua das movimentações e confiabilidade do inventário institucional, reduzindo processos manuais e retrabalho entre as unidades responsáveis?', 'patrimonio'],
    ['Processos internos (Tema 1)', 'Como podemos, de forma automatizada e centralizada, mensurar e atribuir os custos de consumo de recursos (VMs, Clusters K8s, Deployments, Pods e Containers) para permitir uma gestão financeira transparente e eficiente da infraestrutura de TI do TJRR?', 'infraestrutura'],
    ['Processos internos (Tema 1)', 'Como podemos propiciar a extração automatizada, integrada e centralizada de relatórios de dados funcionais e remuneratórios na Secretaria de Gestão de Pessoas, eliminando o uso de controles paralelos e otimizando o envio de informações obrigatórias ao Portal da Transparência do TJRR?', 'transparencia'],
    ['Processos internos (Tema 1)', 'Como podemos dimensionar e alocar, de forma eficiente, a força de trabalho nas unidades de apoio direto e indireto do TJRR, desenvolvendo uma metodologia institucional integrada a uma ferramenta tecnológica que facilite a distribuição equilibrada de pessoal, cargos e funções, mitigando assimetrias e a sobrecarga de trabalho?', 'pessoas'],
    ['Processos internos (Tema 1)', 'Como podemos gerir, de forma preventiva, integrada e sistematizada de informações de risco, os deslocamentos e missões institucionais no GABMIL, unindo inteligência e análise territorial para mitigar a exposição de magistrados e servidores no Estado de Roraima?', 'seguranca'],
    ['Atividade finalística (Tema 2)', 'Como podemos realizar a correição judicial do acervo processual das unidades judiciárias no sistema Projudi, superando as limitações da análise manual por amostragem e padronizando a detecção de movimentações inadequadas ou redundantes?', 'automacao'],
    ['Atividade finalística (Tema 2)', 'Como podemos estimular a resolução de conflitos por meios alternativos e reduzir o volume de novas petições, aliviando a carga de trabalho das secretarias e gabinetes para garantir uma resposta mais célere e efetiva ao cidadão?', 'juridico'],
    ['Atividade finalística (Tema 2)', 'Como podemos construir uma estratégia de automação para a execução de tarefas repetitivas e de baixa complexidade no sistema Projudi, visando dar fluidez ao alto volume de movimentações processuais, reduzir o tempo de tramitação e garantir melhor qualidade de vida e saúde mental para um quadro reduzido de servidores?', 'automacao'],
    ['Atividade finalística (Tema 2)', 'Como podemos melhorar a transcrição automatizada e prover tradução em tempo real de audiências judiciais multilíngues, visando ampliar o acesso à justiça, reduzir a morosidade processual e eliminar a dependência de serviços externos especializados?', 'comunicacao'],
    ['Cidadania e Justiça Inclusiva (Tema 3)', 'Como podemos facilitar o processamento dos pedidos de autorização de viagem para menores, feito pelos pais e responsáveis, para que seja menos custoso a eles e ao judiciário?', 'viagem'],
    ['Cidadania e Justiça Inclusiva (Tema 3)', 'Como podemos facilitar o acesso aos serviços de Justiça e cidadania para as comunidades do Baixo Rio Branco (atendidas pelas comarcas de Caracaraí e Rorainópolis), reduzindo os custos de deslocamento fluvial e simplificando a jornada do usuário por meio de ferramentas digitais acessíveis?', 'acessibilidade'],
    ['Otimização e Celeridade da Prestação Jurisdicional (Tema 1)', 'Como podemos realizar a correição judicial do acervo processual das unidades judiciárias no sistema Projudi, superando as limitações da análise manual por amostragem e padronizando a detecção de movimentações inadequadas ou redundantes?', 'automacao'],
    ['Otimização e Celeridade da Prestação Jurisdicional (Tema 1)', 'Como podemos estimular a resolução de conflitos por meios alternativos e reduzir o volume de novas petições, aliviando a carga de trabalho das secretarias e gabinetes para garantir uma resposta mais célere e efetiva ao cidadão?', 'juridico'],
    ['Otimização e Celeridade da Prestação Jurisdicional (Tema 1)', 'Como podemos automatizar a execução de tarefas repetitivas e de baixa complexidade no sistema Projudi, visando dar fluidez ao alto volume de movimentações processuais, reduzir o tempo de tramitação e garantir melhor qualidade de vida e saúde mental para um quadro reduzido de servidores?', 'automacao'],
    ['Otimização e Celeridade da Prestação Jurisdicional (Tema 1)', 'Como podemos melhorar a transcrição automatizada e inserir tradução em tempo real de audiências judiciais multilíngues, visando ampliar o acesso à justiça, reduzir a morosidade processual e eliminar a dependência de serviços externos especializados?', 'comunicacao'],
    ['Aprimoramento do Atendimento ao Cidadão e Acesso à Justiça (Tema 2)', 'Como podemos facilitar o processamento dos pedidos de autorização de viagem para menores, feito pelos pais e responsáveis, para que seja menos custoso a eles e ao judiciário?', 'viagem'],
    ['Aprimoramento do Atendimento ao Cidadão e Acesso à Justiça (Tema 2)', 'Como podemos facilitar o acesso aos serviços de Justiça e cidadania para as comunidades do Baixo Rio Branco (atendidas pelas comarcas de Caracaraí e Rorainópolis), reduzindo os custos de deslocamento fluvial e simplificando a jornada do usuário por meio de ferramentas digitais acessíveis?', 'acessibilidade'],
    ['Eficiência e Sustentabilidade da Gestão Administrativa (Tema 3)', 'Como podemos viabilizar o gerenciamento e aquisição de passagens aéreas por meio de credenciamento permanente de agências de viagens e companhias aéreas, para centralizar demandas e automatizar a busca, triagem e comparação de passagens em tempo real, garantindo a seleção pelo menor preço, celeridade na emissão e total rastreabilidade para o TJRR?', 'viagem'],
    ['Eficiência e Sustentabilidade da Gestão Administrativa (Tema 3)', 'Como podemos propiciar a elaboração, o fomento e o monitoramento contínuo de um banco de dados unificado para taxas de absenteísmo na SGP/SUBGEP, facilitando a integração de informações e o suporte preventivo à qualidade de vida dos servidores do TJRR?', 'dados'],
    ['Eficiência e Sustentabilidade da Gestão Administrativa (Tema 3)', 'Como podemos melhorar o monitoramento e a coleta unificada de dados de sustentabilidade no SSRS, de modo a mitigar o retrabalho no preenchimento de relatórios obrigatórios (PLS, Resolução CNJ nº 400/2021 e Inventário GEE) e viabilizar a rastreabilidade das ações de compensação ambiental no TJRR de maneira automatizada e padronizada?', 'sustentabilidade'],
    ['Eficiência e Sustentabilidade da Gestão Administrativa (Tema 3)', 'Como podemos automatizar e integrar todo o ciclo de gestão das demandas da Subsecretaria de Sistemas (SubSi), desde o recebimento por diferentes canais (E-mail, SEI e Aranda) até a triagem, registro, implementação, acompanhamento, documentação e geração de informações gerenciais, eliminando atividades manuais repetitivas, aumentando a rastreabilidade e proporcionando inteligência estratégica para a tomada de decisão?', 'gestao'],
    ['Eficiência e Sustentabilidade da Gestão Administrativa (Tema 3)', 'Como podemos modernizar e integrar a gestão dos ativos patrimoniais e de Tecnologia da Informação do TJRR, garantindo rastreabilidade, atualização contínua das movimentações e confiabilidade do inventário institucional, reduzindo processos manuais e retrabalho entre as unidades responsáveis?', 'patrimonio'],
    ['Eficiência e Sustentabilidade da Gestão Administrativa (Tema 3)', 'Como podemos, de forma automatizada e centralizada, mensurar e atribuir os custos de consumo de recursos (VMs, Clusters K8s, Deployments, Pods e Containers) para permitir uma gestão financeira transparente e eficiente da infraestrutura de TI do TJRR?', 'infraestrutura'],
    ['Eficiência e Sustentabilidade da Gestão Administrativa (Tema 3)', 'Como podemos propiciar a extração automatizada, integrada e centralizada de relatórios de dados funcionais e remuneratórios na Secretaria de Gestão de Pessoas, eliminando o uso de controles paralelos e otimizando o envio de informações obrigatórias ao Portal da Transparência do TJRR?', 'transparencia'],
    ['Eficiência e Sustentabilidade da Gestão Administrativa (Tema 3)', 'Como podemos dimensionar e alocar, de forma eficiente, a força de trabalho nas unidades de apoio direto e indireto do TJRR, desenvolvendo uma metodologia institucional integrada a uma ferramenta tecnológica que facilite a distribuição equilibrada de pessoal, cargos e funções, mitigando assimetrias e a sobrecarga de trabalho?', 'pessoas'],
    ['Eficiência e Sustentabilidade da Gestão Administrativa (Tema 3)', 'Como podemos gerir, de forma preventiva, integrada e sistematizada de informações de risco, os deslocamentos e missões institucionais no GABMIL, unindo inteligência e análise territorial para mitigar a exposição de magistrados e servidores no Estado de Roraima?', 'seguranca'],
    ['Eficiência e Sustentabilidade da Gestão Administrativa (Tema 3)', 'Como podemos automatizar o acompanhamento do portfólio de projetos, ações, programas e desafios do NPI/TJRR, de modo que o registro de progresso de cada etapa seja feito uma única vez e alimente automaticamente o painel de Business Intelligence, reduzindo o esforço de atualização manual e assegurando que os dados apresentados à gestão reflitam o estado real e atualizado de cada iniciativa?', 'dados'],
];

echo "Atualizacao de icones - Temas e Desafios\n";
echo str_repeat('-', 60) . "\n";

$naoEncontrados = [];
$total = 0;

if ($confirmar) {
    $pdo->beginTransaction();
}

$stmtExisteTema = $pdo->prepare('SELECT COUNT(*) FROM temas WHERE nome = :nome');
$stmtTema = $pdo->prepare('UPDATE temas SET icone = :icone WHERE nome = :nome');
$stmtExisteDesafio = $pdo->prepare(
    'SELECT COUNT(*) FROM desafios d INNER JOIN temas t ON t.id = d.tema_id
     WHERE t.nome = :tema_nome AND d.pergunta = :pergunta'
);
$stmtDesafio = $pdo->prepare(
    'UPDATE desafios d INNER JOIN temas t ON t.id = d.tema_id
     SET d.icone = :icone
     WHERE t.nome = :tema_nome AND d.pergunta = :pergunta'
);

// "Encontrado" e' checado sempre via SELECT (nao via rowCount() do
// UPDATE) - rowCount() de UPDATE no MySQL conta linhas REALMENTE
// alteradas, nao linhas casadas; se o icone ja estivesse com o mesmo
// valor (re-execucao), daria falso negativo de "nao encontrado".
foreach ($temas as [$nome, $icone]) {
    $stmtExisteTema->execute(['nome' => $nome]);
    $encontrado = ((int) $stmtExisteTema->fetchColumn()) > 0;

    if ($confirmar && $encontrado) {
        $stmtTema->execute(['icone' => $icone, 'nome' => $nome]);
    }

    $total++;
    echo ($encontrado ? '  OK  ' : '  ??  ') . "tema \"$nome\" -> $icone" . (!$encontrado ? ' (NAO ENCONTRADO)' : '') . "\n";

    if (!$encontrado) {
        $naoEncontrados[] = "tema: $nome";
    }
}

foreach ($desafios as [$temaNome, $pergunta, $icone]) {
    $stmtExisteDesafio->execute(['tema_nome' => $temaNome, 'pergunta' => $pergunta]);
    $encontrado = ((int) $stmtExisteDesafio->fetchColumn()) > 0;

    if ($confirmar && $encontrado) {
        $stmtDesafio->execute(['icone' => $icone, 'tema_nome' => $temaNome, 'pergunta' => $pergunta]);
    }

    $total++;
    $resumoPergunta = mb_substr($pergunta, 0, 60, 'UTF-8') . '...';
    echo ($encontrado ? '  OK  ' : '  ??  ') . "desafio [$temaNome] \"$resumoPergunta\" -> $icone" . (!$encontrado ? ' (NAO ENCONTRADO)' : '') . "\n";

    if (!$encontrado) {
        $naoEncontrados[] = "desafio: $resumoPergunta";
    }
}

if ($confirmar) {
    $pdo->commit();
}

echo str_repeat('-', 60) . "\n";
echo "$total item(ns) processado(s).\n";

if (!empty($naoEncontrados)) {
    echo "\n" . count($naoEncontrados) . " item(ns) NAO encontrado(s) (nome/pergunta pode ter sido editado em producao antes deste deploy):\n";
    foreach ($naoEncontrados as $item) {
        echo "  - $item\n";
    }
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi alterado.\n";
    echo "Para aplicar de verdade, repita o comando com --confirmar.\n";
}
