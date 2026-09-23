<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EventoAtividadeTipoRepository;
use App\Repositories\SemanaInovacaoRepository;

/**
 * Fase 51: catalogo de tipos de atividade por evento (Oficina, Palestra,
 * Sessao solene, Cultural, Experiencia...). O tipo vira a etiqueta colorida
 * das secoes "Destaques" e "Programacao" da pagina publica, e e' cadastro do
 * evento, nunca lista fixa em codigo: cada edicao usa os seus.
 */
class AtividadeTipoAdminController extends Controller
{
    private $eventos;
    private $tipos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->tipos = new EventoAtividadeTipoRepository();
    }

    private function eventoOu404($eventoId)
    {
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        return $evento;
    }

    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');

            if ($nome === '') {
                flashErro('Informe o nome do tipo.');
            } else {
                $this->tipos->criar($eventoId, [
                    'nome' => $nome,
                    'cor' => !empty($_POST['cor']) ? trim($_POST['cor']) : null,
                ]);
                flashSucesso('Tipo cadastrado.');
            }

            $this->redirecionar('atividadeTipos/index/' . $eventoId);
            return;
        }

        $this->renderizar('admin/atividade_tipos/index', [
            'evento' => $evento,
            'tipos' => $this->tipos->listar($eventoId),
        ], 'Tipos de atividade: ' . $evento['nome'], ['tipo' => 'atividadeTipos', 'id' => (int) $eventoId]);
    }

    public function salvar($eventoId)
    {
        $this->eventoOu404($eventoId);
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);

        $this->tipos->atualizar($eventoId, $id, [
            'nome' => trim(isset($_POST['nome']) ? $_POST['nome'] : ''),
            'cor' => !empty($_POST['cor']) ? trim($_POST['cor']) : null,
        ]);

        flashSucesso('Tipo atualizado.');
        $this->redirecionar('atividadeTipos/index/' . $eventoId);
    }

    public function remover($eventoId)
    {
        $this->eventoOu404($eventoId);
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);

        if ($this->tipos->emUso($id)) {
            flashAlerta('Este tipo está em uso por alguma atividade. Troque o tipo dessas atividades antes de remover.');
        } else {
            $this->tipos->remover($eventoId, $id);
            flashSucesso('Tipo removido.');
        }

        $this->redirecionar('atividadeTipos/index/' . $eventoId);
    }

    public function reordenar($eventoId)
    {
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->tipos->reordenar($eventoId, $ids);

        echo json_encode(['ok' => true]);
    }
}
