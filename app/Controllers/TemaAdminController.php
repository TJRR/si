<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\BannerRepository;
use App\Repositories\ConfiguracaoVisualRepository;
use App\Services\ImagemService;

/**
 * Fase 18 (4.9): identidade visual do site. Fase 19 (#84 v2): deixou de
 * ter override por concurso - "Tema" (Favicon+Cores), "Cabeçalho" (Logo +
 * imagem de fundo + logo clara) e "Rodapé" (logo do rodapé + atalhos de
 * navegação) são 3 abas da tela "Configuração", todas 100% globais.
 */
class TemaAdminController extends Controller
{
    private $configuracaoVisual;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->configuracaoVisual = new ConfiguracaoVisualRepository();
        $this->imagens = new ImagemService();
    }

    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!empty($_POST['cor_primaria_inicio']) && !empty($_POST['cor_primaria_fim']) && !empty($_POST['cor_secundaria'])) {
                $this->configuracaoVisual->atualizar(
                    trim($_POST['cor_primaria_inicio']),
                    trim($_POST['cor_primaria_fim']),
                    trim($_POST['cor_secundaria'])
                );
            }

            $this->salvarFavicon();

            if (empty($_SESSION['flash'])) {
                $_SESSION['flash'] = 'Tema atualizado.';
            }

            $this->redirecionar('tema/index');
            return;
        }

        $this->renderizar('admin/tema/form', [
            'configuracaoVisual' => $this->configuracaoVisual->buscar(),
        ], 'Tema', ['tipo' => 'configuracaoTema', 'id' => null]);
    }

    public function cabecalho()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->salvarCabecalho();

            if (empty($_SESSION['flash'])) {
                $_SESSION['flash'] = 'Cabeçalho atualizado.';
            }

            $this->redirecionar('tema/cabecalho');
            return;
        }

        $this->renderizar('admin/tema/cabecalho', [
            'configuracaoVisual' => $this->configuracaoVisual->buscar(),
        ], 'Cabeçalho', ['tipo' => 'configuracaoCabecalho', 'id' => null]);
    }

    public function rodape()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->salvarRodape();

            if (empty($_SESSION['flash'])) {
                $_SESSION['flash'] = 'Rodapé atualizado.';
            }

            $this->redirecionar('tema/rodape');
            return;
        }

        $this->renderizar('admin/tema/rodape', [
            'configuracaoVisual' => $this->configuracaoVisual->buscar(),
        ], 'Rodapé', ['tipo' => 'configuracaoRodape', 'id' => null]);
    }

    private function salvarCabecalho()
    {
        $atual = $this->configuracaoVisual->buscar();
        $logoPath = $atual !== false ? $atual['logo_path'] : null;
        $cabecalhoImagemPath = $atual !== false ? $atual['cabecalho_imagem_path'] : null;
        $cabecalhoLogoClaroPath = $atual !== false ? $atual['cabecalho_logo_claro_path'] : null;

        if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            try {
                $novoCaminho = $this->imagens->salvar($_FILES['logo'], 'logo', 600, 200);

                if ($logoPath !== null) {
                    $this->imagens->remover($logoPath);
                }

                $logoPath = $novoCaminho;
                $this->configuracaoVisual->atualizarLogo($logoPath);
            } catch (\RuntimeException $e) {
                $_SESSION['flash'] = $e->getMessage();
            }
        }

        if (!empty($_FILES['cabecalho_imagem']) && $_FILES['cabecalho_imagem']['error'] === UPLOAD_ERR_OK) {
            try {
                $novoCaminho = $this->imagens->salvar($_FILES['cabecalho_imagem'], 'cabecalho', 1920, 800);

                if ($cabecalhoImagemPath !== null) {
                    $this->imagens->remover($cabecalhoImagemPath);
                }

                $cabecalhoImagemPath = $novoCaminho;
            } catch (\RuntimeException $e) {
                $_SESSION['flash'] = $e->getMessage();
            }
        }

        if (!empty($_FILES['logo_claro']) && $_FILES['logo_claro']['error'] === UPLOAD_ERR_OK) {
            try {
                $novoCaminho = $this->imagens->salvar($_FILES['logo_claro'], 'logo', 320, 120);

                if ($cabecalhoLogoClaroPath !== null) {
                    $this->imagens->remover($cabecalhoLogoClaroPath);
                }

                $cabecalhoLogoClaroPath = $novoCaminho;
            } catch (\RuntimeException $e) {
                $_SESSION['flash'] = $e->getMessage();
            }
        }

        $cabecalhoTituloHtml = isset($_POST['cabecalho_titulo_html']) ? $_POST['cabecalho_titulo_html'] : '';

        $efeitoTransicao = isset($_POST['cabecalho_efeito_transicao']) ? $_POST['cabecalho_efeito_transicao'] : 'onda';
        $efeitoTransicao = in_array($efeitoTransicao, ConfiguracaoVisualRepository::CABECALHO_EFEITOS_TRANSICAO, true) ? $efeitoTransicao : 'onda';

        $overlayOpacidade = (int) (isset($_POST['cabecalho_overlay_opacidade']) ? $_POST['cabecalho_overlay_opacidade'] : 50);
        $overlayOpacidade = $overlayOpacidade >= 0 && $overlayOpacidade <= 100 ? $overlayOpacidade : 50;

        $imagemPosicao = isset($_POST['cabecalho_imagem_posicao']) ? $_POST['cabecalho_imagem_posicao'] : 'superior_centro';
        $imagemPosicao = in_array($imagemPosicao, BannerRepository::CTA_POSICOES, true) ? $imagemPosicao : 'superior_centro';

        $efeitoEntrada = isset($_POST['cabecalho_efeito_entrada']) ? $_POST['cabecalho_efeito_entrada'] : 'nenhum';
        $efeitoEntrada = in_array($efeitoEntrada, ConfiguracaoVisualRepository::CABECALHO_EFEITOS_ENTRADA, true) ? $efeitoEntrada : 'nenhum';

        $this->configuracaoVisual->atualizarCabecalho($cabecalhoImagemPath, $cabecalhoLogoClaroPath, $cabecalhoTituloHtml, $efeitoTransicao, $overlayOpacidade, $imagemPosicao, $efeitoEntrada);
    }

    private function salvarRodape()
    {
        $atual = $this->configuracaoVisual->buscar();
        $rodapeLogoPath = $atual !== false ? $atual['rodape_logo_path'] : null;

        if (!empty($_FILES['rodape_logo']) && $_FILES['rodape_logo']['error'] === UPLOAD_ERR_OK) {
            try {
                $novoCaminho = $this->imagens->salvar($_FILES['rodape_logo'], 'logo', 600, 200);

                if ($rodapeLogoPath !== null) {
                    $this->imagens->remover($rodapeLogoPath);
                }

                $rodapeLogoPath = $novoCaminho;
            } catch (\RuntimeException $e) {
                $_SESSION['flash'] = $e->getMessage();
            }
        }

        $this->configuracaoVisual->atualizarRodape(
            $rodapeLogoPath,
            isset($_POST['rodape_mostrar_trilhas']) ? 1 : 0,
            isset($_POST['rodape_mostrar_cronograma']) ? 1 : 0,
            isset($_POST['rodape_mostrar_desafios']) ? 1 : 0,
            isset($_POST['rodape_mostrar_contato']) ? 1 : 0
        );
    }

    private function salvarFavicon()
    {
        if (empty($_FILES['favicon']) || $_FILES['favicon']['error'] === UPLOAD_ERR_NO_FILE) {
            return;
        }

        if ($_FILES['favicon']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash'] = 'Falha ao enviar o favicon.';
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['favicon']['tmp_name']);

        if ($mime !== 'image/png') {
            $_SESSION['flash'] = 'Favicon precisa ser um arquivo PNG.';
            return;
        }

        try {
            $novoCaminho = $this->imagens->salvar($_FILES['favicon'], 'favicon', 512, 512, false);
        } catch (\RuntimeException $e) {
            $_SESSION['flash'] = $e->getMessage();
            return;
        }

        $atual = $this->configuracaoVisual->buscar();

        if ($atual !== false && !empty($atual['favicon_path'])) {
            $this->imagens->remover($atual['favicon_path']);
        }

        $this->configuracaoVisual->atualizarFavicon($novoCaminho);
    }
}
