<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConfiguracaoVisualRepository;
use App\Services\ImagemService;

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
        ], 'Tema');
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
