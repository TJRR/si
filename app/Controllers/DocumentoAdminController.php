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
use App\Repositories\DocumentoRepository;
use App\Repositories\TrilhaRepository;
use App\Services\ArquivoService;

class DocumentoAdminController extends Controller
{
    private $documentos;
    private $concursos;
    private $trilhas;
    private $arquivos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->documentos = new DocumentoRepository();
        $this->concursos = new ConcursoRepository();
        $this->trilhas = new TrilhaRepository();
        $this->arquivos = new ArquivoService();
    }

    public function index($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $this->renderizar('admin/documentos/index', [
            'concurso' => $concurso,
            'documentos' => $this->documentos->listarAtivosPorConcurso($concursoId),
        ], 'Documentos de ' . $concurso['nome'], ['tipo' => 'documentos', 'id' => (int) $concursoId]);
    }

    public function novo($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarNovo($concursoId);

            if ($erro === null) {
                $this->redirecionar('documentos/index/' . $concursoId);
                return;
            }
        }

        $this->renderizar('admin/documentos/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'trilhas' => $this->trilhas->listarPorConcurso($concursoId),
        ], 'Novo documento', ['tipo' => 'documentos', 'id' => (int) $concursoId]);
    }

    public function historico($concursoId, $grupoDocumento)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $this->renderizar('admin/documentos/historico', [
            'concurso' => $concurso,
            'grupoDocumento' => $grupoDocumento,
            'versoes' => $this->documentos->listarVersoesPorGrupo($concursoId, $grupoDocumento),
        ], 'Histórico de versões', ['tipo' => 'documentos', 'id' => (int) $concursoId]);
    }

    public function removerGrupo()
    {
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);
        $grupo = isset($_POST['grupo_documento']) ? $_POST['grupo_documento'] : '';

        $versoes = $this->documentos->removerGrupo($concursoId, $grupo);

        foreach ($versoes as $versao) {
            $this->arquivos->remover($versao['arquivo_path']);
        }

        $_SESSION['flash'] = 'Documento removido (todas as versões).';
        $this->redirecionar('documentos/index/' . $concursoId);
    }

    private function salvarNovo($concursoId)
    {
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
        $titulo = trim(isset($_POST['titulo']) ? $_POST['titulo'] : '');
        $trilhaId = !empty($_POST['trilha_id']) ? (int) $_POST['trilha_id'] : null;

        if (!in_array($tipo, DocumentoRepository::TIPOS, true)) {
            return 'Selecione um tipo de documento válido.';
        }

        if ($titulo === '') {
            return 'Informe o título do documento.';
        }

        if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            return 'Envie o arquivo PDF do documento.';
        }

        try {
            $caminho = $this->arquivos->salvar($_FILES['arquivo'], 'documentos');
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $this->documentos->criar($concursoId, $trilhaId, $tipo, $titulo, $caminho, Auth::usuarioId());

        return null;
    }
}
