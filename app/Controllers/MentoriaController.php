<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Mailer;
use App\Middleware\RoleMiddleware;
use App\Repositories\EquipeRepository;
use App\Repositories\MentoriaRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Repositories\UsuarioRepository;

/**
 * Fase 19 (#106): lado do participante - ve horarios vagos do concurso da
 * propria equipe e reserva/cancela. So' equipes homologadas fazem sentido
 * aqui, mas nao bloqueamos por homologacao (mentoria e' apoio, nao
 * depende do resultado da inscricao).
 */
class MentoriaController extends Controller
{
    private $usuarioParticipante;
    private $equipes;
    private $trilhas;
    private $mentorias;
    private $usuarios;
    private $notificacoes;

    public function __construct()
    {
        RoleMiddleware::exigirEmQualquerConcurso(['participante']);
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->equipes = new EquipeRepository();
        $this->trilhas = new TrilhaRepository();
        $this->mentorias = new MentoriaRepository();
        $this->usuarios = new UsuarioRepository();
        $this->notificacoes = new NotificacaoPainelRepository();
    }

    public function index()
    {
        $contexto = $this->contextoAtual();

        $this->renderizar('participante/mentorias', [
            'equipe' => $contexto['equipe'],
            'vagos' => $this->mentorias->listarVagosPorConcurso($contexto['concursoId']),
            'reservas' => $this->mentorias->listarReservasDaEquipe($contexto['equipe']['id']),
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Mentorias');

        unset($_SESSION['flash']);
    }

    public function reservar($horarioId)
    {
        $contexto = $this->contextoAtual();
        $horario = $this->mentorias->buscarPorId($horarioId);

        if ($horario === null || (int) $horario['concurso_id'] !== (int) $contexto['concursoId']) {
            http_response_code(404);
            exit('Horário não encontrado.');
        }

        $sucesso = $this->mentorias->reservar($horarioId, $contexto['equipe']['id']);

        if (!$sucesso) {
            $_SESSION['flash'] = 'Esse horário acabou de ser reservado por outra equipe.';
            $this->redirecionar('mentoria/index');
            return;
        }

        $mentor = $this->usuarios->buscarPorId($horario['mentor_usuario_id']);
        $mensagem = 'A equipe "' . $contexto['equipe']['nome_equipe'] . '" reservou seu horário de ' . date('d/m/Y H:i', strtotime($horario['data_inicio'])) . '.';
        $this->notificacoes->criar((int) $horario['mentor_usuario_id'], 'mentoria', 'Mentoria reservada', $mensagem, ['url' => url('mentoriaAdmin/index/' . (int) $horario['concurso_id'])]);

        if ($mentor !== null && !empty($mentor['email'])) {
            Mailer::enviar($mentor['email'], 'Mentoria reservada', '<p>' . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . '</p>');
        }

        $_SESSION['flash'] = 'Horário reservado.';
        $this->redirecionar('mentoria/index');
    }

    public function cancelar($horarioId)
    {
        $contexto = $this->contextoAtual();
        $horario = $this->mentorias->buscarPorId($horarioId);

        if ($horario === null || (int) $horario['equipe_id'] !== (int) $contexto['equipe']['id']) {
            http_response_code(404);
            exit('Reserva não encontrada.');
        }

        $this->mentorias->cancelarReserva($horarioId);

        $mentor = $this->usuarios->buscarPorId($horario['mentor_usuario_id']);
        $mensagem = 'A equipe "' . $contexto['equipe']['nome_equipe'] . '" cancelou a reserva do horário de ' . date('d/m/Y H:i', strtotime($horario['data_inicio'])) . '.';
        $this->notificacoes->criar((int) $horario['mentor_usuario_id'], 'mentoria', 'Reserva de mentoria cancelada', $mensagem, ['url' => url('mentoriaAdmin/index/' . (int) $horario['concurso_id'])]);

        if ($mentor !== null && !empty($mentor['email'])) {
            Mailer::enviar($mentor['email'], 'Reserva de mentoria cancelada', '<p>' . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . '</p>');
        }

        $_SESSION['flash'] = 'Reserva cancelada.';
        $this->redirecionar('mentoria/index');
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
