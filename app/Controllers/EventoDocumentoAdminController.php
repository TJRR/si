<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EventoDocumentoRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Services\ArquivoService;

/**
 * Reabertura da Fase 51: sub-aba "Documentos" da ficha do Evento, copia de
 * DocumentoAdminController (Concurso) com toda acao escopada por evento_id
 * e sem trilha. Mesmo versionamento, mesma publicacao, mesmo historico.
 */
class EventoDocumentoAdminController extends Controller
{
    private $eventos;
    private $documentos;
    private $arquivos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->documentos = new EventoDocumentoRepository();
        $this->arquivos = new ArquivoService();
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

    private function documentoDoEventoOu404($eventoId, $id)
    {
        $documento = $this->documentos->buscarPorId($id);

        if ($documento === null || (int) $documento['evento_id'] !== (int) $eventoId) {
            http_response_code(404);
            exit('Documento não encontrado.');
        }

        return $documento;
    }

    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);

        $this->renderizar('admin/evento_documentos/index', [
            'evento' => $evento,
            'documentos' => $this->documentos->listarAtivos($eventoId),
        ], 'Documentos: ' . $evento['nome'], ['tipo' => 'eventoDocumentos', 'id' => (int) $eventoId]);
    }

    public function novo($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarNovo($eventoId);

            if ($erro === null) {
                flashSucesso('Documento enviado.');
                $this->redirecionar('eventoDocumentos/index/' . $eventoId);
                return;
            }
        }

        $this->renderizar('admin/evento_documentos/form', [
            'erro' => $erro,
            'evento' => $evento,
        ], 'Novo documento: ' . $evento['nome'], ['tipo' => 'eventoDocumentos', 'id' => (int) $eventoId]);
    }

    public function editar($eventoId, $id = null)
    {
        $evento = $this->eventoOu404($eventoId);
        $documento = $this->documentoDoEventoOu404($eventoId, (int) $id);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
            $titulo = trim(isset($_POST['titulo']) ? $_POST['titulo'] : '');

            if (!in_array($tipo, EventoDocumentoRepository::TIPOS, true)) {
                $erro = 'Selecione um tipo de documento válido.';
            } elseif ($titulo === '') {
                $erro = 'Informe o título do documento.';
            } else {
                $this->documentos->atualizarMetadados($eventoId, (int) $id, $tipo, $titulo);
                flashSucesso('Documento atualizado.');
                $this->redirecionar('eventoDocumentos/index/' . $eventoId);
                return;
            }
        }

        $this->renderizar('admin/evento_documentos/editar', [
            'erro' => $erro,
            'evento' => $evento,
            'documento' => $documento,
        ], 'Editar documento: ' . $evento['nome'], ['tipo' => 'eventoDocumentos', 'id' => (int) $eventoId]);
    }

    public function historico($eventoId, $grupoDocumento = null)
    {
        $evento = $this->eventoOu404($eventoId);

        $this->renderizar('admin/evento_documentos/historico', [
            'evento' => $evento,
            'versoes' => $this->documentos->listarVersoesPorGrupo($eventoId, (string) $grupoDocumento),
        ], 'Histórico de versões: ' . $evento['nome'], ['tipo' => 'eventoDocumentos', 'id' => (int) $eventoId]);
    }

    public function despublicar($eventoId)
    {
        $this->eventoOu404($eventoId);
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $this->documentoDoEventoOu404($eventoId, $id);

        $this->documentos->alterarPublicacao($eventoId, $id, false);

        flashSucesso('Documento despublicado: não aparece mais na página do evento, mas continua salvo aqui.');
        $this->redirecionar('eventoDocumentos/index/' . $eventoId);
    }

    public function republicar($eventoId)
    {
        $this->eventoOu404($eventoId);
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $this->documentoDoEventoOu404($eventoId, $id);

        $this->documentos->alterarPublicacao($eventoId, $id, true);

        flashSucesso('Documento republicado.');
        $this->redirecionar('eventoDocumentos/index/' . $eventoId);
    }

    public function reordenar($eventoId)
    {
        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->documentos->reordenar($eventoId, $ids);

        echo json_encode(['ok' => true]);
    }

    public function removerGrupo($eventoId)
    {
        $this->eventoOu404($eventoId);
        $grupo = isset($_POST['grupo_documento']) ? (string) $_POST['grupo_documento'] : '';

        $versoes = $this->documentos->removerGrupo($eventoId, $grupo);

        foreach ($versoes as $versao) {
            $this->arquivos->remover($versao['arquivo_path']);
        }

        flashSucesso('Documento removido (todas as versões).');
        $this->redirecionar('eventoDocumentos/index/' . $eventoId);
    }

    private function salvarNovo($eventoId)
    {
        $tipo = isset($_POST['tipo']) ? $_POST['tipo'] : '';
        $titulo = trim(isset($_POST['titulo']) ? $_POST['titulo'] : '');

        if (!in_array($tipo, EventoDocumentoRepository::TIPOS, true)) {
            return 'Selecione um tipo de documento válido.';
        }

        if ($titulo === '') {
            return 'Informe o título do documento.';
        }

        if (empty($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
            return 'Envie o arquivo do documento.';
        }

        try {
            $caminho = $this->arquivos->salvar($_FILES['arquivo'], 'evento-documentos', true);
        } catch (\RuntimeException $e) {
            return $e->getMessage();
        }

        $this->documentos->criar($eventoId, [
            'tipo' => $tipo,
            'titulo' => $titulo,
            'arquivo_path' => $caminho,
            'criado_por' => Auth::usuarioId(),
        ]);

        return null;
    }
}
