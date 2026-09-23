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
use App\Repositories\MidiaPastaRepository;
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
    private $pastas;
    private $concursos;
    private $imagens;
    private $arquivos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->midias = new MidiaRepository();
        $this->pastas = new MidiaPastaRepository();
        $this->concursos = new ConcursoRepository();
        $this->imagens = new ImagemService();
        $this->arquivos = new ArquivoService();
    }

    public function index()
    {
        $tipo = !empty($_GET['tipo']) ? $_GET['tipo'] : null;
        $pastaId = !empty($_GET['pasta']) ? (int) $_GET['pasta'] : null;
        $pastaAtual = $pastaId !== null ? $this->pastas->buscarPorId($pastaId) : null;

        if ($pastaId !== null && $pastaAtual === null) {
            $pastaId = null;
        }

        $this->renderizar('admin/midia/index', [
            'midias' => $this->midias->listar($tipo, $pastaId),
            'tipoFiltro' => $tipo,
            'pastaAtual' => $pastaAtual,
            'subpastas' => $this->pastas->listarFilhas($pastaId),
            'caminho' => $pastaId !== null ? $this->pastas->caminho($pastaId) : [],
            'todasAsPastas' => $this->pastas->listarTodas(),
        ], 'Biblioteca de mídia', ['tipo' => 'configuracaoMidia', 'id' => null]);
    }

    /**
     * Fase 51: pastas da biblioteca. Tudo volta para a mesma tela, na pasta
     * onde a pessoa estava, para nao perder o lugar da navegacao.
     */
    public function pastaNova()
    {
        $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
        $paiId = !empty($_POST['pasta_pai_id']) ? (int) $_POST['pasta_pai_id'] : null;

        if ($nome === '') {
            flashErro('Informe o nome da pasta.');
        } else {
            $this->pastas->criar($nome, $paiId, Auth::usuarioId());
            flashSucesso('Pasta criada.');
        }

        $this->redirecionar('midia/index' . ($paiId !== null ? '?pasta=' . $paiId : ''));
    }

    public function pastaRenomear()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
        $pasta = $this->pastas->buscarPorId($id);

        if ($pasta === null || $nome === '') {
            flashErro('Não foi possível renomear a pasta.');
        } else {
            $this->pastas->renomear($id, $nome);
            flashSucesso('Pasta renomeada.');
        }

        $this->redirecionar('midia/index' . ($pasta !== null && $pasta['pasta_pai_id'] !== null ? '?pasta=' . (int) $pasta['pasta_pai_id'] : ''));
    }

    public function pastaRemover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $pasta = $this->pastas->buscarPorId($id);

        if ($pasta === null) {
            $this->redirecionar('midia/index');
            return;
        }

        $conteudo = $this->pastas->contarConteudo($id);

        if ($conteudo['subpastas'] > 0 || $conteudo['midias'] > 0) {
            flashAlerta('Esta pasta ainda tem conteúdo dentro. Mova ou remova o conteúdo antes de apagar a pasta.');
        } else {
            $this->pastas->remover($id);
            flashSucesso('Pasta removida.');
        }

        $this->redirecionar('midia/index' . ($pasta['pasta_pai_id'] !== null ? '?pasta=' . (int) $pasta['pasta_pai_id'] : ''));
    }

    public function mover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $destino = !empty($_POST['pasta_id']) ? (int) $_POST['pasta_id'] : null;
        $origem = !empty($_POST['pasta_atual']) ? (int) $_POST['pasta_atual'] : null;

        if ($this->midias->buscarPorId($id) !== null) {
            $this->midias->moverPara($id, $destino);
            flashSucesso('Mídia movida.');
        }

        $this->redirecionar('midia/index' . ($origem !== null ? '?pasta=' . $origem : ''));
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
            'todasAsPastas' => $this->pastas->listarTodas(),
            'pastaSelecionada' => !empty($_GET['pasta']) ? (int) $_GET['pasta'] : null,
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
            flashErro($e->getCode() === '23000'
                ? 'Não é possível remover: esta mídia está em uso.'
                : 'Não foi possível remover a mídia.');
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
        $pastaId = !empty($_POST['pasta_id']) ? (int) $_POST['pasta_id'] : null;

        $this->midias->criar([
            'concurso_id' => $concursoId,
            'pasta_id' => $pastaId,
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
