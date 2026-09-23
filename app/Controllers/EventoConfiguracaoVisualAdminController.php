<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EventoConfiguracaoVisualRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Services\ImagemService;

/**
 * Fase 50: cabecalho da pagina publica propria de cada Evento (sub-aba
 * "Cabeçalho" da ficha do Evento) - mesmo desenho de
 * TemaAdminController::cabecalho()/salvarCabecalho() (Concurso), mas
 * escopado por evento_id e com o campo extra "publicado" (controla se
 * evento/index/{id} responde ou nao - ver EventoPublicoController).
 */
class EventoConfiguracaoVisualAdminController extends Controller
{
    private $eventos;
    private $configuracoes;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->configuracoes = new EventoConfiguracaoVisualRepository();
        $this->imagens = new ImagemService();
    }

    public function cabecalho($eventoId)
    {
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->salvarCabecalho($eventoId);

            if (empty($_SESSION['flash'])) {
                $_SESSION['flash'] = 'Cabeçalho atualizado.';
            }

            $this->redirecionar('eventoCabecalho/cabecalho/' . $eventoId);
            return;
        }

        $this->renderizar('admin/evento_configuracao_visual/cabecalho', [
            'evento' => $evento,
            'configuracaoVisual' => $this->configuracoes->buscarPorEvento($eventoId),
        ], 'Cabeçalho: ' . $evento['nome'], ['tipo' => 'eventoCabecalho', 'id' => (int) $eventoId]);
    }

    private function salvarCabecalho($eventoId)
    {
        $atual = $this->configuracoes->buscarPorEvento($eventoId);
        $cabecalhoImagemPath = $atual !== null ? $atual['cabecalho_imagem_path'] : null;
        $cabecalhoLogoClaroPath = $atual !== null ? $atual['cabecalho_logo_claro_path'] : null;
        $logoPath = $atual !== null ? $atual['logo_path'] : null;

        if (!empty($_POST['remover_cabecalho_imagem']) && $cabecalhoImagemPath !== null) {
            $this->imagens->remover($cabecalhoImagemPath);
            $cabecalhoImagemPath = null;
        }

        if (!empty($_POST['remover_logo_claro']) && $cabecalhoLogoClaroPath !== null) {
            $this->imagens->remover($cabecalhoLogoClaroPath);
            $cabecalhoLogoClaroPath = null;
        }

        if (!empty($_FILES['cabecalho_imagem']) && $_FILES['cabecalho_imagem']['error'] === UPLOAD_ERR_OK) {
            try {
                $novoCaminho = $this->imagens->salvar($_FILES['cabecalho_imagem'], 'evento-cabecalho', 1920, 800);

                if ($cabecalhoImagemPath !== null) {
                    $this->imagens->remover($cabecalhoImagemPath);
                }

                $cabecalhoImagemPath = $novoCaminho;
            } catch (\RuntimeException $e) {
                flashErro($e->getMessage());
            }
        }

        if (!empty($_FILES['logo_claro']) && $_FILES['logo_claro']['error'] === UPLOAD_ERR_OK) {
            try {
                $novoCaminho = $this->imagens->salvar($_FILES['logo_claro'], 'evento-logo', 320, 120);

                if ($cabecalhoLogoClaroPath !== null) {
                    $this->imagens->remover($cabecalhoLogoClaroPath);
                }

                $cabecalhoLogoClaroPath = $novoCaminho;
            } catch (\RuntimeException $e) {
                flashErro($e->getMessage());
            }
        }

        // Fase 51: logo oficial do evento. Enviada aqui, ela vale na pagina
        // publica daquele evento mesmo que quem visita tenha outro tema de
        // cor escolhido - identidade visual aprovada nao pode variar.
        if (!empty($_POST['remover_logo']) && $logoPath !== null) {
            $this->imagens->remover($logoPath);
            $logoPath = null;
        }

        if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            try {
                $novoCaminho = $this->imagens->salvar($_FILES['logo'], 'evento-logo', 480, 200);

                if ($logoPath !== null) {
                    $this->imagens->remover($logoPath);
                }

                $logoPath = $novoCaminho;
            } catch (\RuntimeException $e) {
                flashErro($e->getMessage());
            }
        }

        $cabecalhoTituloHtml = isset($_POST['cabecalho_titulo_html']) ? sanitizarHtmlRico($_POST['cabecalho_titulo_html']) : '';

        $efeitoTransicao = isset($_POST['cabecalho_efeito_transicao']) ? $_POST['cabecalho_efeito_transicao'] : 'onda';
        $efeitoTransicao = in_array($efeitoTransicao, EventoConfiguracaoVisualRepository::CABECALHO_EFEITOS_TRANSICAO, true) ? $efeitoTransicao : 'onda';

        $overlayOpacidade = (int) (isset($_POST['cabecalho_overlay_opacidade']) ? $_POST['cabecalho_overlay_opacidade'] : 50);
        $overlayOpacidade = $overlayOpacidade >= 0 && $overlayOpacidade <= 100 ? $overlayOpacidade : 50;

        $imagemPosicao = isset($_POST['cabecalho_imagem_posicao']) ? $_POST['cabecalho_imagem_posicao'] : 'superior_centro';
        $imagemPosicao = in_array($imagemPosicao, EventoConfiguracaoVisualRepository::CABECALHO_IMAGEM_POSICOES, true) ? $imagemPosicao : 'superior_centro';

        $efeitoEntrada = isset($_POST['cabecalho_efeito_entrada']) ? $_POST['cabecalho_efeito_entrada'] : 'nenhum';
        $efeitoEntrada = in_array($efeitoEntrada, EventoConfiguracaoVisualRepository::CABECALHO_EFEITOS_ENTRADA, true) ? $efeitoEntrada : 'nenhum';

        $this->configuracoes->salvar($eventoId, [
            'publicado' => isset($_POST['publicado']) ? 1 : 0,
            'cabecalho_imagem_path' => $cabecalhoImagemPath,
            'cabecalho_logo_claro_path' => $cabecalhoLogoClaroPath,
            'cabecalho_titulo_html' => $cabecalhoTituloHtml,
            'cabecalho_efeito_transicao' => $efeitoTransicao,
            'cabecalho_overlay_opacidade' => $overlayOpacidade,
            'cabecalho_imagem_posicao' => $imagemPosicao,
            'cabecalho_efeito_entrada' => $efeitoEntrada,
            'logo_path' => $logoPath,
            'logo_alt' => isset($_POST['logo_alt']) && trim($_POST['logo_alt']) !== '' ? trim($_POST['logo_alt']) : null,
            'quadros_avanco_automatico' => isset($_POST['quadros_avanco_automatico']) ? 1 : 0,
        ]);
    }
}
