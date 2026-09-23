<?php

/**
 * Fase 45: processa a fila de avisos em massa dos eventos (sub-aba
 * "Comunicacao") em lotes pequenos - uso:
 *   php database/processar_comunicacao_evento.php
 *
 * Chamado por cron a cada 1 minuto (ver DeployFase45.md, secao de
 * infraestrutura). O throttle exigido pelo canal de SMTP institucional
 * (no maximo 10 e-mails por lote, pausa de 60s entre lotes) nao precisa de
 * nenhum controle de tempo proprio: cada execucao processa ate' 10
 * destinatarios pendentes NO TOTAL (de todas as campanhas em aberto, mais
 * antigas primeiro - ver EventoComunicacaoRepository::proximosPendentes()),
 * e o proprio intervalo de 1 minuto entre execucoes do cron impoe a pausa.
 *
 * Seguro rodar a qualquer momento e quantas vezes quiser: so' processa o que
 * esta pendente, e cada destinatario e' marcado enviado/falhou de forma
 * definitiva (sem retry automatico) assim que processado.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acesso negado: este script so pode ser executado via linha de comando.');
}

define('SI_BOOT', true);

require __DIR__ . '/../vendor/autoload.php';

use App\Repositories\EventoComunicacaoRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Services\NotificacaoService;

// Quantos destinatarios processar por execucao - o "lote" exigido pela
// decisao de arquitetura do plano mestre (10 e-mails/60s), imposto aqui
// junto com o intervalo do cron entre execucoes.
$limitePorExecucao = 10;

function registrar($mensagem)
{
    return '[' . date('Y-m-d H:i:s') . '] ' . $mensagem . "\n";
}

/**
 * Trava contra execucoes sobrepostas - mesmo padrao de
 * capturar_presenca_google_meet.php (Fase 32). Local ao host: nao cobriria
 * dois servidores rodando o mesmo cron.
 */
$arquivoTrava = sys_get_temp_dir() . '/si_comunicacao_evento.lock';
$trava = fopen($arquivoTrava, 'c');

if ($trava === false) {
    fwrite(STDERR, registrar('Nao foi possivel abrir o arquivo de trava: ' . $arquivoTrava));
    exit(1);
}

if (!flock($trava, LOCK_EX | LOCK_NB)) {
    fwrite(STDOUT, registrar('Execucao anterior ainda em andamento - abortando esta.'));
    exit(0);
}

$comunicacoes = new EventoComunicacaoRepository();
$notificacoes = new NotificacaoService();
$eventos = new SemanaInovacaoRepository();
$eventosEmCache = [];

$pendentes = $comunicacoes->proximosPendentes($limitePorExecucao);

fwrite(STDOUT, registrar('Inicio - ' . count($pendentes) . ' destinatario(s) pendente(s) neste lote.'));

$contagem = ['enviado' => 0, 'falhou' => 0];
$comunicacaoIdsAfetadas = [];

foreach ($pendentes as $destinatario) {
    $sucesso = false;

    try {
        // Fase 51: a fila deixou de levar so' o aviso em massa. O tipo da
        // campanha diz qual texto montar, e o convite do autor importado
        // monta o endereco de definir senha a partir do token guardado no
        // destinatario (o endereco nunca fica pronto no banco).
        $tipo = isset($destinatario['tipo']) ? $destinatario['tipo'] : 'comunicado';

        if ($tipo === 'convite_autor_importado' || $tipo === 'aviso_autor_importado') {
            $eventoId = (int) $destinatario['evento_id'];

            if (!isset($eventosEmCache[$eventoId])) {
                $eventosEmCache[$eventoId] = $eventos->buscarPorId($eventoId);
            }

            $evento = $eventosEmCache[$eventoId];
        }

        if ($tipo === 'convite_autor_importado' && !empty($destinatario['token_senha'])) {
            $sucesso = $notificacoes->conviteAutorTrabalhoImportado(
                $destinatario['usuario_email'],
                $destinatario['usuario_nome'],
                $evento,
                urlAbsoluta('auth/definirSenha/' . $destinatario['token_senha'])
            );
        } elseif ($tipo === 'aviso_autor_importado') {
            $sucesso = $notificacoes->avisoAutorTrabalhoImportado(
                $destinatario['usuario_email'],
                $destinatario['usuario_nome'],
                $evento
            );
        } else {
            $sucesso = $notificacoes->avisoIndividualEvento(
                $destinatario['usuario_email'],
                ['id' => $destinatario['evento_id']],
                $destinatario['assunto'],
                $destinatario['corpo_html']
            );
        }
    } catch (\Throwable $e) {
        fwrite(STDERR, registrar('Destinatario #' . $destinatario['destinatario_id'] . ' -> ERRO: ' . $e->getMessage()));
    }

    $comunicacoes->marcarProcessado($destinatario['destinatario_id'], $sucesso);
    $contagem[$sucesso ? 'enviado' : 'falhou']++;
    $comunicacaoIdsAfetadas[$destinatario['comunicacao_id']] = true;
}

foreach (array_keys($comunicacaoIdsAfetadas) as $comunicacaoId) {
    $comunicacoes->atualizarContadoresEConcluir($comunicacaoId);
}

fwrite(STDOUT, registrar('Fim - enviado=' . $contagem['enviado'] . ', falhou=' . $contagem['falhou'] . '.'));

flock($trava, LOCK_UN);
fclose($trava);
