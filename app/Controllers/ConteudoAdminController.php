<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConteudoSiteRepository;
use App\Services\ImagemService;

class ConteudoAdminController extends Controller
{
    private $conteudos;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->conteudos = new ConteudoSiteRepository();
        $this->imagens = new ImagemService();
    }

    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->salvarTextos();
            $this->salvarImagens();

            $_SESSION['flash'] = 'Páginas atualizadas.';
            $this->redirecionar('conteudo/index');
            return;
        }

        $this->renderizar('admin/conteudo/form', [
            'conteudos' => $this->conteudos->listar(),
        ], 'Páginas');
    }

    private function salvarTextos()
    {
        if (empty($_POST['conteudo'])) {
            return;
        }

        foreach ($_POST['conteudo'] as $chave => $valor) {
            $this->conteudos->atualizarValor($chave, trim($valor));
        }
    }

    private function salvarImagens()
    {
        if (empty($_FILES['conteudo_imagem'])) {
            return;
        }

        foreach ($_FILES['conteudo_imagem']['error'] as $chave => $erro) {
            if ($erro === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($erro !== UPLOAD_ERR_OK) {
                $_SESSION['flash'] = 'Falha ao enviar a imagem "' . $chave . '".';
                continue;
            }

            $atual = $this->conteudos->buscarPorChave($chave);

            if ($atual === null || $atual['tipo'] !== 'imagem') {
                $_SESSION['flash'] = 'Chave de conteúdo inválida.';
                continue;
            }

            $arquivo = [
                'tmp_name' => $_FILES['conteudo_imagem']['tmp_name'][$chave],
                'size' => $_FILES['conteudo_imagem']['size'][$chave],
            ];

            try {
                $novoCaminho = $this->imagens->salvar($arquivo, $chave, 1200, 1200);
            } catch (\RuntimeException $e) {
                $_SESSION['flash'] = $e->getMessage();
                continue;
            }

            if (!empty($atual['arquivo_path'])) {
                $this->imagens->remover($atual['arquivo_path']);
            }

            $this->conteudos->atualizarImagem($chave, $novoCaminho);
        }
    }
}
