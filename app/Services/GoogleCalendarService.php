<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 31: chamadas REST cruas a Calendar API v3, sempre com um access
 * token ja obtido (App\Core\GoogleServiceAccountAuth) impersonando o
 * organizador. Mesmo estilo curl cru de GoogleOAuth/ItiValidadorService -
 * fail-soft (qualquer falha devolve null, nunca lanca excecao), pra nunca
 * travar o fluxo de admin/participante se o Google estiver fora do ar.
 *
 * Fuso fixo do sistema (America/Boa_Vista, config/config.php) - nao ha
 * timezone por usuario, entao o campo timeZone do evento e' sempre esse.
 */
class GoogleCalendarService
{
    const URL_BASE = 'https://www.googleapis.com/calendar/v3';
    const TIMEZONE = 'America/Boa_Vista';
    const TIMEOUT_SEGUNDOS = 8;

    /**
     * Busca a agenda secundaria do organizador por nome exato; cria via
     * calendars.insert se nao existir (o proprio insert ja inclui a agenda
     * na calendarList do organizador impersonado, sem chamada extra).
     * Risco aceito: se o nome do concurso mudar depois, a proxima busca por
     * nome nao acha mais a agenda antiga e cria uma duplicada - baixa
     * probabilidade, documentado no plano da Fase 31.
     */
    public static function garantirCalendarioSecundario($accessToken, $nomeCalendario)
    {
        $existente = self::buscarCalendarioPorNome($accessToken, $nomeCalendario);

        if ($existente !== null) {
            return $existente;
        }

        $resposta = self::requisitar(self::URL_BASE . '/calendars', $accessToken, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode([
                'summary' => $nomeCalendario,
                'timeZone' => self::TIMEZONE,
            ]),
        ]);

        return isset($resposta['id']) ? $resposta['id'] : null;
    }

    /**
     * Cria o evento com pedido de sala do Meet (conferenceDataVersion=1).
     * $dados: titulo, descricao, data_inicio, data_fim (Y-m-d H:i:s), attendees (array de e-mails).
     * Devolve ['event_id', 'meet_link', 'meet_status' => success|pending|failure, 'attendees' => [email => responseStatus]] ou null.
     */
    public static function criarEvento($accessToken, $calendarId, array $dados)
    {
        $corpo = [
            'summary' => $dados['titulo'],
            'description' => isset($dados['descricao']) ? $dados['descricao'] : '',
            'start' => ['dateTime' => self::formatarDataHoraIso($dados['data_inicio']), 'timeZone' => self::TIMEZONE],
            'end' => ['dateTime' => self::formatarDataHoraIso($dados['data_fim']), 'timeZone' => self::TIMEZONE],
            'attendees' => self::montarAttendees(isset($dados['attendees']) ? $dados['attendees'] : []),
            'conferenceData' => [
                'createRequest' => [
                    'requestId' => bin2hex(random_bytes(16)),
                    'conferenceSolutionKey' => ['type' => 'hangoutsMeet'],
                ],
            ],
        ];

        $url = self::urlEventos($calendarId) . '?conferenceDataVersion=1&sendUpdates=all';

        $resposta = self::requisitar($url, $accessToken, [
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($corpo),
        ]);

        return $resposta !== null && isset($resposta['id']) ? self::interpretarEvento($resposta) : null;
    }

    /**
     * Reconciliacao sob demanda: busca o estado atual do evento (Meet
     * pendente -> resolvido, e/ou RSVP de attendees) sem alterar nada.
     */
    public static function buscarEvento($accessToken, $calendarId, $eventId)
    {
        $url = self::urlEventos($calendarId) . '/' . rawurlencode($eventId);

        $resposta = self::requisitar($url, $accessToken, [CURLOPT_HTTPGET => true]);

        return $resposta !== null && isset($resposta['id']) ? self::interpretarEvento($resposta) : null;
    }

    /**
     * PATCH restrito ao campo attendees - nunca reenvia conferenceData,
     * entao o hangoutLink ja gerado e' preservado. $emails e' a lista
     * COMPLETA recomputada do banco (substitui o array inteiro no Google,
     * nao faz merge incremental - ver justificativa no plano da Fase 31).
     */
    public static function atualizarAttendees($accessToken, $calendarId, $eventId, array $emails)
    {
        $url = self::urlEventos($calendarId) . '/' . rawurlencode($eventId) . '?sendUpdates=all';

        $resposta = self::requisitar($url, $accessToken, [
            CURLOPT_CUSTOMREQUEST => 'PATCH',
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode(['attendees' => self::montarAttendees($emails)]),
        ]);

        return $resposta !== null && isset($resposta['id']) ? self::interpretarEvento($resposta) : null;
    }

    public static function cancelarEvento($accessToken, $calendarId, $eventId)
    {
        $ch = curl_init(self::urlEventos($calendarId) . '/' . rawurlencode($eventId) . '?sendUpdates=all');

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SEGUNDOS,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $corpo = curl_exec($ch);
        $erroCurl = curl_error($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 204 = removido agora; 410 = ja tinha sido removido antes (idempotente, trata como sucesso).
        return $erroCurl === '' && ($codigoHttp === 204 || $codigoHttp === 410);
    }

    /**
     * Fase 31: diagnostico de conectividade (database/testar_google_calendar.php)
     * - lista as agendas visiveis pro organizador impersonado, sem criar/
     * alterar nada. Devolve null em qualquer falha (token invalido,
     * delegacao nao autorizada, etc.) ou um array de ['id','summary'].
     */
    public static function listarCalendarios($accessToken)
    {
        $resposta = self::requisitar(self::URL_BASE . '/users/me/calendarList', $accessToken, [CURLOPT_HTTPGET => true]);

        if ($resposta === null || !isset($resposta['items']) || !is_array($resposta['items'])) {
            return null;
        }

        $calendarios = [];

        foreach ($resposta['items'] as $item) {
            $calendarios[] = [
                'id' => isset($item['id']) ? $item['id'] : null,
                'summary' => isset($item['summary']) ? $item['summary'] : null,
            ];
        }

        return $calendarios;
    }

    private static function buscarCalendarioPorNome($accessToken, $nomeCalendario)
    {
        $pagina = null;

        do {
            $url = self::URL_BASE . '/users/me/calendarList?minAccessRole=owner'
                . ($pagina !== null ? '&pageToken=' . urlencode($pagina) : '');

            $resposta = self::requisitar($url, $accessToken, [CURLOPT_HTTPGET => true]);

            if ($resposta === null || !isset($resposta['items']) || !is_array($resposta['items'])) {
                return null;
            }

            foreach ($resposta['items'] as $item) {
                if (isset($item['summary']) && $item['summary'] === $nomeCalendario) {
                    return isset($item['id']) ? $item['id'] : null;
                }
            }

            $pagina = isset($resposta['nextPageToken']) ? $resposta['nextPageToken'] : null;
        } while ($pagina !== null);

        return null;
    }

    /**
     * Normaliza a resposta bruta da API num formato estavel pro resto do
     * sistema. hangoutLink so aparece quando a sala ja foi gerada; enquanto
     * isso, conferenceData.createRequest.status.statusCode informa
     * pending/failure (a criacao do Meet e' assincrona do lado do Google).
     */
    private static function interpretarEvento(array $evento)
    {
        $meetLink = isset($evento['hangoutLink']) ? $evento['hangoutLink'] : null;
        $statusCode = $evento['conferenceData']['createRequest']['status']['statusCode'] ?? null;

        // Fase 32: identificador da sala do Meet - e' o que a Meet API precisa
        // pra localizar o conferenceRecord depois que a reuniao acabar (ver
        // App\Services\GoogleMeetService). A Fase 31 nao capturava isso, entao
        // horarios criados antes ficam sem - o backfill retroativo acontece
        // aqui mesmo, de graca, na proxima reconciliacao do horario.
        $conferenceId = $evento['conferenceData']['conferenceId'] ?? null;

        if ($meetLink !== null) {
            $meetStatus = 'success';
        } elseif ($statusCode === 'failure') {
            $meetStatus = 'failure';
        } else {
            $meetStatus = 'pending';
        }

        $attendees = [];

        if (isset($evento['attendees']) && is_array($evento['attendees'])) {
            foreach ($evento['attendees'] as $attendee) {
                if (isset($attendee['email'])) {
                    $attendees[$attendee['email']] = isset($attendee['responseStatus']) ? $attendee['responseStatus'] : 'needsAction';
                }
            }
        }

        return [
            'event_id' => $evento['id'],
            'meet_link' => $meetLink,
            'meet_status' => $meetStatus,
            'conference_id' => $conferenceId,
            'attendees' => $attendees,
        ];
    }

    private static function montarAttendees(array $emails)
    {
        $attendees = [];

        foreach ($emails as $email) {
            $attendees[] = ['email' => $email];
        }

        return $attendees;
    }

    /**
     * Normaliza pra RFC3339 completo (com segundos) - a API rejeita
     * dateTime sem segundos. Aceita tanto "Y-m-d H:i:s" (formato salvo no
     * banco, ver reconciliacao) quanto "Y-m-d\TH:i" (formato bruto de um
     * <input type="datetime-local">, sem segundos, vindo direto do form).
     */
    private static function formatarDataHoraIso($dataHora)
    {
        $timestamp = strtotime($dataHora);

        return $timestamp !== false ? date('Y-m-d\TH:i:s', $timestamp) : $dataHora;
    }

    private static function urlEventos($calendarId)
    {
        return self::URL_BASE . '/calendars/' . rawurlencode($calendarId) . '/events';
    }

    private static function requisitar($url, $accessToken, array $opcoesCurl)
    {
        $ch = curl_init($url);

        $cabecalhos = isset($opcoesCurl[CURLOPT_HTTPHEADER]) ? $opcoesCurl[CURLOPT_HTTPHEADER] : [];
        $cabecalhos[] = 'Authorization: Bearer ' . $accessToken;
        $opcoesCurl[CURLOPT_HTTPHEADER] = $cabecalhos;

        curl_setopt_array($ch, $opcoesCurl + [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT_SEGUNDOS,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        $corpo = curl_exec($ch);
        $erroCurl = curl_error($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($corpo === false || $erroCurl !== '') {
            return null;
        }

        if ($codigoHttp < 200 || $codigoHttp >= 300) {
            return null;
        }

        if ($corpo === '') {
            return [];
        }

        $dados = json_decode($corpo, true);

        return is_array($dados) ? $dados : null;
    }
}
