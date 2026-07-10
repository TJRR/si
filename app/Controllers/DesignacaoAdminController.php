<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\AvaliadorDesignacaoRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TrilhaRepository;
use App\Services\AvaliadorDesignacaoService;

class DesignacaoAdminController extends Controller
{
    private $designacoes;
    private $etapas;
    private $trilhas;
    private $perfis;
    private $submissoes;
    private $servico;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->designacoes = new AvaliadorDesignacaoRepository();
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->perfis = new PerfilRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->servico = new AvaliadorDesignacaoService();
    }

    public function index($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);
        $avaliadores = $this->perfis->listarUsuariosPorPerfilConcurso('avaliador', $trilha['concurso_id']);

        $submissoesComDesignacoes = [];
        foreach ($this->submissoes->listarPorEtapa($etapaId) as $submissao) {
            $submissao['designacoes'] = $this->designacoes->listarPorSubmissao($submissao['id']);
            $submissoesComDesignacoes[] = $submissao;
        }

        $this->renderizar('admin/designacoes/index', [
            'etapa' => $etapa,
            'trilha' => $trilha,
            'avaliadores' => $avaliadores,
            'submissoes' => $submissoesComDesignacoes,
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Designação de avaliadores — ' . $etapa['nome']);

        unset($_SESSION['flash']);
    }

    public function atribuir()
    {
        $submissaoId = (int) (isset($_POST['submissao_id']) ? $_POST['submissao_id'] : 0);
        $usuarioId = (int) (isset($_POST['usuario_id']) ? $_POST['usuario_id'] : 0);
        $etapaId = (int) (isset($_POST['etapa_id']) ? $_POST['etapa_id'] : 0);

        if (!$this->designacoes->existeDesignacao($submissaoId, $usuarioId)) {
            $this->designacoes->criar($submissaoId, $usuarioId, Auth::usuarioId());
        }

        $this->redirecionar('designacoes/index/' . $etapaId);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $etapaId = (int) (isset($_POST['etapa_id']) ? $_POST['etapa_id'] : 0);

        $this->designacoes->remover($id);
        $this->redirecionar('designacoes/index/' . $etapaId);
    }

    public function distribuir($etapaId)
    {
        try {
            $quantidade = $this->servico->distribuirAutomaticamente($etapaId);
            $_SESSION['flash'] = $quantidade . ' designação(ões) criada(s) automaticamente.';
        } catch (\RuntimeException $e) {
            $_SESSION['flash'] = $e->getMessage();
        }

        $this->redirecionar('designacoes/index/' . $etapaId);
    }
}
