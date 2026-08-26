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
 *
 * Fase 35: Suporte passa a acessar esta tela - mas so' do concurso a que
 * ele esta vinculado. Como esta tela E' escopada por concurso (ao
 * contrario do banco global em FaqAdminController), a checagem tem que
 * acontecer POR ACAO, com o $concursoId da URL: sem isso, um Suporte do
 * concurso A alteraria o FAQ do concurso B so' trocando o numero na URL.
 */
class FaqConcursoAdminController extends Controller
{
    private $faqConcurso;
    private $concursos;

    public function __construct()
    {
        // exigirEmQualquerConcurso() na entrada (nao exigir(), que sem
        // concurso so' reconhece vinculo GLOBAL e barraria quem e' escopado
        // a um concurso) + exigir() com o concurso resolvido DENTRO de cada
        // acao - mesmo padrao de MentoriaAdminController/OficinaAdminController.
        RoleMiddleware::exigirEmQualquerConcurso(['administrador', 'suporte']);
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

        RoleMiddleware::exigir(['administrador', 'suporte'], $concurso['id']);

        $this->renderizar('admin/faq_concurso/index', [
            'concurso' => $concurso,
            'faqs' => $this->faqConcurso->listarComStatusPorConcurso($concursoId),
        ], 'FAQ de ' . $concurso['nome'], ['tipo' => 'faqConcurso', 'id' => (int) $concursoId]);
    }

    public function alternar($concursoId, $faqId)
    {
        RoleMiddleware::exigir(['administrador', 'suporte'], (int) $concursoId);

        if (isset($_POST['ativo']) && $_POST['ativo'] === '1') {
            $this->faqConcurso->ativar((int) $faqId, (int) $concursoId);
        } else {
            $this->faqConcurso->desativar((int) $faqId, (int) $concursoId);
        }

        $this->redirecionar('faqConcurso/index/' . (int) $concursoId);
    }

    public function reordenar($concursoId)
    {
        RoleMiddleware::exigir(['administrador', 'suporte'], (int) $concursoId);

        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->faqConcurso->reordenar((int) $concursoId, $ids);

        echo json_encode(['ok' => true]);
    }
}
