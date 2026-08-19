<?php

/**
 * Fase 32: preenche retroativamente google_conference_id nos horarios que
 * foram integrados ao Google ANTES desta fase. Uso:
 *   php database/backfill_conference_id.php            (dry-run, so' mostra)
 *   php database/backfill_conference_id.php --confirmar
 *   php database/backfill_conference_id.php --confirmar --limite=50
 *
 * POR QUE E' URGENTE NO DEPLOY: a Fase 31 criava o evento com sala do Meet mas
 * nao guardava o conferenceId, e sem ele nao ha como localizar a sala na Meet
 * API depois. Como o Google apaga os dados de presenca 30 dias apos a reuniao,
 * cada dia sem rodar isto e' presenca perdida em definitivo. Rode logo depois
 * da migration 111.
 *
 * O preenchimento tambem acontece de graca sempre que um admin clica
 * "Verificar/Tentar novamente" num horario - este script so' evita depender de
 * cliques manuais um a um.
 *
 * Janela de 28 dias: 2 dias de folga sobre o limite de 30 do Google. Horarios
 * mais antigos que isso nao tem mais dado nenhum a recuperar.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Repositories\MentoriaRepository;
use App\Repositories\OficinaRepository;
use App\Services\GoogleCalendarSyncService;

$confirmar = in_array('--confirmar', $argv, true);
$limite = 100;

foreach ($argv as $argumento) {
    if (strpos($argumento, '--limite=') === 0) {
        $valor = (int) substr($argumento, strlen('--limite='));

        if ($valor > 0) {
            $limite = $valor;
        }
    }
}

/**
 * Pausa entre chamadas ao Google. O primeiro deploy e' justamente o cenario
 * com mais pendencias acumuladas, e cada horario e' uma chamada a Calendar API
 * - sem intervalo, uma leva grande poderia esbarrar em cota.
 */
$pausaMicrossegundos = 250000;

function horariosSemConferenceId($tabela, $colunaOrganizador, $limite)
{
    $pdo = Database::conexao();
    $stmt = $pdo->prepare(
        "SELECT h.*, u.email AS organizador_email
         FROM {$tabela} h
         JOIN usuarios u ON u.id = h.{$colunaOrganizador}
         WHERE h.integracao_google = 1
           AND h.google_conference_id IS NULL
           AND h.google_event_id IS NOT NULL
           AND h.data_fim >= (NOW() - INTERVAL 28 DAY)
         ORDER BY h.data_fim DESC
         LIMIT :limite"
    );
    $stmt->bindValue('limite', (int) $limite, \PDO::PARAM_INT);
    $stmt->execute();

    return $stmt->fetchAll();
}

$sync = new GoogleCalendarSyncService();
$mentorias = new MentoriaRepository();
$oficinas = new OficinaRepository();

$lotes = [
    ['tipo' => 'mentoria', 'horarios' => horariosSemConferenceId('mentoria_horarios', 'mentor_usuario_id', $limite), 'repo' => $mentorias],
    ['tipo' => 'oficina', 'horarios' => horariosSemConferenceId('oficina_horarios', 'criado_por', $limite), 'repo' => $oficinas],
];

$total = 0;

foreach ($lotes as $lote) {
    $total += count($lote['horarios']);
}

echo "Horarios integrados ao Google, sem conference_id, nos ultimos 28 dias: {$total}\n";

if ($total === 0) {
    echo "Nada a fazer.\n";
    exit(0);
}

if (!$confirmar) {
    foreach ($lotes as $lote) {
        foreach ($lote['horarios'] as $horario) {
            echo "  [{$lote['tipo']} #{$horario['id']}] " . $horario['data_inicio']
                . " - organizador {$horario['organizador_email']}\n";
        }
    }

    echo "\nDry-run: nada foi alterado. Rode de novo com --confirmar para aplicar.\n";
    echo "ATENCAO: reconciliar() tem throttle de 60s por horario - se rodar este script\n";
    echo "         duas vezes seguidas, a segunda passada pula quase tudo. Isso e' protecao,\n";
    echo "         nao falha; espere 1 minuto entre execucoes.\n";
    exit(0);
}

$preenchidos = 0;
$semRetorno = 0;

foreach ($lotes as $lote) {
    foreach ($lote['horarios'] as $horario) {
        $colunas = $sync->reconciliar($lote['tipo'], $horario, $horario['organizador_email']);

        if ($colunas === null || empty($colunas['google_conference_id'])) {
            echo "  [{$lote['tipo']} #{$horario['id']}] sem conference_id no retorno (throttle, evento removido, ou Google indisponivel)\n";
            $semRetorno++;
        } else {
            $lote['repo']->atualizarGoogle((int) $horario['id'], $colunas);
            echo "  [{$lote['tipo']} #{$horario['id']}] conference_id preenchido\n";
            $preenchidos++;
        }

        usleep($pausaMicrossegundos);
    }
}

echo "\nConcluido: {$preenchidos} preenchido(s), {$semRetorno} sem retorno.\n";

if ($semRetorno > 0) {
    echo "Os 'sem retorno' podem ser recuperados rodando de novo daqui a 1 minuto (throttle).\n";
}
