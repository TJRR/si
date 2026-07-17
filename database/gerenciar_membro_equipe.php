<?php

/**
 * Fase 18: versao GENERICA do que era retificar_membros_equipes.php (Fase 17,
 * Bug 9 - script direcionado, com nomes/ids de equipe fixos em constantes).
 * Este script serve qualquer equipe/qualquer integrante, com 3 acoes:
 *
 *   adicionar  - vincula um participante novo (ou ja existente, por CPF/e-mail)
 *                a uma equipe.
 *   remover    - desvincula um integrante da equipe (nao apaga o participante,
 *                so' o vinculo com esta equipe - mesma semantica de
 *                EquipeRepository::desvincularParticipante).
 *   substituir - remove um integrante e adiciona outro no lugar dele, mantendo
 *                o mesmo papel (lider/integrante) que o removido tinha.
 *
 * Identificacao da equipe: --equipe-id=123 OU --equipe-nome="Nome da Equipe"
 * (mesmo padrao ja usado em migrar_equipe_trilha.php).
 *
 * Identificacao do integrante a remover/substituir: --participante-id=456 OU
 * --participante-nome="Nome Exato" (se o nome bater com mais de um integrante
 * da mesma equipe, o script lista os ids e para, igual definir_cpf_teste.php).
 *
 * --homologar (opcional, so' vale para adicionar/substituir): marca o vinculo
 * novo como ja homologado, sem passar pela fila de homologacao do admin - use
 * so' quando o integrante ja foi validado por outro meio (ex.: lista oficial
 * retificada). Sem essa flag, o vinculo novo nasce 'pendente' (padrao do
 * sistema) e aparece na tela Inscritos aguardando homologacao normal.
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito,
 * sem gravar nada. Para gravar de verdade, repita o comando com --confirmar.
 *
 * Exemplos:
 *   php database/gerenciar_membro_equipe.php --equipe-id=129 --acao=adicionar \
 *     --nome="Lailson Herondino" --homologar --confirmar
 *
 *   php database/gerenciar_membro_equipe.php --equipe-nome="Justiça em Movimento" \
 *     --acao=remover --participante-nome="Santonny Silva Guimaraes" --confirmar
 *
 *   php database/gerenciar_membro_equipe.php --equipe-id=129 --acao=substituir \
 *     --participante-nome="Luis Pereira dos Santos" --nome="Lailson Herondino" \
 *     --homologar --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\EquipeRepository;
use App\Repositories\ParticipanteRepository;

function lerArgumento($argv, $nome)
{
    foreach ($argv as $arg) {
        if (strpos($arg, "--{$nome}=") === 0) {
            return substr($arg, strlen("--{$nome}="));
        }
    }

    return null;
}

function mostrarUso()
{
    echo "Uso:\n";
    echo "  php database/gerenciar_membro_equipe.php (--equipe-id=123 | --equipe-nome=\"Nome\") --acao=adicionar\n";
    echo "    --nome=\"Fulano da Silva\" [--cpf=... --email=... --telefone=... --vinculo=... --papel=integrante] [--homologar] [--confirmar]\n";
    echo "  php database/gerenciar_membro_equipe.php (--equipe-id=123 | --equipe-nome=\"Nome\") --acao=remover\n";
    echo "    (--participante-id=456 | --participante-nome=\"Fulano\") [--confirmar]\n";
    echo "  php database/gerenciar_membro_equipe.php (--equipe-id=123 | --equipe-nome=\"Nome\") --acao=substituir\n";
    echo "    (--participante-id=456 | --participante-nome=\"Fulano\") --nome=\"Novo Nome\" [--cpf=... --email=... --telefone=... --vinculo=...] [--homologar] [--confirmar]\n";
}

/**
 * Acha o integrante da equipe por id ou por nome exato (case-insensitive).
 * Se o nome bater com mais de um integrante, lista os ids e retorna null
 * (quem chama trata como erro e para o script).
 */
function localizarIntegrante(EquipeRepository $equipes, $equipeId, $participanteId, $participanteNome)
{
    $membros = $equipes->listarParticipantes($equipeId);

    if ($participanteId !== null) {
        foreach ($membros as $membro) {
            if ((int) $membro['id'] === (int) $participanteId) {
                return $membro;
            }
        }

        echo "ERRO: participante id {$participanteId} não é integrante desta equipe.\n";
        return null;
    }

    $encontrados = array_values(array_filter($membros, function ($membro) use ($participanteNome) {
        return strcasecmp($membro['nome'], $participanteNome) === 0;
    }));

    if (empty($encontrados)) {
        echo "ERRO: nenhum integrante desta equipe com o nome \"{$participanteNome}\".\n";
        return null;
    }

    if (count($encontrados) > 1) {
        echo "ERRO: mais de um integrante desta equipe com o nome \"{$participanteNome}\" - rode de novo com --participante-id= usando um destes ids:\n";
        foreach ($encontrados as $membro) {
            echo "  id {$membro['id']} - papel: {$membro['papel']}\n";
        }
        return null;
    }

    return $encontrados[0];
}

/**
 * Vincula um participante (novo ou ja existente por CPF/e-mail) a uma
 * equipe, com o papel e status de homologacao informados.
 */
function adicionarIntegrante(EquipeRepository $equipes, ParticipanteRepository $participantes, $equipeId, array $dados, $papel, $homologar, $confirmar)
{
    $participante = null;

    if (!empty($dados['cpf'])) {
        $participante = $participantes->buscarPorCpf($dados['cpf']);
    }

    if ($participante === null && !empty($dados['email'])) {
        $participante = $participantes->buscarPorEmail($dados['email']);
    }

    if ($participante !== null) {
        echo "  - participante já existe no sistema: '{$participante['nome']}' (id {$participante['id']}) - reaproveitando cadastro, não criando duplicado.\n";
    } else {
        echo "  - criar novo participante: '{$dados['nome']}'" . (!empty($dados['cpf']) ? " (CPF {$dados['cpf']})" : '') . "\n";
    }

    echo "  - vincular à equipe com papel '{$papel}'" . ($homologar ? ' e já homologar' : ' (fica pendente de homologação)') . "\n";

    if (!$confirmar) {
        return;
    }

    if ($participante === null) {
        $participanteId = $participantes->criar($dados['nome'], $dados['cpf'], $dados['email'], $dados['telefone'], $dados['vinculo']);
    } else {
        $participanteId = $participante['id'];
    }

    $equipes->vincularParticipante($equipeId, $participanteId, $papel);

    if ($homologar) {
        $vinculo = $equipes->buscarVinculo($equipeId, $participanteId);
        $equipes->homologarVinculo($vinculo['id'], null);
    }
}

$confirmar = in_array('--confirmar', $argv, true);
$homologar = in_array('--homologar', $argv, true);
$acao = lerArgumento($argv, 'acao');
$equipeId = lerArgumento($argv, 'equipe-id');
$equipeNome = lerArgumento($argv, 'equipe-nome');
$participanteId = lerArgumento($argv, 'participante-id');
$participanteNome = lerArgumento($argv, 'participante-nome');
$novoNome = lerArgumento($argv, 'nome');
$novoCpf = (string) lerArgumento($argv, 'cpf');
$novoEmail = (string) lerArgumento($argv, 'email');
$novoTelefone = (string) lerArgumento($argv, 'telefone');
$novoVinculo = (string) lerArgumento($argv, 'vinculo');
$papel = lerArgumento($argv, 'papel') ?: 'integrante';

if (!in_array($acao, ['adicionar', 'remover', 'substituir'], true) || ($equipeId === null && $equipeNome === null)) {
    mostrarUso();
    exit(1);
}

$equipes = new EquipeRepository();
$participantes = new ParticipanteRepository();

$equipe = $equipeId !== null ? $equipes->buscarPorId((int) $equipeId) : $equipes->buscarPorNome($equipeNome);

if ($equipe === null) {
    echo "ERRO: equipe não encontrada (id='{$equipeId}', nome='{$equipeNome}').\n";

    if ($equipeNome !== null) {
        $semelhantes = $equipes->listarSemelhantesPorNome($equipeNome);

        if (!empty($semelhantes)) {
            echo "Equipes com nome parecido:\n";
            foreach ($semelhantes as $candidata) {
                echo "  id {$candidata['id']} - '{$candidata['nome_equipe']}'\n";
            }
        }
    }

    exit(1);
}

echo "Equipe: '{$equipe['nome_equipe']}' (id {$equipe['id']})\n";

if ($acao === 'adicionar') {
    if (empty($novoNome)) {
        echo "ERRO: informe --nome=\"Nome do novo integrante\".\n";
        exit(1);
    }

    adicionarIntegrante($equipes, $participantes, $equipe['id'], [
        'nome' => $novoNome,
        'cpf' => $novoCpf,
        'email' => $novoEmail,
        'telefone' => $novoTelefone,
        'vinculo' => $novoVinculo,
    ], $papel, $homologar, $confirmar);
} elseif ($acao === 'remover') {
    if ($participanteId === null && empty($participanteNome)) {
        echo "ERRO: informe --participante-id= ou --participante-nome= para identificar quem remover.\n";
        exit(1);
    }

    $membro = localizarIntegrante($equipes, $equipe['id'], $participanteId, $participanteNome);

    if ($membro === null) {
        exit(1);
    }

    echo "  - remover '{$membro['nome']}' (participante #{$membro['id']}, papel '{$membro['papel']}')\n";

    if ($confirmar) {
        $equipes->desvincularParticipante($equipe['id'], $membro['id']);
    }
} else { // substituir
    if ($participanteId === null && empty($participanteNome)) {
        echo "ERRO: informe --participante-id= ou --participante-nome= para identificar quem sai.\n";
        exit(1);
    }

    if (empty($novoNome)) {
        echo "ERRO: informe --nome=\"Nome de quem entra\".\n";
        exit(1);
    }

    $membroAntigo = localizarIntegrante($equipes, $equipe['id'], $participanteId, $participanteNome);

    if ($membroAntigo === null) {
        exit(1);
    }

    $papelHerdado = $membroAntigo['papel'];

    echo "  - remover '{$membroAntigo['nome']}' (participante #{$membroAntigo['id']}, papel '{$papelHerdado}')\n";

    if ($confirmar) {
        $equipes->desvincularParticipante($equipe['id'], $membroAntigo['id']);
    }

    adicionarIntegrante($equipes, $participantes, $equipe['id'], [
        'nome' => $novoNome,
        'cpf' => $novoCpf,
        'email' => $novoEmail,
        'telefone' => $novoTelefone,
        'vinculo' => $novoVinculo,
    ], $papelHerdado, $homologar, $confirmar);
}

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi gravado.\n";
    echo "Para gravar de verdade, repita o comando com --confirmar.\n";
} else {
    echo "\n[ok] alteração aplicada.\n";
}
