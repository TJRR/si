<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\SlideRepository;
use App\Services\ImagemService;

class SlideAdminController extends Controller
{
    private $slides;
    private $concursos;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->slides = new SlideRepository();
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

        $this->renderizar('admin/slides/index', [
            'concurso' => $concurso,
            'slides' => $this->slides->listarPorConcurso($concursoId),
        ], 'Slideshow de ' . $concurso['nome'], ['tipo' => 'slides', 'id' => (int) $concursoId]);
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
                $this->redirecionar('slides/index/' . $concursoId);
                return;
            }
        }

        $this->renderizar('admin/slides/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'slide' => null,
        ], 'Novo slide', ['tipo' => 'slides', 'id' => (int) $concursoId]);
    }

    public function editar($id)
    {
        $slide = $this->slides->buscarPorId($id);

        if ($slide === null) {
            http_response_code(404);
            exit('Slide não encontrado.');
        }

        $concurso = $this->concursos->buscarPorId($slide['concurso_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarEdicao($slide);
            $slide = $this->slides->buscarPorId($id);
        }

        $this->renderizar('admin/slides/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'slide' => $slide,
        ], 'Editar slide', ['tipo' => 'slides', 'id' => (int) $slide['concurso_id']]);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);
        $slide = $this->slides->buscarPorId($id);

        try {
            $this->slides->remover($id);

            if ($slide !== null) {
                $this->imagens->remover($slide['imagem_desktop_path']);
                $this->imagens->remover($slide['imagem_mobile_path']);
            }

            $_SESSION['flash'] = 'Slide removido.';
        } catch (\PDOException $e) {
            $_SESSION['flash'] = 'Não foi possível remover o slide.';
        }

        $this->redirecionar('slides/index/' . $concursoId);
    }

    public function reordenar($concursoId)
    {
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->slides->reordenar((int) $concursoId, $ids);

        echo json_encode(['ok' => true]);
    }

    private function dadosComuns()
    {
        return [
            'titulo_html' => isset($_POST['titulo_html']) ? $_POST['titulo_html'] : '',
            'separador_cor' => $this->campoOuNulo('separador_cor'),
            'cta_titulo' => $this->campoOuNulo('cta_titulo'),
            'cta_link' => $this->campoOuNulo('cta_link'),
            'cta_target' => $this->valorPermitido('cta_target', SlideRepository::CTA_TARGETS, '_self'),
            'cta_cor_fundo' => $this->campoOuNulo('cta_cor_fundo'),
            'cta_cor_texto' => $this->campoOuNulo('cta_cor_texto'),
            'cta_tamanho' => $this->valorPermitido('cta_tamanho', SlideRepository::CTA_TAMANHOS, 'medio'),
            'cta_efeito_hover' => $this->valorPermitido('cta_efeito_hover', SlideRepository::CTA_EFEITOS_HOVER, 'nenhum'),
            'cta_animacao_entrada' => $this->campoOuNulo('cta_animacao_entrada'),
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
        ];
    }

    private function campoOuNulo($chave)
    {
        $valor = trim(isset($_POST[$chave]) ? $_POST[$chave] : '');

        return $valor !== '' ? $valor : null;
    }

    private function valorPermitido($chave, array $permitidos, $padrao)
    {
        $valor = isset($_POST[$chave]) ? $_POST[$chave] : $padrao;

        return in_array($valor, $permitidos, true) ? $valor : $padrao;
    }

    private function salvarNovo($concursoId)
    {
        $alt = trim(isset($_POST['imagem_alt']) ? $_POST['imagem_alt'] : '');

        if ($alt === '') {
            return 'Informe o texto alternativo (alt) da imagem.';
        }

        if (empty($_FILES['imagem_desktop']) || $_FILES['imagem_desktop']['error'] !== UPLOAD_ERR_OK) {
            return 'Envie a imagem desktop do slide (1440×800).';
        }

        try {
            $caminhoDesktop = $this->imagens->salvar($_FILES['imagem_desktop'], 'slides', 1440, 800);
            $caminhoMobile = null;

            if (!empty($_FILES['imagem_mobile']) && $_FILES['imagem_mobile']['error'] === UPLOAD_ERR_OK) {
                $caminhoMobile = $this->imagens->salvar($_FILES['imagem_mobile'], 'slides', 768, 800);
            }
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $dados = $this->dadosComuns();

        if (!empty($dados['cta_titulo']) && empty($dados['cta_link'])) {
            return 'Informe o link do botão (ou remova o título do botão) — o sistema não permite salvar um botão sem destino.';
        }

        $dados['imagem_desktop_path'] = $caminhoDesktop;
        $dados['imagem_mobile_path'] = $caminhoMobile;
        $dados['imagem_alt'] = $alt;

        $this->slides->criar($concursoId, $dados);

        return null;
    }

    private function salvarEdicao(array $slideAtual)
    {
        $alt = trim(isset($_POST['imagem_alt']) ? $_POST['imagem_alt'] : '');

        if ($alt === '') {
            return 'Informe o texto alternativo (alt) da imagem.';
        }

        $caminhoDesktop = $slideAtual['imagem_desktop_path'];
        $caminhoMobile = $slideAtual['imagem_mobile_path'];

        try {
            if (!empty($_FILES['imagem_desktop']) && $_FILES['imagem_desktop']['error'] === UPLOAD_ERR_OK) {
                $caminhoDesktop = $this->imagens->salvar($_FILES['imagem_desktop'], 'slides', 1440, 800);
                $this->imagens->remover($slideAtual['imagem_desktop_path']);
            }

            if (!empty($_FILES['imagem_mobile']) && $_FILES['imagem_mobile']['error'] === UPLOAD_ERR_OK) {
                $caminhoMobile = $this->imagens->salvar($_FILES['imagem_mobile'], 'slides', 768, 800);
                $this->imagens->remover($slideAtual['imagem_mobile_path']);
            }
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $dados = $this->dadosComuns();

        if (!empty($dados['cta_titulo']) && empty($dados['cta_link'])) {
            return 'Informe o link do botão (ou remova o título do botão) — o sistema não permite salvar um botão sem destino.';
        }

        $dados['imagem_desktop_path'] = $caminhoDesktop;
        $dados['imagem_mobile_path'] = $caminhoMobile;
        $dados['imagem_alt'] = $alt;

        $this->slides->atualizar($slideAtual['id'], $dados);

        return null;
    }
}
