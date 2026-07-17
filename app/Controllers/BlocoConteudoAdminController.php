<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Core\Texto;
use App\Middleware\RoleMiddleware;
use App\Repositories\BlocoConteudoRepository;
use App\Repositories\ConcursoRepository;
use App\Services\ImagemService;

class BlocoConteudoAdminController extends Controller
{
    private $blocos;
    private $concursos;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->blocos = new BlocoConteudoRepository();
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

        $this->blocos->garantirBlocosPadrao($concursoId);

        $this->renderizar('admin/blocos/index', [
            'concurso' => $concurso,
            'blocos' => $this->blocos->listarPorConcurso($concursoId),
        ], 'Blocos de conteúdo de ' . $concurso['nome'], ['tipo' => 'blocos', 'id' => (int) $concursoId]);
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
                $this->redirecionar('blocos/index/' . $concursoId);
                return;
            }
        }

        $this->renderizar('admin/blocos/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'bloco' => null,
        ], 'Novo bloco', ['tipo' => 'blocos', 'id' => (int) $concursoId]);
    }

    public function editar($id)
    {
        $bloco = $this->blocos->buscarPorId($id);

        if ($bloco === null) {
            http_response_code(404);
            exit('Bloco não encontrado.');
        }

        $concurso = $this->concursos->buscarPorId($bloco['concurso_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarEdicao($bloco);
            $bloco = $this->blocos->buscarPorId($id);
        }

        $this->renderizar('admin/blocos/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'bloco' => $bloco,
        ], 'Editar bloco', ['tipo' => 'blocos', 'id' => (int) $bloco['concurso_id']]);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);
        $bloco = $this->blocos->buscarPorId($id);

        if ($bloco !== null && $bloco['chave'] !== null) {
            $_SESSION['flash'] = 'Este é um bloco padrão do sistema (Sobre/Premiação) e não pode ser removido — apenas editado ou desativado.';
            $this->redirecionar('blocos/index/' . $concursoId);
            return;
        }

        try {
            $this->blocos->remover($id);

            if ($bloco !== null) {
                $this->imagens->remover($bloco['imagem_path']);
            }

            $_SESSION['flash'] = 'Bloco removido.';
        } catch (\PDOException $e) {
            $_SESSION['flash'] = 'Não foi possível remover o bloco.';
        }

        $this->redirecionar('blocos/index/' . $concursoId);
    }

    public function reordenar($concursoId)
    {
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->blocos->reordenar((int) $concursoId, $ids);

        echo json_encode(['ok' => true]);
    }

    private function dadosComuns()
    {
        $ancora = Texto::slugify(trim(isset($_POST['secao_ancora']) ? $_POST['secao_ancora'] : ''));

        return [
            'titulo' => trim(isset($_POST['titulo']) ? $_POST['titulo'] : ''),
            'conteudo_html' => isset($_POST['conteudo_html']) ? $_POST['conteudo_html'] : '',
            'cta_titulo' => $this->campoOuNulo('cta_titulo'),
            'cta_link' => $this->campoOuNulo('cta_link'),
            'secao_ancora' => $ancora !== '' ? $ancora : 'bloco',
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
        ];
    }

    private function campoOuNulo($chave)
    {
        $valor = trim(isset($_POST[$chave]) ? $_POST[$chave] : '');

        return $valor !== '' ? $valor : null;
    }

    private function salvarNovo($concursoId)
    {
        $dados = $this->dadosComuns();

        if ($dados['titulo'] === '') {
            return 'Informe o título do bloco.';
        }

        if (!empty($dados['cta_titulo']) && empty($dados['cta_link'])) {
            return 'Informe o link do botão (ou remova o título do botão) — o sistema não permite salvar um botão sem destino.';
        }

        try {
            $dados['imagem_path'] = null;
            $dados['imagem_alt'] = null;

            if (!empty($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $alt = trim(isset($_POST['imagem_alt']) ? $_POST['imagem_alt'] : '');

                if ($alt === '') {
                    return 'Informe o texto alternativo (alt) da imagem.';
                }

                $dados['imagem_path'] = $this->imagens->salvar($_FILES['imagem'], 'blocos', 900, 900);
                $dados['imagem_alt'] = $alt;
            }
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $this->blocos->criar($concursoId, $dados);

        return null;
    }

    private function salvarEdicao(array $blocoAtual)
    {
        $dados = $this->dadosComuns();

        if ($dados['titulo'] === '') {
            return 'Informe o título do bloco.';
        }

        if (!empty($dados['cta_titulo']) && empty($dados['cta_link'])) {
            return 'Informe o link do botão (ou remova o título do botão) — o sistema não permite salvar um botão sem destino.';
        }

        $dados['imagem_path'] = $blocoAtual['imagem_path'];
        $dados['imagem_alt'] = $blocoAtual['imagem_alt'];

        try {
            if (!empty($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $alt = trim(isset($_POST['imagem_alt']) ? $_POST['imagem_alt'] : '');

                if ($alt === '') {
                    return 'Informe o texto alternativo (alt) da imagem.';
                }

                $dados['imagem_path'] = $this->imagens->salvar($_FILES['imagem'], 'blocos', 900, 900);
                $dados['imagem_alt'] = $alt;
                $this->imagens->remover($blocoAtual['imagem_path']);
            } elseif (isset($_POST['imagem_alt'])) {
                $dados['imagem_alt'] = trim($_POST['imagem_alt']);
            }
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $this->blocos->atualizar($blocoAtual['id'], $dados);

        return null;
    }
}
