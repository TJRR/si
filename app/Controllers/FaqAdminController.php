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
 *
 * Fase 35: como o banco é global, o acesso aqui exige perfil GLOBAL
 * (administrador ou suporte sem concurso). Ver o construtor.
 */
class FaqAdminController extends Controller
{
    private $faqs;

    public function __construct()
    {
        // Fase 35: Suporte tambem edita o banco GERAL, mas so' com vinculo
        // GLOBAL. exigir() SEM $concursoId e' exatamente esse criterio:
        // Auth::temPerfil() so' devolve true pra vinculo com concurso_id
        // NULL quando nao ha concurso pra comparar. Nao trocar por
        // exigirEmQualquerConcurso() - isso deixaria entrar quem esta
        // escopado a UM concurso, e este banco e' global e acumulativo
        // entre TODAS as edicoes (mexer aqui altera a home de edicoes que
        // essa pessoa nao administra). Quem e' escopado edita o FAQ da
        // propria edicao, em FaqConcursoAdminController.
        RoleMiddleware::exigir(['administrador', 'suporte']);
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
            flashErro($e->getCode() === '23000'
                ? 'Não é possível remover: esta pergunta está ativa em uma ou mais edições. Desative-a nas edições antes de remover.'
                : 'Não foi possível remover a pergunta.');
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
