<?php

/**
 * Fase 32: diagnostico da leitura de presenca no Google Meet - so' LEITURA,
 * nao grava nada no banco nem altera nada no Google. Uso:
 *   php database/testar_google_meet_presenca.php seu-email@tjrr.jus.br CONFERENCE_ID
 *
 * O CONFERENCE_ID sai da coluna google_conference_id de um horario ja
 * integrado e JA ENCERRADO (mentoria_horarios ou oficina_horarios). Horarios
 * criados antes da Fase 32 tem essa coluna vazia - use um horario novo, ou
 * rode antes o database/backfill_conference_id.php.
 *
 * Rode isto ANTES de confiar na captura automatica. Ele responde, em ordem:
 *   1. A edicao do Workspace libera dados de presenca? (causa mais provavel
 *      de lista vazia - ver DeployFase32.md, secao 0)
 *   2. O conference_id resolve pra um space?
 *   3. Existe conferenceRecord, e ele ja esta encerrado (endTime)?
 *   4. Que nome exatamente a API devolve pra cada participante? (e' por esse
 *      nome que o casamento com participantes cadastrados acontece)
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\GoogleServiceAccountAuth;
use App\Services\GoogleMeetPresencaService;
use App\Services\GoogleMeetService;

function linha($ch = '-')
{
    echo str_repeat($ch, 78) . "\n";
}

$email = isset($argv[1]) ? trim($argv[1]) : '';
$conferenceId = isset($argv[2]) ? trim($argv[2]) : '';

if ($email === '' || $conferenceId === '') {
    echo "Uso: php database/testar_google_meet_presenca.php seu-email@tjrr.jus.br CONFERENCE_ID\n\n";
    echo "  seu-email@tjrr.jus.br  organizador do horario (mentor, ou quem criou a oficina)\n";
    echo "  CONFERENCE_ID          valor da coluna google_conference_id do horario\n";
    exit(1);
}

linha('=');
echo "Fase 32 - diagnostico da leitura de presenca no Google Meet\n";
echo "E-mail a impersonar: {$email}\n";
echo "conference_id informado: {$conferenceId}\n";
linha('=');

// Passo 1: token com o escopo NOVO (meetings.space.readonly). Um token de
// Calendar que funciona nao garante nada aqui - o escopo do Meet precisa ter
// sido autorizado separadamente na Delegacao em Todo o Dominio.
echo "Obtendo access token com o escopo do Meet...\n";
echo "  " . GoogleMeetService::ESCOPO_LEITURA . "\n";

$token = GoogleServiceAccountAuth::obterAccessToken($email, GoogleMeetPresencaService::ESCOPOS);

if ($token === null) {
    linha();
    echo "[FALHOU] Nao foi possivel obter access token com o escopo do Meet.\n\n";
    echo "Causas mais provaveis, em ordem:\n";
    echo "  1. O escopo do Meet ainda NAO foi autorizado na Delegacao em Todo o Dominio.\n";
    echo "     O escopo do Calendar (Fase 31) funcionar nao ajuda aqui - cada escopo e'\n";
    echo "     autorizado separadamente, no MESMO registro do Client ID. Ver DeployFase32.md.\n";
    echo "  2. A 'Google Meet REST API' (servico meet.googleapis.com) nao foi ativada\n";
    echo "     no projeto do Google Cloud.\n";
    echo "  3. O e-mail informado nao existe no Workspace tjrr.jus.br.\n";
    exit(1);
}

echo "[OK] Access token obtido - o escopo do Meet esta autorizado.\n\n";

// Passo 2: conference_id -> space. spaces.get aceita tanto o space id quanto
// o meeting code, entao esta etapa funciona sem sabermos de antemao qual dos
// dois formatos a Calendar API devolveu.
echo "Resolvendo o space a partir do conference_id...\n";
$spaceName = GoogleMeetService::buscarSpace($token, $conferenceId);

if ($spaceName === null) {
    linha();
    echo "[FALHOU] O conference_id nao resolveu para nenhum space.\n\n";
    echo "  - Confira se copiou o valor certo da coluna google_conference_id.\n";
    echo "  - Salas muito antigas podem ter deixado de existir.\n";
    exit(1);
}

echo "[OK] Space encontrado: {$spaceName}\n\n";

// Passo 3: o registro da conferencia. endTime vazio = reuniao ainda rolando,
// e nesse caso a captura automatica NAO grava (evita congelar presenca pela
// metade sem sinal visivel na tela).
echo "Procurando o registro da conferencia (conferenceRecord)...\n";
$registro = GoogleMeetService::buscarConferenceRecord($token, $spaceName);

if ($registro === null) {
    linha();
    echo "[ATENCAO] Nenhum conferenceRecord encontrado para esta sala.\n\n";
    echo "Isso pode significar, em ordem de probabilidade:\n";
    echo "  1. A EDICAO DO GOOGLE WORKSPACE nao inclui rastreamento de presenca do Meet.\n";
    echo "     Esta e' a explicacao MAIS PROVAVEL se a reuniao comprovadamente aconteceu:\n";
    echo "     edicoes de entrada nao expoem dados de participantes pela API. Confira em\n";
    echo "     Admin Console > Faturamento/Assinaturas antes de procurar erro no codigo.\n";
    echo "  2. Ninguem entrou na sala (reuniao marcada mas nao realizada).\n";
    echo "  3. A reuniao terminou ha pouco e o Google ainda nao fechou o registro\n";
    echo "     (a captura automatica so' tenta 2h depois do fim, justamente por isso).\n";
    echo "  4. Passaram-se mais de 30 dias do fim - o Google ja apagou o registro.\n";
    exit(1);
}

echo "[OK] conferenceRecord: {$registro['name']}\n";

if ($registro['fim'] === null) {
    echo "[ATENCAO] endTime VAZIO - a conferencia ainda esta em andamento.\n";
    echo "          A captura automatica trataria isso como 'ainda nao pronto' e tentaria\n";
    echo "          de novo depois, em vez de gravar presenca parcial como definitiva.\n\n";
} else {
    echo "          Encerrada em (horario local): {$registro['fim']}\n\n";
}

// Passo 4: quem entrou. E' aqui que se confirma o formato real dos nomes -
// o casamento com participantes cadastrados depende disso, ja que a API nao
// devolve e-mail nenhum.
echo "Listando participantes...\n";
$participantes = GoogleMeetService::listarParticipantes($token, $registro['name']);

if ($participantes === null) {
    linha();
    echo "[FALHOU] A chamada de participantes falhou.\n";
    exit(1);
}

if (empty($participantes)) {
    linha();
    echo "[ATENCAO] O registro existe, mas veio SEM participantes.\n";
    echo "          Assim como no passo anterior, a causa mais provavel e' a edicao do\n";
    echo "          Workspace nao liberar dados de participantes.\n";
    exit(1);
}

linha();
echo "[OK] " . count($participantes) . " participante(s) encontrado(s):\n\n";

foreach ($participantes as $participante) {
    $sessoes = GoogleMeetService::listarSessoes($token, $participante['meet_ref']);

    $nome = $participante['nome_bruto'] !== null ? $participante['nome_bruto'] : '(sem nome)';
    echo "  - {$nome}  [origem: {$participante['tipo_origem']}]\n";
    echo "    ref: {$participante['meet_ref']}\n";

    if ($sessoes === null) {
        echo "    (falha ao listar as sessoes deste participante)\n\n";
        continue;
    }

    $totalSegundos = 0;

    foreach ($sessoes as $sessao) {
        $fim = $sessao['fim'] !== null ? $sessao['fim'] : '(sem fim registrado)';
        echo "    sessao: {$sessao['inicio']} -> {$fim}\n";

        if ($sessao['fim'] !== null) {
            $totalSegundos += strtotime($sessao['fim']) - strtotime($sessao['inicio']);
        }
    }

    echo '    duracao somada: ' . round($totalSegundos / 60) . " min\n\n";
}

linha('=');
echo "Diagnostico concluido.\n\n";
echo "Confira acima, antes de confiar na captura automatica:\n";
echo "  - os nomes batem com os nomes cadastrados dos participantes? (o casamento e'\n";
echo "    por nome, nao por e-mail - a Meet API nao devolve e-mail)\n";
echo "  - os horarios das sessoes estao no fuso local (nao 4h adiantados)?\n";
