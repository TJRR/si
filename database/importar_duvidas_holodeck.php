<?php

/**
 * Fase 31: importa pro Tira-Duvidas as perguntas que a Equipe holodeck
 * mandou por e-mail antes de existir o canal estruturado (Fase 29) -
 * conteudo em Duvidas_Equipe_holodeck.md, raiz do projeto. Decisao do
 * usuario: uma duvida por pergunta (39 no total), nao agrupado por secao,
 * pra manter rastreio fino de qual foi respondida.
 *
 * Reusa DuvidaRepository::criar() (mesma rotina de DuvidaController::
 * salvarNova()) e notifica os administradores do concurso igual a uma
 * duvida registrada pela tela normal - do ponto de vista do sistema, nao
 * ha diferenca entre uma duvida criada aqui e uma criada pelo participante.
 *
 * Autoria: o LIDER da equipe (mesmo participante que teria digitado isso na
 * tela, se a equipe tivesse usado o sistema desde o inicio).
 *
 * Uso:
 *   php database/importar_duvidas_holodeck.php              (dry-run, so mostra)
 *   php database/importar_duvidas_holodeck.php --confirmar  (aplica de verdade)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\DuvidaRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\TrilhaRepository;

function linha($ch = '-')
{
    echo str_repeat($ch, 78) . "\n";
}

$confirmar = in_array('--confirmar', $argv, true);

// Ajuste aqui se o nome oficial cadastrado no sistema for diferente de
// "holodeck" (o script mostra candidatas semelhantes se nao achar exato).
$nomeEquipe = 'holodeck';

$perguntas = [
    ['secao' => 'Canais de entrada e exemplos', 'numero' => 1, 'texto' => 'Para cada canal (E-mail, SEI, Aranda), qual a proporção aproximada do volume de demandas (%), quem são os demandantes típicos e quais tipos de solicitação mais chegam por cada um?'],
    ['secao' => 'Canais de entrada e exemplos', 'numero' => 2, 'texto' => 'Quais tipos de demanda devem ser priorizados no MVP (ex.: bug, melhoria, suporte, infraestrutura)? A automatização deve ser total ou passar sempre por triagem humana?'],
    ['secao' => 'Canais de entrada e exemplos', 'numero' => 3, 'texto' => 'Existe algum tipo de demanda que nunca deve ser automatizada (sigilo, urgência extrema, exceção institucional)?'],
    ['secao' => 'E-mail corporativo', 'numero' => 4, 'texto' => 'Qual serviço de e-mail é utilizado (Microsoft 365 / Exchange, Gmail, outro)?'],
    ['secao' => 'E-mail corporativo', 'numero' => 5, 'texto' => 'Existe caixa de e-mail dedicada para demandas da SubSi? Qual endereço?'],
    ['secao' => 'E-mail corporativo', 'numero' => 6, 'texto' => 'A STI permite conta de serviço ou aplicativo registrado (ex.: Microsoft Graph) para leitura programática de e-mails?'],
    ['secao' => 'E-mail corporativo', 'numero' => 7, 'texto' => 'As demandas chegam em caixa centralizada ou em caixas pessoais com encaminhamento?'],
    ['secao' => 'Aranda', 'numero' => 8, 'texto' => 'Qual versão/plataforma do Aranda é utilizada (cloud ou on-premise)?'],
    ['secao' => 'Aranda', 'numero' => 9, 'texto' => 'Será garantido acesso a ambiente de sandbox/homologação para testes de integração?'],
    ['secao' => 'Aranda', 'numero' => 10, 'texto' => 'Existe API REST, webhook ou outro mecanismo de integração disponível?'],
    ['secao' => 'Aranda', 'numero' => 11, 'texto' => 'Quais filas, categorias ou tipos de chamado entram no escopo? Quais devem ser ignorados?'],
    ['secao' => 'Aranda', 'numero' => 12, 'texto' => 'A integração deve ser unidirecional (Aranda → plataforma) ou bidirecional (atualizar status no Aranda ao concluir a demanda)?'],
    ['secao' => 'SEI', 'numero' => 13, 'texto' => 'Qual versão do SEI é utilizada no TJRR?'],
    ['secao' => 'SEI', 'numero' => 14, 'texto' => 'Existe API documentada para integração? Quem fornece a documentação?'],
    ['secao' => 'SEI', 'numero' => 15, 'texto' => 'Há ambiente de homologação/sandbox do SEI disponível para testes?'],
    ['secao' => 'SEI', 'numero' => 16, 'texto' => 'Quais tipos de processo ou documento no SEI representam demanda de desenvolvimento/sistemas?'],
    ['secao' => 'SEI', 'numero' => 17, 'texto' => 'A integração com o SEI deve ser unidirecional ou bidirecional (registrar andamento no processo ao concluir)?'],
    ['secao' => 'Git', 'numero' => 18, 'texto' => 'Qual plataforma Git institucional é utilizada (GitLab, GitHub Enterprise, Bitbucket, outra)?'],
    ['secao' => 'Git', 'numero' => 19, 'texto' => 'Quantos repositórios recebem demandas da SubSi? Quais são os principais?'],
    ['secao' => 'Git', 'numero' => 20, 'texto' => 'Existe template obrigatório de issue (labels, milestone, assignee)?'],
    ['secao' => 'Git', 'numero' => 21, 'texto' => 'A issue no Git deve ser criada automaticamente ao registrar a demanda ou após aprovação na triagem?'],
    ['secao' => 'Git', 'numero' => 22, 'texto' => 'É necessário sincronizar status entre a plataforma e o Git (ex.: fechar issue = concluir demanda)?'],
    ['secao' => 'Plak e fluxo operacional', 'numero' => 23, 'texto' => 'A nova solução deve integrar com o Plak, substituí-lo ou conviver em paralelo durante uma transição?'],
    ['secao' => 'Plak e fluxo operacional', 'numero' => 24, 'texto' => 'Quais campos obrigatórios existem hoje no Plak (descrição, prazo, tipo, desenvolvedor, solicitante etc.)?'],
    ['secao' => 'Plak e fluxo operacional', 'numero' => 25, 'texto' => 'Quais estados/status são utilizados no Plak (ex.: aberto, em triagem, em desenvolvimento, concluído)?'],
    ['secao' => 'Plak e fluxo operacional', 'numero' => 26, 'texto' => 'Quem realiza a triagem das demandas hoje?'],
    ['secao' => 'Plak e fluxo operacional', 'numero' => 27, 'texto' => 'Existe critério formal de prioridade (urgente, alta, normal)?'],
    ['secao' => 'Plak e fluxo operacional', 'numero' => 28, 'texto' => 'Como é feita a atribuição de desenvolvedor (manual, rodízio, por sistema/squad)?'],
    ['secao' => 'Plak e fluxo operacional', 'numero' => 29, 'texto' => 'Existem SLAs ou prazos por tipo de demanda?'],
    ['secao' => 'Plak e fluxo operacional', 'numero' => 30, 'texto' => 'Como tratam hoje demandas duplicadas em mais de um canal? Deve haver deduplicação automática?'],
    ['secao' => 'Plak e fluxo operacional', 'numero' => 31, 'texto' => 'É necessário migrar histórico do Plak ou registrar apenas novas demandas a partir do go-live?'],
    ['secao' => 'Relatórios e indicadores', 'numero' => 32, 'texto' => 'Além do relatório mensal e do anual de relevância, existem outros relatórios ou painéis exigidos?'],
    ['secao' => 'Relatórios e indicadores', 'numero' => 33, 'texto' => 'Quanto tempo leva hoje para elaborar o relatório mensal? E o anual?'],
    ['secao' => 'Relatórios e indicadores', 'numero' => 34, 'texto' => 'Quais seções são obrigatórias e não podem faltar na automação?'],
    ['secao' => 'Relatórios e indicadores', 'numero' => 35, 'texto' => 'O relatório descritivo deve usar texto literal dos chamados ou resumo elaborado pela equipe?'],
    ['secao' => 'Relatórios e indicadores', 'numero' => 36, 'texto' => 'Vocês já medem algum indicador hoje (tempo de cadastro, entregas/mês, % de retrabalho)?'],
    ['secao' => 'Volume, usuários e expectativas', 'numero' => 37, 'texto' => 'Quantas demandas/mês em média? Qual o volume de pico?'],
    ['secao' => 'Volume, usuários e expectativas', 'numero' => 38, 'texto' => 'Quantos usuários utilizarão o sistema (analistas, desenvolvedores, coordenação, demandantes)?'],
    ['secao' => 'Volume, usuários e expectativas', 'numero' => 39, 'texto' => 'O que vocês esperam de um produto utilizável no dia a dia?'],
];

$equipes = new EquipeRepository();
$trilhas = new TrilhaRepository();
$duvidas = new DuvidaRepository();
$perfis = new PerfilRepository();
$notificacoes = new NotificacaoPainelRepository();

$equipe = $equipes->buscarPorNome($nomeEquipe);

if ($equipe === null) {
    echo "Equipe \"$nomeEquipe\" nao encontrada por nome exato.\n";
    linha();
    $semelhantes = $equipes->listarSemelhantesPorNome($nomeEquipe);
    if (empty($semelhantes)) {
        echo "Nenhuma candidata parecida encontrada. Confira o nome exato cadastrado no sistema.\n";
    } else {
        echo "Candidatas semelhantes:\n";
        foreach ($semelhantes as $c) {
            printf("id %-4d | %s\n", $c['id'], $c['nome_equipe']);
        }
        echo "\nAjuste a variavel \$nomeEquipe no topo do script com o nome exato e rode de novo.\n";
    }
    exit(1);
}

$participantesEquipe = $equipes->listarParticipantes($equipe['id']);
$lider = null;
foreach ($participantesEquipe as $p) {
    if ($p['papel'] === 'lider') {
        $lider = $p;
        break;
    }
}

if ($lider === null) {
    echo "Equipe \"{$equipe['nome_equipe']}\" (id {$equipe['id']}) nao tem lider cadastrado. Abortando.\n";
    exit(1);
}

$trilha = $trilhas->buscarPorId($equipe['trilha_id']);

echo "Equipe: {$equipe['nome_equipe']} (id {$equipe['id']})\n";
echo "Trilha: {$trilha['nome']} (id {$trilha['id']}, concurso {$trilha['concurso_id']})\n";
echo "Lider: {$lider['nome']} (participante_id {$lider['id']})"
    . (empty($lider['email']) ? ' - ATENCAO: sem e-mail cadastrado (a duvida sera criada mesmo assim, mas o lider nao recebe notificacao por e-mail de resposta)' : " <{$lider['email']}>")
    . "\n";
linha('=');
printf("Serao criadas %d duvidas (uma por pergunta), autoria do lider acima.\n", count($perguntas));
linha('=');

$criadas = 0;

foreach ($perguntas as $item) {
    $texto = '[' . $item['secao'] . '] ' . $item['numero'] . '. ' . $item['texto'];

    printf("%2d. [%s] %s\n", $item['numero'], $item['secao'], mb_substr($item['texto'], 0, 70));

    if ($confirmar) {
        $duvidaId = $duvidas->criar(
            $lider['id'],
            $equipe['id'],
            $trilha['concurso_id'],
            $trilha['id'],
            $texto,
            null,
            null
        );

        foreach ($perfis->listarUsuariosPorPerfilConcurso('administrador', $trilha['concurso_id']) as $admin) {
            $notificacoes->criar(
                (int) $admin['id'],
                'duvida_nova',
                'Nova dúvida registrada',
                '"' . $lider['nome'] . '" (equipe "' . $equipe['nome_equipe'] . '") registrou uma dúvida.',
                ['url' => url('duvidaAdmin/ver/' . (int) $duvidaId)]
            );
        }

        $criadas++;
    }
}

linha();
if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi criado. Repita com --confirmar para aplicar de verdade.\n";
} else {
    echo "\nConcluido: $criadas duvida(s) criada(s) para a equipe \"{$equipe['nome_equipe']}\", notificacoes enviadas aos administradores do concurso.\n";
}
