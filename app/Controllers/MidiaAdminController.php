<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\MidiaRepository;
use App\Services\ArquivoService;
use App\Services\ImagemService;

/**
 * Biblioteca de midia GLOBAL (Fase 18, 4.5) - aba de nivel 1 do admin, igual
 * FAQ/Paginas/Tema, pois e' reaproveitavel entre edicoes.
 */
class MidiaAdminController extends Controller
{
    private $midias;
    private $concursos;
    private $imagens;
    private $arquivos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->midias = new MidiaRepository();
        $this->concursos = new ConcursoRepository();
        $this->imagens = new ImagemService();
        $this->arquivos = new ArquivoService();
    }

    public function index()
    {
        $tipo = !empty($_GET['tipo']) ? $_GET['tipo'] : null;

        $this->renderizar('admin/midia/index', [
            'midias' => $this->midias->listar($tipo),
            'tipoFiltro' => $tipo,
        ], 'Biblioteca de mídia', ['tipo' => 'configuracaoMidia', 'id' => null]);
    }

    public function novo()
    {
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarNovo();

            if ($erro === null) {
                $this->redirecionar('midia/index');
                return;
            }
        }

        $this->renderizar('admin/midia/form', [
            'erro' => $erro,
            'concursos' => $this->concursos->listar(),
        ], 'Nova mídia');
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $midia = $this->midias->buscarPorId($id);

        try {
            $this->midias->remover($id);

            if ($midia !== null) {
                if ($midia['tipo'] === 'imagem') {
                    $this->imagens->remover($midia['arquivo_path']);
                } else {
                    $this->arquivos->remover($midia['arquivo_path']);
                }
            }

            $_SESSION['flash'] = 'Mídia removida.';
        } catch (\PDOException $e) {
            $_SESSION['flash'] = $e->getCode() === '23000'
                ? 'Não é possível remover: esta mídia está em uso.'
                : 'Não foi possível remover a mídia.';
        }

        $this->redirecionar('midia/index');
    }

    private function salvarNovo()
    {
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';

        if (!in_array($tipo, ['imagem', 'pdf', 'video'], true)) {
            return 'Selecione o tipo de mídia.';
        }

        if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            return 'Envie o arquivo.';
        }

        $altText = trim(isset($_POST['alt_text']) ? $_POST['alt_text'] : '');

        if ($tipo === 'imagem' && $altText === '') {
            return 'Informe o texto alternativo (alt) da imagem.';
        }

        try {
            $caminho = $tipo === 'imagem'
                ? $this->imagens->salvar($_FILES['arquivo'], 'midia', 1600, 1600)
                : $this->arquivos->salvar($_FILES['arquivo'], 'midia');
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $concursoId = !empty($_POST['concurso_id']) ? (int) $_POST['concurso_id'] : null;

        $this->midias->criar([
            'concurso_id' => $concursoId,
            'arquivo_path' => $caminho,
            'tipo' => $tipo,
            'alt_text' => $tipo === 'imagem' ? $altText : null,
            'titulo' => trim(isset($_POST['titulo']) ? $_POST['titulo'] : '') ?: null,
            'descricao' => trim(isset($_POST['descricao']) ? $_POST['descricao'] : '') ?: null,
            'criado_por' => Auth::usuarioId(),
        ]);

        return null;
    }
}
