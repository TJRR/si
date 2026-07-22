<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\SlideRepository;
use App\Services\ImagemService;

class SlideAdminController extends Controller
{
    private $slides;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->slides = new SlideRepository();
        $this->imagens = new ImagemService();
    }

    public function index()
    {
        $this->renderizar('admin/slides/index', [
            'slides' => $this->slides->listar(),
        ], 'Slideshow', ['tipo' => 'configuracaoSlides', 'id' => null]);
    }

    public function novo()
    {
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarNovo();

            if ($erro === null) {
                $this->redirecionar('slides/index');
                return;
            }
        }

        $this->renderizar('admin/slides/form', [
            'erro' => $erro,
            'slide' => null,
        ], 'Novo slide', ['tipo' => 'configuracaoSlides', 'id' => null]);
    }

    public function editar($id)
    {
        $slide = $this->slides->buscarPorId($id);

        if ($slide === null) {
            http_response_code(404);
            exit('Slide não encontrado.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarEdicao($slide);
            $slide = $this->slides->buscarPorId($id);
        }

        $this->renderizar('admin/slides/form', [
            'erro' => $erro,
            'slide' => $slide,
        ], 'Editar slide', ['tipo' => 'configuracaoSlides', 'id' => null]);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
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

        $this->redirecionar('slides/index');
    }

    public function reordenar()
    {
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->slides->reordenar($ids);

        echo json_encode(['ok' => true]);
    }

    private function dadosComuns()
    {
        $duracaoSegundos = (int) (isset($_POST['duracao_segundos']) ? $_POST['duracao_segundos'] : 7);
        $duracaoSegundos = $duracaoSegundos >= 1 && $duracaoSegundos <= 30 ? $duracaoSegundos : 7;

        return [
            'cor_fundo' => $this->campoOuNulo('cor_fundo'),
            'duracao_ms' => $duracaoSegundos * 1000,
            'efeito_transicao' => $this->valorPermitido('efeito_transicao', SlideRepository::EFEITOS_TRANSICAO, 'fade'),
            'overlay_efeito' => $this->valorPermitido('overlay_efeito', SlideRepository::OVERLAY_EFEITOS, 'nenhum'),
            'overlay_cor' => $this->campoOuNulo('overlay_cor'),
            'overlay_opacidade' => $this->overlayOpacidade(),
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

    private function overlayOpacidade()
    {
        $opacidade = (int) (isset($_POST['overlay_opacidade']) ? $_POST['overlay_opacidade'] : 40);

        return $opacidade >= 0 && $opacidade <= 100 ? $opacidade : 40;
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

    private function salvarNovo()
    {
        try {
            $caminhoDesktop = null;
            $caminhoMobile = null;
            $alt = null;

            if (!empty($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $alt = trim(isset($_POST['imagem_alt']) ? $_POST['imagem_alt'] : '');

                if ($alt === '') {
                    return 'Informe o texto alternativo (alt) da imagem.';
                }

                $caminhoDesktop = $this->imagens->salvar($_FILES['imagem'], 'slides', 1440, 800);
                $caminhoMobile = $this->imagens->salvar($_FILES['imagem'], 'slides', 768, 800);
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

        $this->slides->criar($dados);

        return null;
    }

    private function salvarEdicao(array $slideAtual)
    {
        $caminhoDesktop = $slideAtual['imagem_desktop_path'];
        $caminhoMobile = $slideAtual['imagem_mobile_path'];
        $alt = $slideAtual['imagem_alt'];

        try {
            if (!empty($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $alt = trim(isset($_POST['imagem_alt']) ? $_POST['imagem_alt'] : '');

                if ($alt === '') {
                    return 'Informe o texto alternativo (alt) da imagem.';
                }

                $caminhoDesktop = $this->imagens->salvar($_FILES['imagem'], 'slides', 1440, 800);
                $caminhoMobile = $this->imagens->salvar($_FILES['imagem'], 'slides', 768, 800);
                $this->imagens->remover($slideAtual['imagem_desktop_path']);
                $this->imagens->remover($slideAtual['imagem_mobile_path']);
            } elseif (isset($_POST['imagem_alt'])) {
                $alt = trim($_POST['imagem_alt']);
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
