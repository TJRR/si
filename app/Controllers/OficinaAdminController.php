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
use App\Repositories\ConcursoRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\OficinaRepository;
use App\Repositories\UsuarioParticipanteRepository;

/**
 * Fase 24: administrador/suporte cria horarios de oficina - encontro
 * coletivo com tema pre-definido conduzido pelo organizador do concurso
 * (sem "mentor" designado). Qualquer equipe interessada pode se inscrever,
 * sem exclusividade (diferente de MentoriaAdminController).
 */
class OficinaAdminController extends Controller
{
    private $oficinas;
    private $concursos;
    private $equipes;
    private $usuarioParticipante;
    private $notificacoes;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->oficinas = new OficinaRepository();
        $this->concursos = new ConcursoRepository();
        $this->equipes = new EquipeRepository();
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->notificacoes = new NotificacaoPainelRepository();
    }

    public function index($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $this->renderizar('admin/oficinas/index', [
            'concurso' => $concurso,
            'horarios' => $this->oficinas->listarPorConcurso($concursoId),
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Oficinas de ' . $concurso['nome'], ['tipo' => 'oficinas', 'id' => (int) $concursoId]);

        unset($_SESSION['flash']);
    }

    public function novo($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tema = trim(isset($_POST['tema']) ? $_POST['tema'] : '');
            $dataInicio = trim(isset($_POST['data_inicio']) ? $_POST['data_inicio'] : '');
            $dataFim = trim(isset($_POST['data_fim']) ? $_POST['data_fim'] : '');
            $linkMeet = trim(isset($_POST['link_meet']) ? $_POST['link_meet'] : '');
            $observacao = trim(isset($_POST['observacao']) ? $_POST['observacao'] : '');

            if ($tema === '') {
                $erro = 'Informe o tema da oficina.';
            } elseif ($dataInicio === '' || $dataFim === '') {
                $erro = 'Informe o início e o fim do horário.';
            } elseif (strtotime($dataFim) <= strtotime($dataInicio)) {
                $erro = 'O fim do horário deve ser depois do início.';
            } elseif ($linkMeet !== '' && !linkHttpValido($linkMeet)) {
                $erro = 'O link do Google Meet deve começar com http:// ou https://.';
            } else {
                $this->oficinas->criar(
                    $concursoId,
                    $tema,
                    $dataInicio,
                    $dataFim,
                    $linkMeet !== '' ? $linkMeet : null,
                    $observacao !== '' ? $observacao : null,
                    Auth::usuarioId()
                );
                $this->redirecionar('oficinaAdmin/index/' . $concursoId);
                return;
            }
        }

        $this->renderizar('admin/oficinas/form', [
            'erro' => $erro,
            'concurso' => $concurso,
        ], 'Novo horário de oficina', ['tipo' => 'oficinas', 'id' => (int) $concursoId]);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);
        $horario = $this->oficinas->buscarPorId($id);

        if ($horario === null) {
            $this->redirecionar('oficinaAdmin/index/' . $concursoId);
            return;
        }

        $souDono = (int) $horario['criado_por'] === (int) Auth::usuarioId();

        if (!$souDono && !Auth::possuiPerfil('administrador')) {
            http_response_code(403);
            exit('Acesso negado: este horário pertence a outro organizador.');
        }

        foreach ($this->oficinas->listarInscritos($id) as $inscrito) {
            $this->notificarEquipe(
                (int) $inscrito['equipe_id'],
                'Oficina cancelada',
                'A oficina "' . $horario['tema'] . '" de ' . date('d/m/Y H:i', strtotime($horario['data_inicio'])) . ' foi cancelada.'
            );
        }

        $this->oficinas->remover($id);
        $_SESSION['flash'] = 'Horário removido.';
        $this->redirecionar('oficinaAdmin/index/' . $concursoId);
    }

    private function notificarEquipe($equipeId, $titulo, $mensagem)
    {
        foreach ($this->equipes->listarParticipantes($equipeId) as $participante) {
            foreach ($this->usuarioParticipante->usuariosDoParticipante($participante['id']) as $usuarioId) {
                $this->notificacoes->criar($usuarioId, 'oficina', $titulo, $mensagem, ['url' => url('oficina/index')]);
            }

            if (!empty($participante['email'])) {
                Mailer::enviar($participante['email'], $titulo, '<p>' . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . '</p>');
            }
        }
    }
}
