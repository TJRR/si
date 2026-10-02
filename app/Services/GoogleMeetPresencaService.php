<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\GoogleServiceAccountAuth;

/**
 * Orquestrador da leitura de presenca no Google Meet, separado de
 * GoogleCalendarSyncService de proposito: aquele escreve evento e
 * convidados de forma sincrona, dentro de um pedido de tela; este le
 * presenca de forma assincrona, numa rotina agendada, horas depois da
 * reuniao, com permissao propria de leitura.
 *
 * Qualquer falha vira null, nunca excecao: a rotina precisa seguir para os
 * proximos horarios.
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
     * Revalida a elegibilidade do organizador a cada chamada. Ver
     * Implantar.md, secao 13.6.
     */
    private function obterToken($organizadorEmail)
    {
        if (!organizadorElegivelGoogle($organizadorEmail)) {
            return null;
        }

        return GoogleServiceAccountAuth::obterAccessToken($organizadorEmail, self::ESCOPOS);
    }
}
