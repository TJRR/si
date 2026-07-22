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
use App\Repositories\MentoriaRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\UsuarioParticipanteRepository;

/**
 * Fase 19 (#106): qualquer administrador/suporte cria horarios de
 * mentoria - por padrao pra si mesmo, mas pode escolher outro
 * administrador/suporte como mentor do horario. Quem pode editar/remover
 * depois e' quem FICOU como mentor (mentor_usuario_id), nao
 * necessariamente quem criou - Admin global sempre pode, pra moderacao.
 */
class MentoriaAdminController extends Controller
{
    private $mentorias;
    private $concursos;
    private $equipes;
    private $usuarioParticipante;
    private $notificacoes;
    private $perfis;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->mentorias = new MentoriaRepository();
        $this->concursos = new ConcursoRepository();
        $this->equipes = new EquipeRepository();
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->notificacoes = new NotificacaoPainelRepository();
        $this->perfis = new PerfilRepository();
    }

    public function index($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $this->renderizar('admin/mentorias/index', [
            'concurso' => $concurso,
            'horarios' => $this->mentorias->listarPorConcurso($concursoId),
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Mentorias de ' . $concurso['nome'], ['tipo' => 'mentorias', 'id' => (int) $concursoId]);

        unset($_SESSION['flash']);
    }

    public function novo($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $mentores = $this->mentoresDisponiveis($concursoId);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dataInicio = trim(isset($_POST['data_inicio']) ? $_POST['data_inicio'] : '');
            $dataFim = trim(isset($_POST['data_fim']) ? $_POST['data_fim'] : '');
            $observacao = trim(isset($_POST['observacao']) ? $_POST['observacao'] : '');
            $mentorEscolhido = (int) (isset($_POST['mentor_usuario_id']) ? $_POST['mentor_usuario_id'] : 0);
            $mentorValido = in_array($mentorEscolhido, array_column($mentores, 'id'), false);

            if ($dataInicio === '' || $dataFim === '') {
                $erro = 'Informe o início e o fim do horário.';
            } elseif (strtotime($dataFim) <= strtotime($dataInicio)) {
                $erro = 'O fim do horário deve ser depois do início.';
            } elseif (!$mentorValido) {
                $erro = 'Selecione um mentor válido (administrador ou suporte).';
            } else {
                $this->mentorias->criar($concursoId, $mentorEscolhido, $dataInicio, $dataFim, $observacao !== '' ? $observacao : null);
                $this->redirecionar('mentoriaAdmin/index/' . $concursoId);
                return;
            }
        }

        $this->renderizar('admin/mentorias/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'mentores' => $mentores,
        ], 'Novo horário de mentoria', ['tipo' => 'mentorias', 'id' => (int) $concursoId]);
    }

    /**
     * Administrador/suporte globais (concurso_id NULL) + os escopados a
     * este concurso, sem duplicar quem tiver os dois perfis.
     */
    private function mentoresDisponiveis($concursoId)
    {
        $porId = [];

        foreach (['administrador', 'suporte'] as $perfilChave) {
            foreach ($this->perfis->listarUsuariosPorPerfilConcurso($perfilChave, $concursoId) as $usuario) {
                $porId[(int) $usuario['id']] = $usuario;
            }
        }

        usort($porId, function ($a, $b) {
            return strcmp($a['nome'], $b['nome']);
        });

        return array_values($porId);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);
        $horario = $this->mentorias->buscarPorId($id);

        if ($horario === null) {
            $this->redirecionar('mentoriaAdmin/index/' . $concursoId);
            return;
        }

        $souDono = (int) $horario['mentor_usuario_id'] === (int) Auth::usuarioId();

        if (!$souDono && !Auth::possuiPerfil('administrador')) {
            http_response_code(403);
            exit('Acesso negado: este horário pertence a outro mentor.');
        }

        if ($horario['equipe_id'] !== null) {
            $this->notificarEquipe(
                (int) $horario['equipe_id'],
                'Horário de mentoria cancelado',
                'O mentor cancelou o horário de ' . date('d/m/Y H:i', strtotime($horario['data_inicio'])) . ' que sua equipe havia reservado.'
            );
        }

        $this->mentorias->remover($id);
        $_SESSION['flash'] = 'Horário removido.';
        $this->redirecionar('mentoriaAdmin/index/' . $concursoId);
    }

    private function notificarEquipe($equipeId, $titulo, $mensagem)
    {
        foreach ($this->equipes->listarParticipantes($equipeId) as $participante) {
            foreach ($this->usuarioParticipante->usuariosDoParticipante($participante['id']) as $usuarioId) {
                $this->notificacoes->criar($usuarioId, 'mentoria', $titulo, $mensagem, ['url' => url('mentoria/index')]);
            }

            if (!empty($participante['email'])) {
                Mailer::enviar($participante['email'], $titulo, '<p>' . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . '</p>');
            }
        }
    }
}
