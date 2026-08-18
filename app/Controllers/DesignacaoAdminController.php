<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\AvaliadorCategoriaRepository;
use App\Repositories\AvaliadorDesignacaoRepository;
use App\Repositories\EtapaCategoriaAvaliadorRepository;
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
    private $etapaCategorias;
    private $avaliadorCategorias;

    public function __construct()
    {
        // Fase 28: leitura (index, progresso) liberada pra Suporte tambem -
        // acoes que alteram designacao (atribuir/remover/distribuir) travam
        // individualmente com RoleMiddleware::exigir(['administrador'])
        // dentro do proprio metodo, mesmo padrao de EtapaAdminController.
        //
        // Fase 29 (ajuste pos-push): exigirEmQualquerConcurso() aqui (nao
        // exigir()) + exigir() com o concurso resolvido dentro de cada
        // acao - exigir() sem concurso so' reconhece vinculo GLOBAL. Nos
        // metodos que recebem submissao_id/usuario_id/etapa_id do POST, o
        // concurso usado na checagem vem SEMPRE do registro buscado no
        // banco (nunca do etapa_id cru do POST, que so' serve pro
        // redirect) - senao um administrador/suporte escopado a outro
        // concurso poderia forjar o etapa_id certo pra passar na checagem
        // mesmo agindo sobre submissao de fora do escopo dele.
        RoleMiddleware::exigirEmQualquerConcurso(['administrador', 'suporte']);
        $this->designacoes = new AvaliadorDesignacaoRepository();
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->perfis = new PerfilRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->notas = new NotaLancadaRepository();
        $this->servico = new AvaliadorDesignacaoService();
        $this->etapaCategorias = new EtapaCategoriaAvaliadorRepository();
        $this->avaliadorCategorias = new AvaliadorCategoriaRepository();
    }

    public function index($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);
        RoleMiddleware::exigir(['administrador', 'suporte'], $trilha['concurso_id']);

        $avaliadores = $this->perfis->listarUsuariosPorPerfilConcurso('avaliador', $trilha['concurso_id']);

        $filtroAvaliador = isset($_GET['filtro_avaliador']) ? $_GET['filtro_avaliador'] : '';
        $filtroNota = isset($_GET['filtro_nota']) ? $_GET['filtro_nota'] : '';

        $submissoesComDesignacoes = [];
        foreach ($this->submissoes->listarPorEtapa($etapaId) as $submissao) {
            $submissao['designacoes'] = $this->designacoes->listarPorSubmissao($submissao['id']);
            $submissao['tem_nota_lancada'] = count($this->notas->listarPorSubmissao($submissao['id'])) > 0;

            // Fase 17 (Bug 3): trava por designacao - sorteio aceito nunca
            // remove; manual so' trava se o proprio avaliador ja notou.
            foreach ($submissao['designacoes'] as &$designacao) {
                $designacao['travada'] = $designacao['origem'] === 'sorteio'
                    || $this->notas->contarNotasPorUsuario($submissao['id'], $designacao['usuario_id']) > 0;
            }
            unset($designacao);

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

        // Fase 29 (ajuste pos-push): $etapaId do POST antes so' servia pro
        // redirect, nunca era conferido contra a submissao de verdade -
        // agora tambem e' a base da checagem de concurso, entao precisa
        // bater com a etapa real da submissao.
        $submissao = $this->submissoes->buscarPorId($submissaoId);

        if ($submissao === null || (int) $submissao['etapa_id'] !== $etapaId) {
            http_response_code(404);
            exit('Submissão não encontrada nesta etapa.');
        }

        $etapa = $this->etapas->buscarPorId($etapaId);
        $trilha = $etapa !== null ? $this->trilhas->buscarPorId($etapa['trilha_id']) : null;
        RoleMiddleware::exigir(['administrador'], $trilha !== null ? $trilha['concurso_id'] : null);

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

        $etapa = $this->etapas->buscarPorId($etapaId);
        $trilha = $etapa !== null ? $this->trilhas->buscarPorId($etapa['trilha_id']) : null;
        RoleMiddleware::exigir(['administrador'], $trilha !== null ? $trilha['concurso_id'] : null);

        if ($usuarioId > 0) {
            $totalAtribuido = 0;

            foreach ($submissaoIds as $submissaoId) {
                $submissaoId = (int) $submissaoId;
                $submissao = $this->submissoes->buscarPorId($submissaoId);

                // Fase 29 (ajuste pos-push): so' atribui submissao que
                // realmente pertence a esta etapa - submissao_ids vem do
                // POST, nao confiavel por si so'.
                if ($submissao === null || (int) $submissao['etapa_id'] !== $etapaId) {
                    continue;
                }

                if (!$this->designacoes->existeDesignacao($submissaoId, $usuarioId)) {
                    $this->designacoes->criar($submissaoId, $usuarioId, Auth::usuarioId());
                }

                $totalAtribuido++;
            }

            $_SESSION['flash'] = $totalAtribuido . ' submissão(ões) atribuída(s) ao avaliador selecionado.';
        }

        $this->redirecionar('designacoes/index/' . $etapaId);
    }

    /**
     * Fase 17 (Bug 3): duas travas antes de remover uma designacao -
     * (a) designacoes vindas de sorteio aceito nunca podem ser removidas
     * (preserva a lisura do processo); (b) designacoes cujo avaliador ja
     * lancou nota tambem nao, mesmo se manuais.
     */
    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $etapaId = (int) (isset($_POST['etapa_id']) ? $_POST['etapa_id'] : 0);

        $designacao = $this->designacoes->buscarPorId($id);

        if ($designacao === null) {
            flashErro('Designação não encontrada.');
            $this->redirecionar('designacoes/index/' . $etapaId);
            return;
        }

        // Fase 29 (ajuste pos-push): concurso resolvido a partir da
        // designacao de verdade (submissao -> etapa -> trilha), nao do
        // etapa_id cru do POST.
        $submissaoDaDesignacao = $this->submissoes->buscarPorId($designacao['submissao_id']);
        $etapaDaDesignacao = $submissaoDaDesignacao !== null ? $this->etapas->buscarPorId($submissaoDaDesignacao['etapa_id']) : null;
        $trilhaDaDesignacao = $etapaDaDesignacao !== null ? $this->trilhas->buscarPorId($etapaDaDesignacao['trilha_id']) : null;
        RoleMiddleware::exigir(['administrador'], $trilhaDaDesignacao !== null ? $trilhaDaDesignacao['concurso_id'] : null);

        if ($designacao['origem'] === 'sorteio') {
            flashErro('Designações de sorteio não podem ser removidas — a distribuição já foi aceita.');
            $this->redirecionar('designacoes/index/' . $etapaId);
            return;
        }

        if ($this->notas->contarNotasPorUsuario($designacao['submissao_id'], $designacao['usuario_id']) > 0) {
            flashErro('Este avaliador já lançou nota para esta submissão — não é possível remover a designação.');
            $this->redirecionar('designacoes/index/' . $etapaId);
            return;
        }

        $this->designacoes->remover($id);
        $this->redirecionar('designacoes/index/' . $etapaId);
    }

    /**
     * Fase 24: quebra por avaliador do quadro "Progresso da avaliação" do
     * painel do Admin - quem já terminou tudo e quem ainda tem submissões
     * pendentes, com o nome da equipe de cada pendência.
     */
    public function progresso($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);
        RoleMiddleware::exigir(['administrador', 'suporte'], $trilha['concurso_id']);

        $this->renderizar('admin/designacoes/progresso', [
            'etapa' => $etapa,
            'trilha' => $trilha,
            'progresso' => $this->servico->progressoPorAvaliador($etapaId),
        ], 'Progresso da avaliação — ' . $etapa['nome'], ['tipo' => 'designacoes', 'id' => (int) $etapaId]);
    }

    public function distribuir($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);
        RoleMiddleware::exigir(['administrador'], $trilha['concurso_id']);

        if ($etapa['modo_designacao'] === 'sorteio_categoria') {
            $vagas = $this->etapaCategorias->listarPorEtapa($etapaId);

            if (empty($vagas)) {
                flashErro('Configure as vagas por categoria desta etapa antes de sortear.');
                $this->redirecionar('designacoes/index/' . $etapaId);
                return;
            }

            $categorias = [];
            foreach ($vagas as $vaga) {
                $categoriaId = (int) $vaga['categoria_avaliador_id'];
                $categorias[] = [
                    'categoria_nome' => $vaga['categoria_nome'],
                    'avaliadores' => $this->avaliadorCategorias->listarUsuariosPorCategoria($categoriaId, $trilha['concurso_id']),
                ];
            }

            $this->renderizar('admin/designacoes/selecionar_avaliadores', [
                'etapa' => $etapa,
                'trilha' => $trilha,
                'categorias' => $categorias,
            ], 'Selecionar avaliadores do sorteio — ' . $etapa['nome'], ['tipo' => 'designacoes', 'id' => (int) $etapaId]);
            return;
        }

        try {
            $linhas = $this->servico->calcularDistribuicao($etapaId);
        } catch (\RuntimeException $e) {
            flashErro($e->getMessage());
            $this->redirecionar('designacoes/index/' . $etapaId);
            return;
        }

        if (empty($linhas)) {
            flashAlerta('Todas as submissões já têm a quantidade de avaliadores configurada.');
            $this->redirecionar('designacoes/index/' . $etapaId);
            return;
        }

        $this->renderizar('admin/designacoes/distribuir_previa', [
            'etapa' => $etapa,
            'trilha' => $trilha,
            'linhas' => $linhas,
        ], 'Prévia da distribuição — ' . $etapa['nome'], ['tipo' => 'designacoes', 'id' => (int) $etapaId]);
    }

    public function distribuirComSelecao()
    {
        $etapaId = (int) (isset($_POST['etapa_id']) ? $_POST['etapa_id'] : 0);
        $avaliadorIds = isset($_POST['avaliador_id']) && is_array($_POST['avaliador_id'])
            ? array_map('intval', $_POST['avaliador_id'])
            : [];

        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);
        RoleMiddleware::exigir(['administrador'], $trilha['concurso_id']);

        try {
            $linhas = $this->servico->calcularDistribuicaoPorCategoria($etapaId, $avaliadorIds);
        } catch (\RuntimeException $e) {
            flashErro($e->getMessage());
            $this->redirecionar('designacoes/index/' . $etapaId);
            return;
        }

        if (empty($linhas)) {
            flashAlerta('Todas as submissões já têm a quantidade de avaliadores configurada.');
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

        $etapa = $this->etapas->buscarPorId($etapaId);
        $trilha = $etapa !== null ? $this->trilhas->buscarPorId($etapa['trilha_id']) : null;
        RoleMiddleware::exigir(['administrador'], $trilha !== null ? $trilha['concurso_id'] : null);

        $atribuicoes = [];
        foreach ($submissaoIds as $indice => $submissaoId) {
            if (isset($usuarioIds[$indice])) {
                $atribuicoes[] = ['submissao_id' => $submissaoId, 'usuario_id' => $usuarioIds[$indice]];
            }
        }

        // Fase 17 (Bug 3): so' a distribuicao por sorteio de categoria fica
        // travada contra remocao - a "automatica balanceada" nao foi pedida
        // pelo usuario pra travar.
        $origem = ($etapa !== null && $etapa['modo_designacao'] === 'sorteio_categoria') ? 'sorteio' : 'manual';

        $total = $this->servico->confirmarDistribuicao($etapaId, $atribuicoes, Auth::usuarioId(), $origem);
        $_SESSION['flash'] = $total . ' designação(ões) criada(s).';
        $this->redirecionar('designacoes/index/' . $etapaId);
    }
}
