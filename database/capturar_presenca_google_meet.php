<?php

/**
 * Fase 32: captura automatica da presenca real no Google Meet dos horarios de
 * Mentoria/Oficina ja encerrados. Uso:
 *   php database/capturar_presenca_google_meet.php [--limite=N]
 *
 * ESTE E' O PRIMEIRO PROCESSO AGENDADO DO PROJETO. Todo o resto do sistema e'
 * requisicao-resposta; aqui e' o cron do servidor que dispara (ver
 * DeployFase32.md, secao de infraestrutura, pra como instalar/monitorar).
 *
 * NAO tem flag --confirmar, ao contrario dos outros scripts de database/.
 * Aqueles sao operados por um humano que confere o dry-run antes; este roda
 * sozinho a cada 15-30 min, sem ninguem pra confirmar nada. Escrever e'
 * exatamente o trabalho dele.
 *
 * Seguro rodar a qualquer momento e quantas vezes quiser: so' processa o que
 * esta pendente, e a gravacao das sessoes e' idempotente (apaga e regrava as
 * do horario) - reprocessar nunca duplica carga horaria.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\MentoriaRepository;
use App\Repositories\OficinaRepository;
use App\Services\PresencaMeetCapturaService;

// Quantos horarios processar por execucao. Segura rajadas: o backfill
// retroativo pode tornar ate' 28 dias de horarios elegiveis de uma vez, e um
// cron que ficou fora do ar volta com backlog acumulado. Cada horario custa
// ~3 requisicoes + 1 por participante, entao processar tudo de uma vez poderia
// esbarrar em cota do Google e fazer a execucao passar do intervalo do cron.
$limitePadrao = 20;

$limite = $limitePadrao;

function registrar($mensagem)
{
    return '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . "\n";
}

foreach ($argv as $argumento) {
    if (strpos($argumento, '--limite=') === 0) {
        $valor = (int) substr($argumento, strlen('--limite='));

        if ($valor > 0) {
            $limite = $valor;
        }
    }
}

/**
 * Trava contra execucoes sobrepostas. E' defesa SECUNDARIA - a primaria e' a
 * gravacao idempotente das sessoes -, mas evita gastar cota do Google a toa
 * quando uma execucao demora mais que o intervalo do cron. Local ao host: nao
 * cobriria dois servidores rodando o mesmo cron (ver DeployFase32.md).
 */
$arquivoTrava = sys_get_temp_dir() . '/si_presenca_meet.lock';
$trava = fopen($arquivoTrava, 'c');

if ($trava === false) {
    fwrite(STDERR, registrar('Nao foi possivel abrir o arquivo de trava: ' . $arquivoTrava));
    exit(1);
}

if (!flock($trava, LOCK_EX | LOCK_NB)) {
    fwrite(STDOUT, registrar('Execucao anterior ainda em andamento - abortando esta.'));
    exit(0);
}

$captura = new PresencaMeetCapturaService();
$mentorias = new MentoriaRepository();
$oficinas = new OficinaRepository();

$pendentes = [];

foreach ($mentorias->listarPendentesDePresenca($limite) as $horario) {
    $pendentes[] = ['tipo' => 'mentoria', 'horario' => $horario];
}

foreach ($oficinas->listarPendentesDePresenca($limite) as $horario) {
    $pendentes[] = ['tipo' => 'oficina', 'horario' => $horario];
}

fwrite(STDOUT, registrar('Inicio - ' . count($pendentes) . ' horario(s) elegivel(is), limite ' . $limite . ' por tipo.'));

$contagem = [];

foreach ($pendentes as $item) {
    $identificacao = $item['tipo'] . ' #' . (int) $item['horario']['id'];

    // Try/catch por horario: uma falha inesperada (bug, timeout estranho,
    // dado corrompido) nao pode abortar a varredura dos demais. E' um desvio
    // consciente do padrao fail-soft-por-retorno-null do resto do projeto,
    // justificado por ser o unico processo que roda sem supervisao humana.
    try {
        $resultado = $captura->processarHorario($item['tipo'], $item['horario']);
        fwrite(STDOUT, registrar($identificacao . ' -> ' . $resultado));
    } catch (\Throwable $e) {
        $resultado = 'erro';
        fwrite(STDERR, registrar($identificacao . ' -> ERRO: ' . $e->getMessage()));
    }

    $contagem[$resultado] = (isset($contagem[$resultado]) ? $contagem[$resultado] : 0) + 1;
}

$resumo = [];

foreach ($contagem as $resultado => $total) {
    $resumo[] = $resultado . '=' . $total;
}

fwrite(STDOUT, registrar('Fim' . (empty($resumo) ? ' - nada a processar.' : ' - ' . implode(', ', $resumo))));

flock($trava, LOCK_UN);
fclose($trava);
