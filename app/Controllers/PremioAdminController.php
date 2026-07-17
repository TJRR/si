<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\PremioRepository;
use App\Services\ImagemService;

class PremioAdminController extends Controller
{
    private $premios;
    private $concursos;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->premios = new PremioRepository();
        $this->concursos = new ConcursoRepository();
        $this->imagens = new ImagemService();
    }

    public function index($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $this->renderizar('admin/premios/index', [
            'concurso' => $concurso,
            'premios' => $this->premios->listarPorConcurso($concursoId),
        ], 'Premiação de ' . $concurso['nome'], ['tipo' => 'premios', 'id' => (int) $concursoId]);
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
            $erro = $this->salvarNovo($concursoId);

            if ($erro === null) {
                $this->redirecionar('premios/index/' . $concursoId);
                return;
            }
        }

        $this->renderizar('admin/premios/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'premio' => null,
        ], 'Novo prêmio', ['tipo' => 'premios', 'id' => (int) $concursoId]);
    }

    public function editar($id)
    {
        $premio = $this->premios->buscarPorId($id);

        if ($premio === null) {
            http_response_code(404);
            exit('Prêmio não encontrado.');
        }

        $concurso = $this->concursos->buscarPorId($premio['concurso_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarEdicao($premio);
            $premio = $this->premios->buscarPorId($id);
        }

        $this->renderizar('admin/premios/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'premio' => $premio,
        ], 'Editar prêmio', ['tipo' => 'premios', 'id' => (int) $premio['concurso_id']]);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);
        $premio = $this->premios->buscarPorId($id);

        $this->premios->remover($id);

        if ($premio !== null) {
            $this->imagens->remover($premio['imagem_path']);
        }

        $_SESSION['flash'] = 'Prêmio removido.';
        $this->redirecionar('premios/index/' . $concursoId);
    }

    public function reordenar($concursoId)
    {
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->premios->reordenar((int) $concursoId, $ids);

        echo json_encode(['ok' => true]);
    }

    private function salvarNovo($concursoId)
    {
        $posicao = (int) (isset($_POST['posicao']) ? $_POST['posicao'] : 0);
        $descricao = trim(isset($_POST['descricao']) ? $_POST['descricao'] : '');

        if ($posicao < 1) {
            return 'Informe a posição (1, 2, 3...).';
        }

        if ($descricao === '') {
            return 'Informe a descrição do prêmio.';
        }

        $dados = ['posicao' => $posicao, 'descricao' => $descricao, 'imagem_path' => null, 'imagem_alt' => null];

        try {
            if (!empty($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $alt = trim(isset($_POST['imagem_alt']) ? $_POST['imagem_alt'] : '');

                if ($alt === '') {
                    return 'Informe o texto alternativo (alt) da imagem.';
                }

                $dados['imagem_path'] = $this->imagens->salvar($_FILES['imagem'], 'premios', 600, 600);
                $dados['imagem_alt'] = $alt;
            }
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $this->premios->criar($concursoId, $dados);

        return null;
    }

    private function salvarEdicao(array $premioAtual)
    {
        $posicao = (int) (isset($_POST['posicao']) ? $_POST['posicao'] : 0);
        $descricao = trim(isset($_POST['descricao']) ? $_POST['descricao'] : '');

        if ($posicao < 1) {
            return 'Informe a posição (1, 2, 3...).';
        }

        if ($descricao === '') {
            return 'Informe a descrição do prêmio.';
        }

        $dados = [
            'posicao' => $posicao,
            'descricao' => $descricao,
            'imagem_path' => $premioAtual['imagem_path'],
            'imagem_alt' => $premioAtual['imagem_alt'],
        ];

        try {
            if (!empty($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $alt = trim(isset($_POST['imagem_alt']) ? $_POST['imagem_alt'] : '');

                if ($alt === '') {
                    return 'Informe o texto alternativo (alt) da imagem.';
                }

                $dados['imagem_path'] = $this->imagens->salvar($_FILES['imagem'], 'premios', 600, 600);
                $dados['imagem_alt'] = $alt;
                $this->imagens->remover($premioAtual['imagem_path']);
            } elseif (isset($_POST['imagem_alt'])) {
                $dados['imagem_alt'] = trim($_POST['imagem_alt']);
            }
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $this->premios->atualizar($premioAtual['id'], $dados);

        return null;
    }
}
