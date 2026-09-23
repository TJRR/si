<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EventoBannerRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Services\ImagemService;

/**
 * Fase 50: faixas (Banner/Hero) proprias de cada Evento (sub-aba "Faixas"
 * da ficha do Evento) - mesmo desenho de BannerAdminController (Concurso),
 * mas toda acao e escopada por evento_id.
 */
class EventoBannerAdminController extends Controller
{
    private $eventos;
    private $banners;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->banners = new EventoBannerRepository();
        $this->imagens = new ImagemService();
    }

    private function eventoOu404($eventoId)
    {
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        return $evento;
    }

    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);

        $this->renderizar('admin/evento_banners/index', [
            'evento' => $evento,
            'banners' => $this->banners->listar($eventoId),
        ], 'Faixas: ' . $evento['nome'], ['tipo' => 'eventoBanners', 'id' => (int) $eventoId]);
    }

    public function novo($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarNovo($eventoId);

            if ($erro === null) {
                $this->redirecionar('eventoBanners/index/' . $eventoId);
                return;
            }
        }

        $this->renderizar('admin/evento_banners/form', [
            'erro' => $erro,
            'evento' => $evento,
            'banner' => null,
        ], 'Nova faixa: ' . $evento['nome'], ['tipo' => 'eventoBanners', 'id' => (int) $eventoId]);
    }

    public function editar($id)
    {
        $banner = $this->banners->buscarPorId($id);

        if ($banner === null) {
            http_response_code(404);
            exit('Faixa não encontrada.');
        }

        $evento = $this->eventoOu404($banner['evento_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarEdicao($banner);
            $banner = $this->banners->buscarPorId($id);
        }

        $this->renderizar('admin/evento_banners/form', [
            'erro' => $erro,
            'evento' => $evento,
            'banner' => $banner,
        ], 'Editar faixa: ' . $evento['nome'], ['tipo' => 'eventoBanners', 'id' => (int) $evento['id']]);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $eventoId = (int) (isset($_POST['evento_id']) ? $_POST['evento_id'] : 0);
        $banner = $this->banners->buscarPorId($id);

        try {
            $this->banners->remover($id);

            if ($banner !== null) {
                $this->imagens->remover($banner['imagem_desktop_path']);
                $this->imagens->remover($banner['imagem_mobile_path']);
            }

            $_SESSION['flash'] = 'Faixa removida.';
        } catch (\PDOException $e) {
            flashErro('Não foi possível remover a faixa.');
        }

        $this->redirecionar('eventoBanners/index/' . $eventoId);
    }

    public function reordenar($eventoId)
    {
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->banners->reordenar($eventoId, $ids);

        echo json_encode(['ok' => true]);
    }

    private function dadosComuns()
    {
        return [
            'conteudo_html' => isset($_POST['conteudo_html']) ? sanitizarHtmlRico($_POST['conteudo_html']) : '',
            'conteudo_alinhamento' => $this->valorPermitido('conteudo_alinhamento', EventoBannerRepository::CONTEUDO_ALINHAMENTOS, 'centro'),
            'cor_fundo' => $this->campoOuNulo('cor_fundo'),
            'cta_titulo' => $this->campoOuNulo('cta_titulo'),
            'cta_destino_tipo' => $this->valorPermitidoOuNulo('cta_destino_tipo', EventoBannerRepository::CTA_DESTINO_TIPOS),
            'cta_destino_valor' => $this->campoOuNulo('cta_destino_valor'),
            'cta_posicao' => $this->valorPermitido('cta_posicao', EventoBannerRepository::CTA_POSICOES, 'centro_centro'),
            'cta_efeito_hover' => $this->valorPermitido('cta_efeito_hover', EventoBannerRepository::CTA_EFEITOS_HOVER, 'nenhum'),
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

    private function salvarNovo($eventoId)
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

                $caminhoDesktop = $this->imagens->salvar($_FILES['imagem'], 'evento-banners', 1440, 400);
                $caminhoMobile = $this->imagens->salvar($_FILES['imagem'], 'evento-banners', 768, 400);
            }
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $dados = $this->dadosComuns();

        if (!empty($dados['cta_titulo']) && (empty($dados['cta_destino_tipo']) || empty($dados['cta_destino_valor']))) {
            return 'Selecione o destino do botão e informe o valor (ou remova o título do botão). O sistema não permite salvar um botão sem destino.';
        }

        $dados['imagem_desktop_path'] = $caminhoDesktop;
        $dados['imagem_mobile_path'] = $caminhoMobile;
        $dados['imagem_alt'] = $alt;

        $this->banners->criar($eventoId, $dados);

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

                $caminhoDesktop = $this->imagens->salvar($_FILES['imagem'], 'evento-banners', 1440, 400);
                $caminhoMobile = $this->imagens->salvar($_FILES['imagem'], 'evento-banners', 768, 400);
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
            return 'Selecione o destino do botão e informe o valor (ou remova o título do botão). O sistema não permite salvar um botão sem destino.';
        }

        $dados['imagem_desktop_path'] = $caminhoDesktop;
        $dados['imagem_mobile_path'] = $caminhoMobile;
        $dados['imagem_alt'] = $alt;

        $this->banners->atualizar($bannerAtual['id'], $dados);

        return null;
    }
}
