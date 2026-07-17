<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\ConfiguracaoVisualRepository;
use App\Services\ImagemService;

class TemaAdminController extends Controller
{
    private $configuracaoVisual;
    private $imagens;
    private $concursos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->configuracaoVisual = new ConfiguracaoVisualRepository();
        $this->imagens = new ImagemService();
        $this->concursos = new ConcursoRepository();
    }

    /**
     * Sem $concursoId: edita a identidade visual GLOBAL/default (aba
     * "Tema" de nivel 1, usada quando nao ha concurso no contexto - login,
     * paineis). Com $concursoId (Fase 18, acessado via arvore de um
     * concurso especifico): edita o override daquela edicao.
     */
    public function index($concursoId = null)
    {
        $concurso = $concursoId !== null ? $this->concursos->buscarPorId($concursoId) : null;

        if ($concursoId !== null && $concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->salvar($concursoId);

            if (empty($_SESSION['flash'])) {
                $_SESSION['flash'] = 'Tema atualizado.';
            }

            $this->redirecionar($concursoId !== null ? 'tema/index/' . $concursoId : 'tema/index');
            return;
        }

        $atual = $concursoId !== null
            ? $this->configuracaoVisual->buscarPorConcurso($concursoId)
            : $this->configuracaoVisual->buscar();

        // Nao participa da arvore (nao entra em $modulosArvore): a rota
        // 'tema' ja e' usada pela tela GLOBAL (fora da arvore, layout flat);
        // colocar 'tema' em $modulosArvore quebraria essa tela global,
        // envolvendo-a com a sidebar sem contexto. Por isso este screen
        // concurso-scoped e' so' um link direto a partir de Concursos, nao
        // um no na arvore.
        $this->renderizar('admin/tema/form', [
            'configuracaoVisual' => $atual,
            'concurso' => $concurso,
        ], 'Tema');
    }

    private function salvar($concursoId)
    {
        if ($concursoId !== null) {
            $this->salvarParaConcurso($concursoId);
            return;
        }

        if (!empty($_POST['cor_primaria_inicio']) && !empty($_POST['cor_primaria_fim']) && !empty($_POST['cor_secundaria'])) {
            $this->configuracaoVisual->atualizar(
                trim($_POST['cor_primaria_inicio']),
                trim($_POST['cor_primaria_fim']),
                trim($_POST['cor_secundaria'])
            );
        }

        $this->salvarFavicon();
        $this->salvarLogoGlobal();
    }

    private function salvarLogoGlobal()
    {
        if (empty($_FILES['logo']) || $_FILES['logo']['error'] === UPLOAD_ERR_NO_FILE) {
            return;
        }

        if ($_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['flash'] = 'Falha ao enviar o logo.';
            return;
        }

        try {
            $novoCaminho = $this->imagens->salvar($_FILES['logo'], 'logo', 600, 200);
        } catch (\RuntimeException $e) {
            $_SESSION['flash'] = $e->getMessage();
            return;
        }

        $atual = $this->configuracaoVisual->buscar();

        if ($atual !== false && !empty($atual['logo_path'])) {
            $this->imagens->remover($atual['logo_path']);
        }

        $this->configuracaoVisual->atualizarLogo($novoCaminho);
    }

    private function salvarParaConcurso($concursoId)
    {
        $atual = $this->configuracaoVisual->buscarPorConcurso($concursoId);
        $logoPath = $atual !== null ? $atual['logo_path'] : null;

        if (!empty($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            try {
                $novoCaminho = $this->imagens->salvar($_FILES['logo'], 'logo', 600, 200);

                if ($logoPath !== null) {
                    $this->imagens->remover($logoPath);
                }

                $logoPath = $novoCaminho;
            } catch (\RuntimeException $e) {
                $_SESSION['flash'] = $e->getMessage();
            }
        }

        $this->configuracaoVisual->salvarParaConcurso(
            $concursoId,
            trim(isset($_POST['cor_primaria_inicio']) ? $_POST['cor_primaria_inicio'] : ($atual['cor_primaria_inicio'] ?? '#FF6600')),
            trim(isset($_POST['cor_primaria_fim']) ? $_POST['cor_primaria_fim'] : ($atual['cor_primaria_fim'] ?? '#FF9955')),
            trim(isset($_POST['cor_secundaria']) ? $_POST['cor_secundaria'] : ($atual['cor_secundaria'] ?? '#191919')),
            $logoPath
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
