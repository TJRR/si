<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\EventoCronogramaRepository;
use App\Repositories\TrilhaRepository;

class EventoCronogramaAdminController extends Controller
{
    private $eventos;
    private $concursos;
    private $trilhas;
    private $etapas;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->eventos = new EventoCronogramaRepository();
        $this->concursos = new ConcursoRepository();
        $this->trilhas = new TrilhaRepository();
        $this->etapas = new EtapaRepository();
    }

    public function index($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $this->renderizar('admin/eventos_cronograma/index', [
            'concurso' => $concurso,
            'eventos' => $this->eventos->listarPorConcurso($concursoId),
        ], 'Cronograma de ' . $concurso['nome'], ['tipo' => 'eventosCronograma', 'id' => (int) $concursoId]);
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
            $erro = $this->validar();

            if ($erro === null) {
                $this->eventos->criar($concursoId, $this->dadosComuns());
                $this->redirecionar('eventosCronograma/index/' . $concursoId);
                return;
            }
        }

        $this->renderizar('admin/eventos_cronograma/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'evento' => null,
            'etapasDisponiveis' => $this->etapasDoConcurso($concursoId),
        ], 'Novo evento', ['tipo' => 'eventosCronograma', 'id' => (int) $concursoId]);
    }

    public function editar($id)
    {
        $evento = $this->eventos->buscarPorId($id);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $concurso = $this->concursos->buscarPorId($evento['concurso_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->validar();

            if ($erro === null) {
                $this->eventos->atualizar($id, $this->dadosComuns());
                $evento = $this->eventos->buscarPorId($id);
            }
        }

        $this->renderizar('admin/eventos_cronograma/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'evento' => $evento,
            'etapasDisponiveis' => $this->etapasDoConcurso($evento['concurso_id']),
        ], 'Editar evento', ['tipo' => 'eventosCronograma', 'id' => (int) $evento['concurso_id']]);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);

        $this->eventos->remover($id);

        $_SESSION['flash'] = 'Evento removido.';
        $this->redirecionar('eventosCronograma/index/' . $concursoId);
    }

    private function etapasDoConcurso($concursoId)
    {
        $lista = [];

        foreach ($this->trilhas->listarPorConcurso($concursoId) as $trilha) {
            foreach ($this->etapas->listarPorTrilha($trilha['id']) as $etapa) {
                $etapa['trilha_nome'] = $trilha['nome'];
                $lista[] = $etapa;
            }
        }

        return $lista;
    }

    private function dadosComuns()
    {
        $dataFim = trim(isset($_POST['data_fim']) ? $_POST['data_fim'] : '');

        return [
            'etapa_id' => !empty($_POST['etapa_id']) ? (int) $_POST['etapa_id'] : null,
            'titulo' => trim(isset($_POST['titulo']) ? $_POST['titulo'] : ''),
            'descricao' => trim(isset($_POST['descricao']) ? $_POST['descricao'] : '') ?: null,
            'data_inicio' => isset($_POST['data_inicio']) ? $_POST['data_inicio'] : null,
            'data_fim' => $dataFim !== '' ? $dataFim : null,
            'ordem' => 0,
        ];
    }

    private function validar()
    {
        if (trim(isset($_POST['titulo']) ? $_POST['titulo'] : '') === '') {
            return 'Informe o título do evento.';
        }

        if (trim(isset($_POST['data_inicio']) ? $_POST['data_inicio'] : '') === '') {
            return 'Informe a data/hora de início.';
        }

        return null;
    }
}
