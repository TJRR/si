<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\FaqConcursoRepository;

/**
 * Tela concurso-scoped de "quais perguntas do banco global estao ativas
 * nesta edicao" (Fase 18, 3.10). Reaproveitar de edicao anterior = so'
 * marcar ativo=1 aqui, nunca duplica o texto da pergunta.
 */
class FaqConcursoAdminController extends Controller
{
    private $faqConcurso;
    private $concursos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->faqConcurso = new FaqConcursoRepository();
        $this->concursos = new ConcursoRepository();
    }

    public function index($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $this->renderizar('admin/faq_concurso/index', [
            'concurso' => $concurso,
            'faqs' => $this->faqConcurso->listarComStatusPorConcurso($concursoId),
        ], 'FAQ de ' . $concurso['nome'], ['tipo' => 'faqConcurso', 'id' => (int) $concursoId]);
    }

    public function alternar($concursoId, $faqId)
    {
        if (isset($_POST['ativo']) && $_POST['ativo'] === '1') {
            $this->faqConcurso->ativar((int) $faqId, (int) $concursoId);
        } else {
            $this->faqConcurso->desativar((int) $faqId, (int) $concursoId);
        }

        $this->redirecionar('faqConcurso/index/' . (int) $concursoId);
    }

    public function reordenar($concursoId)
    {
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->faqConcurso->reordenar((int) $concursoId, $ids);

        echo json_encode(['ok' => true]);
    }
}
