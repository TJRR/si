<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\BannerRepository;
use App\Services\ImagemService;

class BannerAdminController extends Controller
{
    private $banners;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->banners = new BannerRepository();
        $this->imagens = new ImagemService();
    }

    public function index()
    {
        $this->renderizar('admin/banners/index', [
            'banners' => $this->banners->listar(),
        ], 'Banners', ['tipo' => 'configuracaoBanners', 'id' => null]);
    }

    public function novo()
    {
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarNovo();

            if ($erro === null) {
                $this->redirecionar('banners/index');
                return;
            }
        }

        $this->renderizar('admin/banners/form', [
            'erro' => $erro,
            'banner' => null,
        ], 'Novo banner', ['tipo' => 'configuracaoBanners', 'id' => null]);
    }

    public function editar($id)
    {
        $banner = $this->banners->buscarPorId($id);

        if ($banner === null) {
            http_response_code(404);
            exit('Banner não encontrado.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarEdicao($banner);
            $banner = $this->banners->buscarPorId($id);
        }

        $this->renderizar('admin/banners/form', [
            'erro' => $erro,
            'banner' => $banner,
        ], 'Editar banner', ['tipo' => 'configuracaoBanners', 'id' => null]);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $banner = $this->banners->buscarPorId($id);

        try {
            $this->banners->remover($id);

            if ($banner !== null) {
                $this->imagens->remover($banner['imagem_desktop_path']);
                $this->imagens->remover($banner['imagem_mobile_path']);
            }

            $_SESSION['flash'] = 'Banner removido.';
        } catch (\PDOException $e) {
            $_SESSION['flash'] = 'Não foi possível remover o banner.';
        }

        $this->redirecionar('banners/index');
    }

    public function reordenar()
    {
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->banners->reordenar($ids);

        echo json_encode(['ok' => true]);
    }

    private function dadosComuns()
    {
        return [
            'conteudo_html' => isset($_POST['conteudo_html']) ? $_POST['conteudo_html'] : '',
            'conteudo_alinhamento' => $this->valorPermitido('conteudo_alinhamento', BannerRepository::CONTEUDO_ALINHAMENTOS, 'centro'),
            'cor_fundo' => $this->campoOuNulo('cor_fundo'),
            'cta_titulo' => $this->campoOuNulo('cta_titulo'),
            'cta_destino_tipo' => $this->valorPermitidoOuNulo('cta_destino_tipo', BannerRepository::CTA_DESTINO_TIPOS),
            'cta_destino_valor' => $this->campoOuNulo('cta_destino_valor'),
            'cta_posicao' => $this->valorPermitido('cta_posicao', BannerRepository::CTA_POSICOES, 'centro_centro'),
            'cta_efeito_hover' => $this->valorPermitido('cta_efeito_hover', BannerRepository::CTA_EFEITOS_HOVER, 'nenhum'),
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

    private function valorPermitidoOuNulo($chave, array $permitidos)
    {
        $valor = isset($_POST[$chave]) ? $_POST[$chave] : '';

        return in_array($valor, $permitidos, true) ? $valor : null;
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

                $caminhoDesktop = $this->imagens->salvar($_FILES['imagem'], 'banners', 1440, 400);
                $caminhoMobile = $this->imagens->salvar($_FILES['imagem'], 'banners', 768, 400);
            }
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $dados = $this->dadosComuns();

        if (!empty($dados['cta_titulo']) && (empty($dados['cta_destino_tipo']) || empty($dados['cta_destino_valor']))) {
            return 'Selecione o destino do botão e informe o valor (ou remova o título do botão) — o sistema não permite salvar um botão sem destino.';
        }

        $dados['imagem_desktop_path'] = $caminhoDesktop;
        $dados['imagem_mobile_path'] = $caminhoMobile;
        $dados['imagem_alt'] = $alt;

        $this->banners->criar($dados);

        return null;
    }

    private function salvarEdicao(array $bannerAtual)
    {
        $caminhoDesktop = $bannerAtual['imagem_desktop_path'];
        $caminhoMobile = $bannerAtual['imagem_mobile_path'];
        $alt = $bannerAtual['imagem_alt'];

        try {
            if (!empty($_FILES['imagem']) && $_FILES['imagem']['error'] === UPLOAD_ERR_OK) {
                $alt = trim(isset($_POST['imagem_alt']) ? $_POST['imagem_alt'] : '');

                if ($alt === '') {
                    return 'Informe o texto alternativo (alt) da imagem.';
                }

                $caminhoDesktop = $this->imagens->salvar($_FILES['imagem'], 'banners', 1440, 400);
                $caminhoMobile = $this->imagens->salvar($_FILES['imagem'], 'banners', 768, 400);
                $this->imagens->remover($bannerAtual['imagem_desktop_path']);
                $this->imagens->remover($bannerAtual['imagem_mobile_path']);
            } elseif (isset($_POST['imagem_alt'])) {
                $alt = trim($_POST['imagem_alt']);
            }
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $dados = $this->dadosComuns();

        if (!empty($dados['cta_titulo']) && (empty($dados['cta_destino_tipo']) || empty($dados['cta_destino_valor']))) {
            return 'Selecione o destino do botão e informe o valor (ou remova o título do botão) — o sistema não permite salvar um botão sem destino.';
        }

        $dados['imagem_desktop_path'] = $caminhoDesktop;
        $dados['imagem_mobile_path'] = $caminhoMobile;
        $dados['imagem_alt'] = $alt;

        $this->banners->atualizar($bannerAtual['id'], $dados);

        return null;
    }
}
