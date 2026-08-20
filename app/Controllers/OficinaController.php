<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EquipeRepository;
use App\Repositories\OficinaRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Repositories\UsuarioRepository;
use App\Services\EventoEtapaService;
use App\Services\GoogleCalendarSyncService;

/**
 * Fase 24: lado do participante - qualquer equipe do concurso pode ver os
 * horarios de oficina e se inscrever/cancelar, sem exclusividade (varias
 * equipes no mesmo horario, diferente de MentoriaController).
 */
class OficinaController extends Controller
{
    private $usuarioParticipante;
    private $equipes;
    private $trilhas;
    private $oficinas;
    private $usuarios;
    private $googleSync;
    private $eventoEtapa;

    // Fase 31: teto de reconciliacao automatica sob demanda no lado do
    // participante (ver plano da Fase 31) - evita que uma equipe inscrita
    // em muitas oficinas pendentes deixe o load da tela lento.
    const RECONCILIACOES_MAXIMAS_POR_PAGINA = 2;

    public function __construct()
    {
        RoleMiddleware::exigirEmQualquerConcurso(['participante']);
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->equipes = new EquipeRepository();
        $this->trilhas = new TrilhaRepository();
        $this->oficinas = new OficinaRepository();
        $this->usuarios = new UsuarioRepository();
        $this->googleSync = new GoogleCalendarSyncService();
        $this->eventoEtapa = new EventoEtapaService();
    }

    public function index()
    {
        $contexto = $this->contextoAtual();
        $inscricoes = $this->oficinas->listarInscricoesDaEquipe($contexto['equipe']['id']);
        $inscritosIds = array_map('intval', array_column($inscricoes, 'id'));
        // Fase 34: horario vinculado a uma etapa so' aparece pra quem esta'
        // habilitado a ela. Filtra ANTES do laco de reconciliacao pra nao
        // gastar chamada ao Google com horario que a equipe nem enxerga -
        // inclusive quando ela ja esta' inscrita e perdeu a elegibilidade
        // depois (o horario some da tela, a inscricao nao e' desfeita).
        $horarios = $this->eventoEtapa->apenasVisiveis(
            $this->oficinas->listarFuturasPorConcurso($contexto['concursoId']),
            $contexto['equipe']
        );

        $reconciliacoesFeitas = 0;

        foreach ($horarios as &$horario) {
            if ($reconciliacoesFeitas >= self::RECONCILIACOES_MAXIMAS_POR_PAGINA) {
                break;
            }

            if (!in_array((int) $horario['id'], $inscritosIds, true)) {
                continue;
            }

            if (empty($horario['integracao_google']) || empty($horario['google_event_id'])) {
                continue;
            }

            $organizador = $this->usuarios->buscarPorId($horario['criado_por']);

            if ($organizador === null) {
                continue;
            }

            $resultado = $this->googleSync->reconciliar('oficina', $horario, $organizador['email']);
            $reconciliacoesFeitas++;

            if ($resultado !== null) {
                $this->oficinas->atualizarGoogle($horario['id'], $resultado);
                $horario['link_meet'] = $resultado['meet_link'];
                $horario['meet_pendente'] = $resultado['meet_pendente'];
            }
        }
        unset($horario);

        $this->renderizar('participante/oficinas', [
            'equipe' => $contexto['equipe'],
            'horarios' => $horarios,
            'inscritosIds' => $inscritosIds,
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Oficinas');

        unset($_SESSION['flash']);
    }

    public function inscrever($horarioId)
    {
        $contexto = $this->contextoAtual();
        $horario = $this->oficinas->buscarPorId($horarioId);

        if ($horario === null || (int) $horario['concurso_id'] !== (int) $contexto['concursoId']) {
            http_response_code(404);
            exit('Horário não encontrado.');
        }

        // Fase 34: esconder o botao nao protege - esta rota aceita o id
        // direto no POST. Mesma trava de servidor que
        // RequerimentoController::modeloDisponivelOuAbortar() (Fase 30).
        $motivo = $this->eventoEtapa->motivoBloqueio($horario, $contexto['equipe']);

        if ($motivo !== null) {
            http_response_code(403);
            exit('Acesso negado: ' . $motivo);
        }

        $sucesso = $this->oficinas->inscrever($horarioId, $contexto['equipe']['id']);

        if ($sucesso) {
            flashSucesso('Inscrição confirmada.');
        } else {
            flashAlerta('Sua equipe já está inscrita nesta oficina.');
        }

        if ($sucesso) {
            $this->sincronizarAttendeesGoogle($horario);
        }

        $this->redirecionar('oficina/index');
    }

    public function cancelar($horarioId)
    {
        $contexto = $this->contextoAtual();
        $horario = $this->oficinas->buscarPorId($horarioId);
        $this->oficinas->cancelarInscricao($horarioId, $contexto['equipe']['id']);

        if ($horario !== null) {
            $this->sincronizarAttendeesGoogle($horario);
        }

        $_SESSION['flash'] = 'Inscrição cancelada.';
        $this->redirecionar('oficina/index');
    }

    /**
     * Fase 31: recomputa a lista COMPLETA de attendees a partir de TODAS as
     * equipes atualmente inscritas (chamado depois de inscrever/cancelar já
     * ter alterado oficina_inscricoes) - nunca merge incremental.
     */
    private function sincronizarAttendeesGoogle(array $horario)
    {
        if (empty($horario['integracao_google']) || empty($horario['google_event_id'])) {
            return;
        }

        $organizador = $this->usuarios->buscarPorId($horario['criado_por']);

        if ($organizador === null) {
            return;
        }

        $equipeIds = array_map('intval', array_column($this->oficinas->listarInscritos($horario['id']), 'equipe_id'));
        $emails = $this->equipes->listarEmailsPorEquipes($equipeIds);

        $resultado = $this->googleSync->sincronizarAttendees(
            'oficina', $horario['id'], $organizador['email'], $horario['google_calendar_id'], $horario['google_event_id'],
            array_keys($emails), $emails
        );

        if ($resultado !== null) {
            $this->oficinas->atualizarGoogle($horario['id'], $resultado);
        }
    }

    private function contextoAtual()
    {
        $participantes = $this->usuarioParticipante->participantesDoUsuario(Auth::usuarioId());
        $participante = !empty($participantes) ? $participantes[0] : null;

        if ($participante === null) {
            http_response_code(404);
            exit('Nenhum participante vinculado a esta conta.');
        }

        $equipe = $this->equipes->buscarPorParticipante($participante['id']);

        if ($equipe === null) {
            http_response_code(404);
            exit('Nenhuma equipe encontrada para este participante.');
        }

        $trilha = $this->trilhas->buscarPorId($equipe['trilha_id']);

        return ['participante' => $participante, 'equipe' => $equipe, 'concursoId' => (int) $trilha['concurso_id']];
    }
}
