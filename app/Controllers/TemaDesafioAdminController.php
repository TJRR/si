<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\DesafioRepository;
use App\Repositories\TemaRepository;
use App\Repositories\TrilhaRepository;

/**
 * Fase 17 (Bug 2): mantem o nome/rota historicos ("temas", TemaDesafioAdminController)
 * para nao colidir com TemaAdminController/rota "tema" (identidade visual, assunto
 * nao relacionado) - mas agora orquestra os dois niveis reais: Tema (TemaRepository)
 * e Desafio (DesafioRepository), com uma tela por Tema listando seus Desafios.
 */
class TemaDesafioAdminController extends Controller
{
    private $temas;
    private $desafios;
    private $trilhas;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->temas = new TemaRepository();
        $this->desafios = new DesafioRepository();
        $this->trilhas = new TrilhaRepository();
    }

    public function index($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha não encontrada.');
        }

        $lista = $this->temas->listarPorTrilha($trilhaId);
        $this->renderizar('admin/temas/index', [
            'trilha' => $trilha,
            'temas' => $lista,
        ], 'Temas de ' . $trilha['nome'], ['tipo' => 'temas', 'id' => (int) $trilhaId]);
    }

    /**
     * Fase 19 (#102): reordenacao dos Temas de uma trilha, mesmo padrao de
     * BlocoConteudoAdminController::reordenar().
     */
    public function reordenar($trilhaId)
    {
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->temas->reordenar((int) $trilhaId, $ids);

        echo json_encode(['ok' => true]);
    }

    /**
     * Reordenacao dos Desafios de UM Tema (rota separada de reordenar()
     * porque o escopo e' outro nivel da hierarquia).
     */
    public function reordenarDesafios($temaId)
    {
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->desafios->reordenar((int) $temaId, $ids);

        echo json_encode(['ok' => true]);
    }

    public function remover()
    {
        RoleMiddleware::exigir(['administrador']);
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);

        try {
            $this->temas->remover($id);
            $_SESSION['flash'] = 'Tema removido.';
        } catch (\PDOException $e) {
            $_SESSION['flash'] = $e->getCode() === '23000'
                ? 'Não é possível remover: este tema ainda tem desafios cadastrados.'
                : 'Não foi possível remover o tema.';
        }

        $this->redirecionar('temas/index/' . $trilhaId);
    }

    public function novo($trilhaId)
    {
        RoleMiddleware::exigir(['administrador']);
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha não encontrada.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $descricaoLonga = trim(isset($_POST['descricao_longa']) ? $_POST['descricao_longa'] : '');
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            $icone = $this->iconeSelecionado();
            $ordem = (int) (isset($_POST['ordem']) ? $_POST['ordem'] : 0);

            if ($nome === '') {
                $erro = 'Informe o nome do tema.';
            } else {
                $this->temas->criar($trilhaId, $nome, $descricaoLonga, $ativo, $icone, $ordem);
                $this->redirecionar('temas/index/' . $trilhaId);
                return;
            }
        }

        $this->renderizar('admin/temas/form', [
            'erro' => $erro,
            'trilha' => $trilha,
            'tema' => null,
        ], 'Novo tema', ['tipo' => 'temas', 'id' => (int) $trilhaId]);
    }

    private function iconeSelecionado()
    {
        $valor = isset($_POST['icone']) ? $_POST['icone'] : '';

        return array_key_exists($valor, \App\Repositories\TemaRepository::ICONES_DISPONIVEIS) ? $valor : null;
    }

    public function editar($id)
    {
        $tema = $this->temas->buscarPorId($id);

        if ($tema === null) {
            http_response_code(404);
            exit('Tema não encontrado.');
        }

        $trilha = $this->trilhas->buscarPorId($tema['trilha_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $descricaoLonga = trim(isset($_POST['descricao_longa']) ? $_POST['descricao_longa'] : '');
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            $icone = $this->iconeSelecionado();
            $ordem = (int) (isset($_POST['ordem']) ? $_POST['ordem'] : 0);

            if ($nome === '') {
                $erro = 'Informe o nome do tema.';
            } else {
                $this->temas->atualizar($id, $nome, $descricaoLonga, $ativo, $icone, $ordem);
                $tema = $this->temas->buscarPorId($id);
            }
        }

        $this->renderizar('admin/temas/form', [
            'erro' => $erro,
            'trilha' => $trilha,
            'tema' => $tema,
        ], 'Editar tema', ['tipo' => 'temas', 'id' => (int) $trilha['id']]);
    }

    /**
     * Tela do Tema com a lista de Desafios vinculados a ele - a "usabilidade"
     * pedida explicitamente na Fase 17 (Bug 2): uma tela por Tema, nao um
     * formulario generico solto.
     */
    public function desafios($temaId)
    {
        $tema = $this->temas->buscarPorId($temaId);

        if ($tema === null) {
            http_response_code(404);
            exit('Tema não encontrado.');
        }

        $trilha = $this->trilhas->buscarPorId($tema['trilha_id']);
        $lista = $this->desafios->listarPorTema($temaId);

        $this->renderizar('admin/temas/desafios', [
            'trilha' => $trilha,
            'tema' => $tema,
            'desafios' => $lista,
        ], 'Desafios de ' . $tema['nome'], ['tipo' => 'temas', 'id' => (int) $trilha['id']]);
    }

    public function removerDesafio()
    {
        RoleMiddleware::exigir(['administrador']);
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $temaId = (int) (isset($_POST['tema_id']) ? $_POST['tema_id'] : 0);

        try {
            $this->desafios->remover($id);
            $_SESSION['flash'] = 'Desafio removido.';
        } catch (\PDOException $e) {
            $_SESSION['flash'] = $e->getCode() === '23000'
                ? 'Não é possível remover: este desafio já tem equipes vinculadas.'
                : 'Não foi possível remover o desafio.';
        }

        $this->redirecionar('temas/desafios/' . $temaId);
    }

    public function novoDesafio($temaId)
    {
        RoleMiddleware::exigir(['administrador']);
        $tema = $this->temas->buscarPorId($temaId);

        if ($tema === null) {
            http_response_code(404);
            exit('Tema não encontrado.');
        }

        $trilha = $this->trilhas->buscarPorId($tema['trilha_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $pergunta = trim(isset($_POST['pergunta']) ? $_POST['pergunta'] : '');
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            $icone = $this->iconeSelecionado();
            $ordem = (int) (isset($_POST['ordem']) ? $_POST['ordem'] : 0);

            if ($pergunta === '') {
                $erro = 'Informe o texto da pergunta do desafio.';
            } else {
                $this->desafios->criar($temaId, $pergunta, $ativo, $icone, $ordem);
                $this->redirecionar('temas/desafios/' . $temaId);
                return;
            }
        }

        $this->renderizar('admin/temas/form_desafio', [
            'erro' => $erro,
            'trilha' => $trilha,
            'tema' => $tema,
            'desafio' => null,
        ], 'Novo desafio', ['tipo' => 'temas', 'id' => (int) $trilha['id']]);
    }

    public function editarDesafio($id)
    {
        $desafio = $this->desafios->buscarPorId($id);

        if ($desafio === null) {
            http_response_code(404);
            exit('Desafio não encontrado.');
        }

        $tema = $this->temas->buscarPorId($desafio['tema_id']);
        $trilha = $this->trilhas->buscarPorId($tema['trilha_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);
            $pergunta = trim(isset($_POST['pergunta']) ? $_POST['pergunta'] : '');
            $ativo = isset($_POST['ativo']) ? 1 : 0;
            $icone = $this->iconeSelecionado();
            $ordem = (int) (isset($_POST['ordem']) ? $_POST['ordem'] : 0);

            if ($pergunta === '') {
                $erro = 'Informe o texto da pergunta do desafio.';
            } else {
                $this->desafios->atualizar($id, $pergunta, $ativo, $icone, $ordem);
                $desafio = $this->desafios->buscarPorId($id);
            }
        }

        $this->renderizar('admin/temas/form_desafio', [
            'erro' => $erro,
            'trilha' => $trilha,
            'tema' => $tema,
            'desafio' => $desafio,
        ], 'Editar desafio', ['tipo' => 'temas', 'id' => (int) $trilha['id']]);
    }
}
