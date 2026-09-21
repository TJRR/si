<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ApresentacaoPitchRepository;
use App\Repositories\AvaliadorDesignacaoRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Repositories\UsuarioRepository;
use App\Services\GoogleCalendarSyncService;
use App\Services\PermissaoParticipanteService;

/**
 * Fase 38B: lado do participante - qualquer integrante homologado da
 * equipe (não só o líder, ver plano da fase) escolhe slot + modalidade
 * dentro da janela configurada. Mesmo padrão de MentoriaController.
 */
class ApresentacaoPitchController extends Controller
{
    private $usuarioParticipante;
    private $equipes;
    private $trilhas;
    private $etapas;
    private $apresentacoes;
    private $submissoes;
    private $designacoes;
    private $usuarios;
    private $googleSync;

    public function __construct()
    {
        RoleMiddleware::exigirEmQualquerConcurso(['participante']);
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->equipes = new EquipeRepository();
        $this->trilhas = new TrilhaRepository();
        $this->etapas = new EtapaRepository();
        $this->apresentacoes = new ApresentacaoPitchRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->designacoes = new AvaliadorDesignacaoRepository();
        $this->usuarios = new UsuarioRepository();
        $this->googleSync = new GoogleCalendarSyncService();
    }

    public function index($etapaId)
    {
        $contexto = $this->contextoAtual($etapaId);

        $reserva = $this->apresentacoes->buscarPorEquipeNaEtapa($etapaId, $contexto['equipe']['id']);

        $this->renderizar('participante/apresentacaoPitch/index', [
            'etapa' => $contexto['etapa'],
            'config' => $contexto['config'],
            'dentroDaJanela' => $this->dentroDaJanela($contexto['config']),
            'reserva' => $reserva,
            'vagos' => $reserva === null ? $this->apresentacoes->listarVagosPorEtapa($etapaId) : [],
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Apresentação de pitch');

        unset($_SESSION['flash']);
    }

    public function reservar($etapaId, $slotId)
    {
        $contexto = $this->contextoAtual($etapaId);

        if (!(new PermissaoParticipanteService())->podeExecutar($contexto['participante']['id'], 'reservar_apresentacao_pitch')) {
            http_response_code(403);
            exit('Acesso negado: sua inscrição não está homologada.');
        }

        if (!$this->dentroDaJanela($contexto['config'])) {
            http_response_code(403);
            exit('Acesso negado: a janela para escolher data e horário não está aberta.');
        }

        $modalidade = isset($_POST['modalidade']) ? $_POST['modalidade'] : '';

        if (!in_array($modalidade, ['presencial', 'online'], true)) {
            flashErro('Escolha uma modalidade válida.');
            $this->redirecionar('apresentacaoPitch/index/' . (int) $etapaId);
            return;
        }

        $slot = $this->apresentacoes->buscarPorId($slotId);

        if ($slot === null || (int) $slot['etapa_id'] !== (int) $etapaId) {
            http_response_code(404);
            exit('Horário não encontrado.');
        }

        $sucesso = $this->apresentacoes->reservar($slotId, $contexto['equipe']['id'], $modalidade);

        if (!$sucesso) {
            flashAlerta('Esse horário acabou de ser reservado por outra equipe.');
            $this->redirecionar('apresentacaoPitch/index/' . (int) $etapaId);
            return;
        }

        if ($modalidade === 'online') {
            $this->sincronizarGoogleAposReserva($slotId, $etapaId, (int) $contexto['equipe']['id'], $contexto['config']);
        }

        $_SESSION['flash'] = 'Horário reservado.';
        $this->redirecionar('apresentacaoPitch/index/' . (int) $etapaId);
    }

    /**
     * Mesma lógica de ApresentacaoPitchAdminController::sincronizarGoogleAposReserva()
     * - duplicada entre os dois controllers, mesmo padrão já usado entre
     * MentoriaAdminController/MentoriaController.
     */
    private function sincronizarGoogleAposReserva($slotId, $etapaId, $equipeId, array $config = null)
    {
        if ($config === null || empty($config['email_organizador_google']) || !organizadorElegivelGoogle($config['email_organizador_google'])) {
            return;
        }

        $slot = $this->apresentacoes->buscarPorId($slotId);
        $emailOrganizador = $config['email_organizador_google'];
        $etapa = $this->etapas->buscarPorId($etapaId);
        $equipe = $this->equipes->buscarPorId($equipeId);

        $resultado = $this->googleSync->criar(
            $emailOrganizador,
            [
                'titulo' => 'Apresentação de pitch: ' . ($equipe !== null ? $equipe['nome_equipe'] : 'Equipe #' . $equipeId),
                'descricao' => 'Apresentação de pitch da Etapa 3 (' . $etapa['nome'] . ").\n\nDetalhes no sistema: " . urlAbsoluta('apresentacaoPitch/index/' . $etapaId),
                'data_inicio' => $slot['data_inicio'],
                'data_fim' => $slot['data_fim'],
            ],
            'Apresentações de pitch: ' . $etapa['nome']
        );

        if ($resultado === null) {
            flashAlerta('Horário reservado, mas não foi possível conectar com o Google Agenda agora. O sistema tentará novamente automaticamente.');
            return;
        }

        $this->apresentacoes->marcarIntegracaoGoogle($slotId, true);
        $this->apresentacoes->atualizarGoogle($slotId, $resultado);

        $emails = $this->equipes->listarEmailsPorEquipes([$equipeId]);
        $submissao = $this->submissoes->buscarPorEquipeEEtapa($equipeId, $etapaId);

        if ($submissao !== null) {
            foreach ($this->designacoes->listarPorSubmissao($submissao['id']) as $designacao) {
                $avaliador = $this->usuarios->buscarPorId($designacao['usuario_id']);

                if ($avaliador !== null && !empty($avaliador['email'])) {
                    $emails[$avaliador['email']] = null;
                }
            }
        }

        $sincronizacao = $this->googleSync->sincronizarAttendees(
            'apresentacao_pitch', $slotId, $emailOrganizador, $resultado['google_calendar_id'], $resultado['google_event_id'],
            array_keys($emails), $emails
        );

        if ($sincronizacao !== null) {
            $this->apresentacoes->atualizarGoogle($slotId, $sincronizacao);
        }
    }

    private function dentroDaJanela($config)
    {
        if ($config === null || empty($config['janela_escolha_inicio']) || empty($config['janela_escolha_fim'])) {
            return false;
        }

        $agora = time();

        return $agora >= strtotime($config['janela_escolha_inicio']) && $agora <= strtotime($config['janela_escolha_fim']);
    }

    private function contextoAtual($etapaId)
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

        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);

        if ((int) $equipe['trilha_id'] !== (int) $trilha['id']) {
            http_response_code(403);
            exit('Acesso negado: esta etapa não pertence à trilha da sua equipe.');
        }

        return [
            'participante' => $participante,
            'equipe' => $equipe,
            'etapa' => $etapa,
            'config' => $this->apresentacoes->buscarConfig($etapaId),
        ];
    }
}
