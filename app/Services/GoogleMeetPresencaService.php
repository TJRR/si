<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\GoogleServiceAccountAuth;

/**
 * Fase 32: orquestrador da leitura de presenca no Google Meet - irmao de
 * GoogleCalendarSyncService, mas deliberadamente separado dele.
 *
 * Por que nao reaproveitar GoogleCalendarSyncService: sao preocupacoes e
 * janelas de tempo diferentes. Aquele escreve evento/attendees de forma
 * SINCRONA dentro de um request de admin/participante, com escopos de
 * calendario; este LE presenca de forma ASSINCRONA dentro de um cron, horas
 * depois da reuniao acabar, com um escopo proprio (meetings.space.readonly).
 * Misturar os dois faria o request web carregar um escopo que ele nunca usa.
 *
 * Fail-soft igual ao resto da integracao: qualquer falha vira null, nunca
 * excecao - o cron precisa seguir para os proximos horarios.
 */
class GoogleMeetPresencaService
{
    const ESCOPOS = [
        GoogleMeetService::ESCOPO_LEITURA,
    ];

    /**
     * Captura a presenca de uma sala. Devolve:
     *   [
     *     'conferencia_encerrada' => bool,  // endTime do conferenceRecord preenchido
     *     'participantes' => [
     *        ['meet_ref', 'nome_bruto', 'tipo_origem', 'sessoes' => [['inicio','fim'], ...]],
     *        ...
     *     ],
     *   ]
     * ou null se nao deu pra capturar agora (token, sala, ou registro ainda
     * indisponivel) - nesse caso quem chama trata como "tentar de novo depois",
     * nunca como erro definitivo.
     *
     * ATENCAO a 'conferencia_encerrada': quando false, a reuniao ainda estava
     * acontecendo no momento da leitura (caso real de um horario marcado pra
     * 14h-15h que se estendeu ate' 18h). Gravar esses dados como definitivos
     * congelaria presenca pela metade sem nenhum sinal visivel na tela - por
     * isso quem chama DEVE tratar false como "ainda nao capturou".
     */
    public function capturar($organizadorEmail, $conferenceId)
    {
        $token = $this->obterToken($organizadorEmail);

        if ($token === null) {
            return null;
        }

        $spaceName = GoogleMeetService::buscarSpace($token, $conferenceId);

        if ($spaceName === null) {
            return null;
        }

        $registro = GoogleMeetService::buscarConferenceRecord($token, $spaceName);

        if ($registro === null) {
            return null;
        }

        $participantes = GoogleMeetService::listarParticipantes($token, $registro['name']);

        if ($participantes === null) {
            return null;
        }

        foreach ($participantes as $indice => $participante) {
            $sessoes = GoogleMeetService::listarSessoes($token, $participante['meet_ref']);

            // Uma falha no meio da paginacao de sessoes tornaria a duracao
            // daquela pessoa menor que a real - preferimos abortar a captura
            // inteira e tentar de novo a gravar numero errado como definitivo.
            if ($sessoes === null) {
                return null;
            }

            $participantes[$indice]['sessoes'] = $sessoes;
        }

        return [
            'conferencia_encerrada' => $registro['fim'] !== null,
            'participantes' => $participantes,
        ];
    }

    /**
     * Revalida elegibilidade a cada chamada (defesa em profundidade, mesmo
     * padrao de GoogleCalendarSyncService::obterToken) - o organizador pode
     * ter deixado de ser conta institucional desde que o horario foi criado.
     */
    private function obterToken($organizadorEmail)
    {
        if (!organizadorElegivelGoogle($organizadorEmail)) {
            return null;
        }

        return GoogleServiceAccountAuth::obterAccessToken($organizadorEmail, self::ESCOPOS);
    }
}
