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

    public function __construct()
    {
        RoleMiddleware::exigirEmQualquerConcurso(['participante']);
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->equipes = new EquipeRepository();
        $this->trilhas = new TrilhaRepository();
        $this->oficinas = new OficinaRepository();
    }

    public function index()
    {
        $contexto = $this->contextoAtual();
        $inscricoes = $this->oficinas->listarInscricoesDaEquipe($contexto['equipe']['id']);

        $this->renderizar('participante/oficinas', [
            'equipe' => $contexto['equipe'],
            'horarios' => $this->oficinas->listarFuturasPorConcurso($contexto['concursoId']),
            'inscritosIds' => array_map('intval', array_column($inscricoes, 'id')),
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

        $sucesso = $this->oficinas->inscrever($horarioId, $contexto['equipe']['id']);
        $_SESSION['flash'] = $sucesso ? 'Inscrição confirmada.' : 'Sua equipe já está inscrita nesta oficina.';
        $this->redirecionar('oficina/index');
    }

    public function cancelar($horarioId)
    {
        $contexto = $this->contextoAtual();
        $this->oficinas->cancelarInscricao($horarioId, $contexto['equipe']['id']);

        $_SESSION['flash'] = 'Inscrição cancelada.';
        $this->redirecionar('oficina/index');
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
