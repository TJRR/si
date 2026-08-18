<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\GoogleServiceAccountAuth;
use App\Repositories\GoogleConviteStatusRepository;

/**
 * Fase 31: orquestrador unico da integracao Mentoria/Oficina <-> Google
 * Agenda - evita duplicar "checar elegibilidade / obter token / decidir
 * mensagem de fail-soft" nos 4 controllers x 2 modulos. Agnostico de tabela:
 * devolve as colunas prontas pra MentoriaRepository/OficinaRepository
 * persistirem, e cuida sozinho da tabela compartilhada
 * google_convite_status (RSVP), que nao pertence a nenhum dos dois modulos.
 *
 * Toda chamada publica e' fail-soft: nunca lanca excecao, devolve null (ou
 * false) quando nao da pra sincronizar - quem chama decide a mensagem pro
 * usuario e NUNCA bloqueia a acao principal (criar/reservar/cancelar/
 * remover) so' porque o Google falhou.
 */
class GoogleCalendarSyncService
{
    const ESCOPOS = [
        'https://www.googleapis.com/auth/calendar.events',
        'https://www.googleapis.com/auth/calendar.calendars',
    ];

    // Sem fila/cron no projeto - throttle evita bater no Google de novo a
    // cada reload/clique enquanto a ultima verificacao ainda for recente.
    const THROTTLE_SEGUNDOS = 60;

    private $conviteStatus;

    public function __construct()
    {
        $this->conviteStatus = new GoogleConviteStatusRepository();
    }

    /**
     * Cria a agenda secundaria do organizador (se ainda nao existir) + o
     * evento com pedido de sala do Meet. $dadosEvento: titulo, descricao,
     * data_inicio, data_fim (Y-m-d H:i:s). Devolve as colunas prontas pra
     * persistir no horario, ou null se nao deu pra integrar agora (admin
     * ve "Tentar novamente" na listagem, o horario continua existindo).
     */
    public function criar($organizadorEmail, array $dadosEvento, $nomeCalendario)
    {
        $token = $this->obterToken($organizadorEmail);

        if ($token === null) {
            return null;
        }

        $calendarId = GoogleCalendarService::garantirCalendarioSecundario($token, $nomeCalendario);

        if ($calendarId === null) {
            return null;
        }

        $resultado = GoogleCalendarService::criarEvento($token, $calendarId, $dadosEvento);

        if ($resultado === null) {
            return null;
        }

        return [
            'google_event_id' => $resultado['event_id'],
            'google_calendar_id' => $calendarId,
            'meet_link' => $resultado['meet_link'],
            'meet_link_origem' => $resultado['meet_link'] !== null ? 'google_auto' : null,
            'meet_pendente' => $resultado['meet_status'] === 'pending',
            'google_sincronizado_em' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Recomputa e sobrescreve a lista COMPLETA de attendees a partir do
     * estado atual do banco (nunca merge incremental - ver justificativa no
     * plano da Fase 31). Chamado a cada reserva/cancelamento (mentoria) ou
     * inscricao/cancelamento (oficina). Atualiza tambem o cache de RSVP
     * (google_convite_status) com a resposta que o proprio patch devolve.
     */
    public function sincronizarAttendees($tipo, $horarioId, $organizadorEmail, $calendarId, $eventId, array $emails, array $participanteIdPorEmail = [])
    {
        if ($calendarId === null || $eventId === null) {
            return null;
        }

        $token = $this->obterToken($organizadorEmail);

        if ($token === null) {
            return null;
        }

        $resultado = GoogleCalendarService::atualizarAttendees($token, $calendarId, $eventId, $emails);

        if ($resultado === null) {
            return null;
        }

        $this->conviteStatus->sincronizar($tipo, $horarioId, $resultado['attendees'], $participanteIdPorEmail);

        return ['google_sincronizado_em' => date('Y-m-d H:i:s')];
    }

    /**
     * Cancela o evento no Google (chamado antes de excluir o horario).
     * Sem evento pra cancelar (nunca integrado, ou integracao nunca chegou
     * a criar o evento) conta como sucesso - nao ha nada a fazer.
     */
    public function cancelar($organizadorEmail, $calendarId, $eventId)
    {
        if ($calendarId === null || $eventId === null) {
            return true;
        }

        $token = $this->obterToken($organizadorEmail);

        if ($token === null) {
            return false;
        }

        return GoogleCalendarService::cancelarEvento($token, $calendarId, $eventId);
    }

    /**
     * Reconciliacao sob demanda (Meet ainda "pending" + refresh de RSVP),
     * chamada ao abrir a tela do compromisso ou no botao manual
     * "Verificar/Tentar novamente". Throttled por google_sincronizado_em -
     * devolve null sem chamar o Google se a ultima verificacao foi ha menos
     * de THROTTLE_SEGUNDOS, e tambem se o horario nunca chegou a ter um
     * evento criado (nesse caso quem chama deve usar criar() de novo, nao
     * reconciliar()).
     */
    public function reconciliar($tipo, array $horario, $organizadorEmail)
    {
        if (empty($horario['google_event_id']) || empty($horario['google_calendar_id'])) {
            return null;
        }

        if (!self::throttleLiberado($horario['google_sincronizado_em'])) {
            return null;
        }

        $token = $this->obterToken($organizadorEmail);

        if ($token === null) {
            return null;
        }

        $resultado = GoogleCalendarService::buscarEvento($token, $horario['google_calendar_id'], $horario['google_event_id']);

        if ($resultado === null) {
            return null;
        }

        $this->conviteStatus->sincronizar($tipo, $horario['id'], $resultado['attendees']);

        return [
            'meet_link' => $resultado['meet_link'],
            'meet_link_origem' => $resultado['meet_link'] !== null ? 'google_auto' : $horario['meet_link_origem'],
            'meet_pendente' => $resultado['meet_status'] === 'pending',
            'google_sincronizado_em' => date('Y-m-d H:i:s'),
        ];
    }

    public static function throttleLiberado($ultimaSincronizacaoEm)
    {
        if ($ultimaSincronizacaoEm === null) {
            return true;
        }

        return (time() - strtotime($ultimaSincronizacaoEm)) >= self::THROTTLE_SEGUNDOS;
    }

    /**
     * Revalida elegibilidade a cada chamada (defesa em profundidade - ver
     * plano da Fase 31), nao so' no momento em que o horario foi marcado
     * como integrado.
     */
    private function obterToken($organizadorEmail)
    {
        if (!organizadorElegivelGoogle($organizadorEmail)) {
            return null;
        }

        return GoogleServiceAccountAuth::obterAccessToken($organizadorEmail, self::ESCOPOS);
    }
}
