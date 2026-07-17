<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\PerguntaFrequenteRepository;

/**
 * Banco GLOBAL de perguntas frequentes (Fase 18) - aba de nível 1 do admin,
 * igual "Páginas"/"Tema", pois não pertence a nenhum concurso específico.
 * Ativação por edição fica em FaqConcursoAdminController (rota faqConcurso).
 */
class FaqAdminController extends Controller
{
    private $faqs;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->faqs = new PerguntaFrequenteRepository();
    }

    public function index()
    {
        $this->renderizar('admin/faq/index', [
            'faqs' => $this->faqs->listar(),
        ], 'Banco de perguntas frequentes');
    }

    public function novo()
    {
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->validarESalvar();

            if ($erro === null) {
                $this->faqs->criar(trim($_POST['pergunta']), trim($_POST['resposta']), $this->categoriaOuNula());
                $this->redirecionar('faq/index');
                return;
            }
        }

        $this->renderizar('admin/faq/form', ['erro' => $erro, 'faq' => null], 'Nova pergunta');
    }

    public function editar($id)
    {
        $faq = $this->faqs->buscarPorId($id);

        if ($faq === null) {
            http_response_code(404);
            exit('Pergunta não encontrada.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->validarESalvar();

            if ($erro === null) {
                $this->faqs->atualizar($id, trim($_POST['pergunta']), trim($_POST['resposta']), $this->categoriaOuNula());
                $faq = $this->faqs->buscarPorId($id);
            }
        }

        $this->renderizar('admin/faq/form', ['erro' => $erro, 'faq' => $faq], 'Editar pergunta');
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);

        try {
            $this->faqs->remover($id);
            $_SESSION['flash'] = 'Pergunta removida do banco.';
        } catch (\PDOException $e) {
            $_SESSION['flash'] = $e->getCode() === '23000'
                ? 'Não é possível remover: esta pergunta está ativa em uma ou mais edições. Desative-a nas edições antes de remover.'
                : 'Não foi possível remover a pergunta.';
        }

        $this->redirecionar('faq/index');
    }

    private function categoriaOuNula()
    {
        $valor = trim(isset($_POST['categoria']) ? $_POST['categoria'] : '');

        return $valor !== '' ? $valor : null;
    }

    private function validarESalvar()
    {
        if (trim(isset($_POST['pergunta']) ? $_POST['pergunta'] : '') === '') {
            return 'Informe a pergunta.';
        }

        if (trim(isset($_POST['resposta']) ? $_POST['resposta'] : '') === '') {
            return 'Informe a resposta.';
        }

        return null;
    }
}
