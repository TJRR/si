<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Services\ImagemService;

/**
 * Endpoint unico de upload de imagem inline, reaproveitado por TODO campo de
 * texto rico do sistema (Fase 18: slides, banners, blocos de conteudo,
 * premiacao...) - assets/js/editor-rico.js chama esta rota via fetch quando o
 * admin insere uma imagem no meio do texto. Sem isso cada tela precisaria
 * reimplementar upload+validacao so' para o botao "imagem" do editor.
 */
class EditorMidiaAdminController extends Controller
{
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->imagens = new ImagemService();
    }

    public function uploadImagem()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (empty($_FILES['imagem']) || $_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['erro' => 'Nenhuma imagem enviada ou falha no upload.']);
            return;
        }

        try {
            $caminhoRelativo = $this->imagens->salvar($_FILES['imagem'], 'editor', 1600, 1600);
        } catch (\RuntimeException $e) {
            http_response_code(422);
            echo json_encode(['erro' => $e->getMessage()]);
            return;
        }

        echo json_encode(['url' => config('base_path') . '/assets/' . $caminhoRelativo]);
    }
}
