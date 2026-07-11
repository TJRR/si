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
use App\Repositories\NotaLancadaRepository;
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
    private $notas;
    private $servico;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->designacoes = new AvaliadorDesignacaoRepository();
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->perfis = new PerfilRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->notas = new NotaLancadaRepository();
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

        $filtroAvaliador = isset($_GET['filtro_avaliador']) ? $_GET['filtro_avaliador'] : '';
        $filtroNota = isset($_GET['filtro_nota']) ? $_GET['filtro_nota'] : '';

        $submissoesComDesignacoes = [];
        foreach ($this->submissoes->listarPorEtapa($etapaId) as $submissao) {
            $submissao['designacoes'] = $this->designacoes->listarPorSubmissao($submissao['id']);
            $submissao['tem_nota_lancada'] = count($this->notas->listarPorSubmissao($submissao['id'])) > 0;

            if (!$this->passaNosFiltros($submissao, $filtroAvaliador, $filtroNota)) {
                continue;
            }

            $submissoesComDesignacoes[] = $submissao;
        }

        $this->renderizar('admin/designacoes/index', [
            'etapa' => $etapa,
            'trilha' => $trilha,
            'avaliadores' => $avaliadores,
            'submissoes' => $submissoesComDesignacoes,
            'filtroAvaliador' => $filtroAvaliador,
            'filtroNota' => $filtroNota,
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Designação de avaliadores — ' . $etapa['nome'], ['tipo' => 'designacoes', 'id' => (int) $etapaId]);

        unset($_SESSION['flash']);
    }

    private function passaNosFiltros(array $submissao, $filtroAvaliador, $filtroNota)
    {
        if ($filtroAvaliador === 'sem_avaliador' && !empty($submissao['designacoes'])) {
            return false;
        }

        if ($filtroAvaliador !== '' && $filtroAvaliador !== 'sem_avaliador') {
            $designadosIds = array_map('intval', array_column($submissao['designacoes'], 'usuario_id'));

            if (!in_array((int) $filtroAvaliador, $designadosIds, true)) {
                return false;
            }
        }

        if ($filtroNota === 'lancada' && !$submissao['tem_nota_lancada']) {
            return false;
        }

        if ($filtroNota === 'pendente' && $submissao['tem_nota_lancada']) {
            return false;
        }

        return true;
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

    public function atribuirEmMassa()
    {
        $etapaId = (int) (isset($_POST['etapa_id']) ? $_POST['etapa_id'] : 0);
        $usuarioId = (int) (isset($_POST['usuario_id']) ? $_POST['usuario_id'] : 0);
        $submissaoIds = isset($_POST['submissao_ids']) && is_array($_POST['submissao_ids']) ? $_POST['submissao_ids'] : [];

        if ($usuarioId > 0) {
            foreach ($submissaoIds as $submissaoId) {
                $submissaoId = (int) $submissaoId;

                if (!$this->designacoes->existeDesignacao($submissaoId, $usuarioId)) {
                    $this->designacoes->criar($submissaoId, $usuarioId, Auth::usuarioId());
                }
            }

            $_SESSION['flash'] = count($submissaoIds) . ' submissão(ões) atribuída(s) ao avaliador selecionado.';
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
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);

        try {
            $linhas = $this->servico->calcularDistribuicao($etapaId);
        } catch (\RuntimeException $e) {
            $_SESSION['flash'] = $e->getMessage();
            $this->redirecionar('designacoes/index/' . $etapaId);
            return;
        }

        if (empty($linhas)) {
            $_SESSION['flash'] = 'Todas as submissões já têm a quantidade de avaliadores configurada.';
            $this->redirecionar('designacoes/index/' . $etapaId);
            return;
        }

        $this->renderizar('admin/designacoes/distribuir_previa', [
            'etapa' => $etapa,
            'trilha' => $trilha,
            'linhas' => $linhas,
        ], 'Prévia da distribuição — ' . $etapa['nome'], ['tipo' => 'designacoes', 'id' => (int) $etapaId]);
    }

    public function confirmarDistribuicao()
    {
        $etapaId = (int) (isset($_POST['etapa_id']) ? $_POST['etapa_id'] : 0);
        $submissaoIds = isset($_POST['submissao_id']) && is_array($_POST['submissao_id']) ? $_POST['submissao_id'] : [];
        $usuarioIds = isset($_POST['usuario_id']) && is_array($_POST['usuario_id']) ? $_POST['usuario_id'] : [];

        $atribuicoes = [];
        foreach ($submissaoIds as $indice => $submissaoId) {
            if (isset($usuarioIds[$indice])) {
                $atribuicoes[] = ['submissao_id' => $submissaoId, 'usuario_id' => $usuarioIds[$indice]];
            }
        }

        $total = $this->servico->confirmarDistribuicao($etapaId, $atribuicoes, Auth::usuarioId());
        $_SESSION['flash'] = $total . ' designação(ões) criada(s).';
        $this->redirecionar('designacoes/index/' . $etapaId);
    }
}
