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
use App\Repositories\TemaVisualRepository;
use App\Services\ImagemService;

/**
 * Fase 18 (4.9): identidade visual do site. Fase 19 (#84 v2): deixou de
 * ter override por concurso - "Tema" (Favicon+Temas), "Cabeçalho" (Logo +
 * imagem de fundo + logo clara) e "Rodapé" (logo do rodapé + atalhos de
 * navegação) são 3 abas da tela "Configuração", todas 100% globais.
 *
 * Fase 48B: index() deixou de editar um unico conjunto de cor e virou a
 * listagem de multiplos temas (TemaVisualRepository); favicon continua sendo
 * salvo pelo mesmo POST desta tela, sem mudanca de comportamento.
 */
class TemaAdminController extends Controller
{
    private $configuracaoVisual;
    private $temas;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->configuracaoVisual = new ConfiguracaoVisualRepository();
        $this->temas = new TemaVisualRepository();
        $this->imagens = new ImagemService();
    }

    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->salvarFavicon();

            if (empty($_SESSION['flash'])) {
                $_SESSION['flash'] = 'Favicon atualizado.';
            }

            $this->redirecionar('tema/index');
            return;
        }

        $temas = $this->temas->listarTodos();
        $contagens = [];
        foreach ($temas as $tema) {
            $contagens[$tema['id']] = $this->temas->contarUsuarios($tema['id']);
        }

        $this->renderizar('admin/tema/index', [
            'configuracaoVisual' => $this->configuracaoVisual->buscar(),
            'temas' => $temas,
            'contagens' => $contagens,
        ], 'Tema', ['tipo' => 'configuracaoTema', 'id' => null]);
    }

    public function novo()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->salvarTema(null);
            return;
        }

        $this->renderizar('admin/tema/form', [
            'tema' => null,
        ], 'Novo tema', ['tipo' => 'configuracaoTema', 'id' => null]);
    }

    public function editar($id)
    {
        $tema = $this->temas->buscarPorId($id);

        if ($tema === null) {
            http_response_code(404);
            exit('Tema não encontrado.');
        }

        if ((int) $tema['editavel'] === 0) {
            flashErro('Este é um dos temas de sistema (não customizado) e não pode ser editado.');
            $this->redirecionar('tema/index');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->salvarTema($id);
            return;
        }

        $this->renderizar('admin/tema/form', [
            'tema' => $tema,
        ], 'Editar tema', ['tipo' => 'configuracaoTema', 'id' => null]);
    }

    private function salvarTema($id)
    {
        $rotaVolta = $id === null ? 'tema/novo' : 'tema/editar/' . $id;
        $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');

        if ($nome === '') {
            flashErro('Informe um nome para o tema.');
            $this->redirecionar($rotaVolta);
            return;
        }

        $corPrimariaInicio = trim(isset($_POST['cor_primaria_inicio']) ? $_POST['cor_primaria_inicio'] : '');
        $corSecundaria = trim(isset($_POST['cor_secundaria']) ? $_POST['cor_secundaria'] : '');
        $corTerciaria = trim(isset($_POST['cor_terciaria']) ? $_POST['cor_terciaria'] : '');
        $corDestaqueApp = trim(isset($_POST['cor_destaque_app']) ? $_POST['cor_destaque_app'] : '');

        $temaAtual = $id !== null ? $this->temas->buscarPorId($id) : null;
        $logoConcursoPath = $this->processarLogo('logo_concurso', $temaAtual !== null ? $temaAtual['logo_concurso_path'] : null);
        $logoEventoPath = $this->processarLogo('logo_evento', $temaAtual !== null ? $temaAtual['logo_evento_path'] : null);

        try {
            if ($id === null) {
                $this->temas->criar($nome, $corPrimariaInicio, $corSecundaria, $corTerciaria, $corDestaqueApp, $logoConcursoPath, $logoEventoPath);
                flashSucesso('Tema criado.');
            } else {
                $this->temas->atualizar($id, $nome, $corPrimariaInicio, $corSecundaria, $corTerciaria, $corDestaqueApp, $logoConcursoPath, $logoEventoPath);
                flashSucesso('Tema atualizado.');
            }
            $this->redirecionar('tema/index');
        } catch (\InvalidArgumentException $e) {
            flashErro('Todas as 4 cores precisam estar no formato #RRGGBB.');
            $this->redirecionar($rotaVolta);
        } catch (\RuntimeException $e) {
            flashErro($e->getMessage());
            $this->redirecionar($rotaVolta);
        }
    }

    /**
     * Fase 48B: upload opcional de uma das 2 logos do tema. Sem arquivo
     * novo, mantem o path atual. Com arquivo novo, so' remove o arquivo
     * fisico anterior se ele for um upload gerenciado (uploads/conteudo/...)
     * - os 5 temas de sistema apontam para assets fixos (img/logo-*.png),
     * que nunca podem ser apagados.
     */
    private function processarLogo($chaveArquivo, $pathAtual)
    {
        if (empty($_FILES[$chaveArquivo]) || $_FILES[$chaveArquivo]['error'] === UPLOAD_ERR_NO_FILE) {
            return $pathAtual;
        }

        if ($_FILES[$chaveArquivo]['error'] !== UPLOAD_ERR_OK) {
            flashErro('Falha ao enviar uma das logos.');
            return $pathAtual;
        }

        try {
            $novoCaminho = $this->imagens->salvar($_FILES[$chaveArquivo], 'logo', 600, 200);
        } catch (\RuntimeException $e) {
            flashErro($e->getMessage());
            return $pathAtual;
        }

        if ($pathAtual !== null && strpos($pathAtual, 'uploads/') === 0) {
            $this->imagens->remover($pathAtual);
        }

        return $novoCaminho;
    }

    public function duplicar($id)
    {
        $tema = $this->temas->buscarPorId($id);

        if ($tema === null) {
            http_response_code(404);
            exit('Tema não encontrado.');
        }

        $novoId = $this->temas->duplicar($id, $tema['nome'] . ' (cópia)');

        flashSucesso('Tema duplicado. Ajuste as cores e publique quando estiver pronto.');
        $this->redirecionar('tema/editar/' . $novoId);
    }

    public function publicar($id)
    {
        try {
            $this->temas->publicar($id);
            flashSucesso('Tema publicado.');
        } catch (\RuntimeException $e) {
            flashErro($e->getMessage());
        }
        $this->redirecionar('tema/index');
    }

    public function despublicar($id)
    {
        $tema = $this->temas->buscarPorId($id);

        if ($tema !== null && (int) $tema['padrao'] === 1) {
            flashErro('Este tema é o padrão do sistema; defina outro como padrão antes de despublicar.');
            $this->redirecionar('tema/index');
            return;
        }

        try {
            $this->temas->despublicar($id);
            flashSucesso('Tema despublicado.');
        } catch (\RuntimeException $e) {
            flashErro($e->getMessage());
        }
        $this->redirecionar('tema/index');
    }

    public function padrao($id)
    {
        try {
            $this->temas->definirPadrao($id);
            flashSucesso('Tema definido como padrão do sistema.');
        } catch (\RuntimeException $e) {
            flashErro($e->getMessage());
        }
        $this->redirecionar('tema/index');
    }

    public function remover($id)
    {
        $tema = $this->temas->buscarPorId($id);

        try {
            $this->temas->remover($id);
            if ($tema !== null) {
                foreach ([$tema['logo_concurso_path'], $tema['logo_evento_path']] as $path) {
                    if ($path !== null && strpos($path, 'uploads/') === 0) {
                        $this->imagens->remover($path);
                    }
                }
            }
            flashSucesso('Tema removido.');
        } catch (\RuntimeException $e) {
            flashErro($e->getMessage());
        }
        $this->redirecionar('tema/index');
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
        $cabecalhoImagemPath = $atual !== false ? $atual['cabecalho_imagem_path'] : null;
        $cabecalhoLogoClaroPath = $atual !== false ? $atual['cabecalho_logo_claro_path'] : null;

        // Fase 48B (correcao pos-teste de fumaca): remocao explicita, sem
        // depender de enviar um arquivo novo para "trocar" - antes nao
        // havia jeito de simplesmente limpar um desses 2 campos opcionais.
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
                $novoCaminho = $this->imagens->salvar($_FILES['cabecalho_imagem'], 'cabecalho', 1920, 800);

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
                $novoCaminho = $this->imagens->salvar($_FILES['logo_claro'], 'logo', 320, 120);

                if ($cabecalhoLogoClaroPath !== null) {
                    $this->imagens->remover($cabecalhoLogoClaroPath);
                }

                $cabecalhoLogoClaroPath = $novoCaminho;
            } catch (\RuntimeException $e) {
                flashErro($e->getMessage());
            }
        }

        $cabecalhoTituloHtml = isset($_POST['cabecalho_titulo_html']) ? sanitizarHtmlRico($_POST['cabecalho_titulo_html']) : '';

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
                flashErro($e->getMessage());
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
            flashErro('Falha ao enviar o favicon.');
            return;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($_FILES['favicon']['tmp_name']);

        if ($mime !== 'image/png') {
            flashErro('Favicon precisa ser um arquivo PNG.');
            return;
        }

        try {
            $novoCaminho = $this->imagens->salvar($_FILES['favicon'], 'favicon', 512, 512, false);
        } catch (\RuntimeException $e) {
            flashErro($e->getMessage());
            return;
        }

        $atual = $this->configuracaoVisual->buscar();

        if ($atual !== false && !empty($atual['favicon_path'])) {
            $this->imagens->remover($atual['favicon_path']);
        }

        $this->configuracaoVisual->atualizarFavicon($novoCaminho);
    }
}
