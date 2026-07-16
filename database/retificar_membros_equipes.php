<?php

/**
 * Fase 17 (Bug 9, correcao pontual pedida em 16/07/2026): ajusta a composicao
 * de duas equipes para bater com as listas oficiais retificadas
 * (/home/f3011432/Code/NPI/InscritosEXTretificado.csv e InscritosINTretificado.csv):
 *
 * - "Nexo Documental" (equipe #129): sai Luis Pereira dos Santos e Guilherme
 *   de Paula Alvim Moreira; entram Lailson Herondino e Eliane Ferreira dos
 *   Santos (participantes novos - a lista retificada so' tem nome, sem CPF/
 *   e-mail/telefone, entao esses campos ficam NULL, mesmo padrao ja usado por
 *   outros participantes desta equipe sem contato completo).
 * - "Justiça em Movimento" (equipe #44): sai Santonny Silva Guimaraes.
 *
 * Nao migra a equipe de trilha (isso e' feito por migrar_equipe_trilha.php,
 * script generico ja existente da Fase 17/Bug 9) - so' ajusta membros.
 *
 * Por padrao roda em modo consulta (dry-run): so mostra o que seria feito,
 * sem gravar nada. Para gravar de verdade:
 *   php retificar_membros_equipes.php --confirmar
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\EquipeRepository;
use App\Repositories\ParticipanteRepository;

const EQUIPE_NEXO_DOCUMENTAL = 129;
const EQUIPE_JUSTICA_EM_MOVIMENTO = 44;

$confirmar = in_array('--confirmar', $argv, true);

$equipes = new EquipeRepository();
$participantes = new ParticipanteRepository();

function removerIntegrantePorNome(EquipeRepository $equipes, ParticipanteRepository $participantes, $equipeId, $nomeEquipe, $nomeParticipante, $confirmar)
{
    foreach ($equipes->listarParticipantes($equipeId) as $membro) {
        if (strcasecmp($membro['nome'], $nomeParticipante) === 0) {
            echo "  - remover '{$membro['nome']}' (participante #{$membro['id']}) de '{$nomeEquipe}'\n";

            if ($confirmar) {
                $equipes->desvincularParticipante($equipeId, $membro['id']);
            }

            return true;
        }
    }

    echo "  - AVISO: '{$nomeParticipante}' nao encontrado em '{$nomeEquipe}' - nada a remover.\n";

    return false;
}

echo "=== Nexo Documental (equipe #" . EQUIPE_NEXO_DOCUMENTAL . ") ===\n";

removerIntegrantePorNome($equipes, $participantes, EQUIPE_NEXO_DOCUMENTAL, 'Nexo Documental', 'Luis Pereira dos Santos', $confirmar);
removerIntegrantePorNome($equipes, $participantes, EQUIPE_NEXO_DOCUMENTAL, 'Nexo Documental', 'Guilherme de Paula Alvim Moreira', $confirmar);

foreach (['Lailson Herondino', 'Eliane Ferreira dos Santos'] as $nomeNovo) {
    echo "  - adicionar '{$nomeNovo}' como integrante (homologado)\n";

    if ($confirmar) {
        $participanteId = $participantes->criar($nomeNovo, '', '', '', '');
        $equipes->vincularParticipante(EQUIPE_NEXO_DOCUMENTAL, $participanteId, 'integrante');
        $vinculo = $equipes->buscarVinculo(EQUIPE_NEXO_DOCUMENTAL, $participanteId);
        $equipes->homologarVinculo($vinculo['id'], null);
    }
}

echo "\n=== Justiça em Movimento (equipe #" . EQUIPE_JUSTICA_EM_MOVIMENTO . ") ===\n";

removerIntegrantePorNome($equipes, $participantes, EQUIPE_JUSTICA_EM_MOVIMENTO, 'Justiça em Movimento', 'Santonny Silva Guimaraes', $confirmar);

if (!$confirmar) {
    echo "\nModo consulta (dry-run). Nada foi gravado.\n";
    echo "Para gravar de verdade, rode: php retificar_membros_equipes.php --confirmar\n";
} else {
    echo "\n[ok] retificação de membros aplicada.\n";
}
