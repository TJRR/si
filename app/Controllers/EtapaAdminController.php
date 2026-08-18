<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EtapaRepository;
use App\Repositories\FormularioDinamicoRepository;
use App\Repositories\TrilhaRepository;

class EtapaAdminController extends Controller
{
    private $etapas;
    private $trilhas;
    private $formularios;

    public function __construct()
    {
        // Fase 29 (ajuste pos-push): exigirEmQualquerConcurso() na entrada +
        // exigir() com o concurso resolvido dentro de cada acao - exigir()
        // sem concurso so' reconhece vinculo GLOBAL.
        RoleMiddleware::exigirEmQualquerConcurso(['administrador', 'suporte']);
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->formularios = new FormularioDinamicoRepository();
    }

    public function index($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha não encontrada.');
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $trilha['concurso_id']);

        $lista = $this->etapas->listarPorTrilha($trilhaId);

        $statusPorFormulario = [];
        foreach ($this->formularios->listar($trilha['concurso_id']) as $formulario) {
            $statusPorFormulario[(int) $formulario['id']] = $formulario['status'];
        }

        foreach ($lista as &$etapa) {
            $etapa['formulario_status'] = $etapa['formulario_dinamico_id'] !== null && isset($statusPorFormulario[(int) $etapa['formulario_dinamico_id']])
                ? $statusPorFormulario[(int) $etapa['formulario_dinamico_id']]
                : null;
        }
        unset($etapa);

        $this->renderizar('admin/etapas/index', [
            'trilha' => $trilha,
            'etapas' => $lista,
        ], 'Etapas de ' . $trilha['nome'], ['tipo' => 'etapas', 'id' => (int) $trilhaId]);
    }

    public function novo($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha não encontrada.');
        }

        RoleMiddleware::exigir(['administrador'], $trilha['concurso_id']);

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->lerDadosFormulario();

            if ($dados['nome'] === '') {
                $erro = 'Informe o nome da etapa.';
            } else {
                $erro = $this->validarPrazoSubmissao($dados);
            }

            if ($erro === null) {
                $this->etapas->criar(
                    $trilhaId,
                    $dados['nome'],
                    $dados['descricao'],
                    $dados['ordem'],
                    $dados['data_inicio'],
                    $dados['data_fim'],
                    $dados['formulario_dinamico_id'],
                    $dados['regra_transicao_tipo'],
                    $dados['regra_transicao_valor'],
                    $dados['config_avaliacao'],
                    $dados['prazo_final_submissao']
                );
                $this->redirecionar('etapas/index/' . $trilhaId);
                return;
            }
        }

        $this->renderizar('admin/etapas/form', [
            'erro' => $erro,
            'trilha' => $trilha,
            'etapa' => null,
            'formularios' => $this->formularios->listar($trilha['concurso_id']),
        ], 'Nova etapa', ['tipo' => 'etapas', 'id' => (int) $trilhaId]);
    }

    public function editar($id)
    {
        $etapa = $this->etapas->buscarPorId($id);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);

        RoleMiddleware::exigir(['administrador', 'suporte'], $trilha['concurso_id']);

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador'], $trilha['concurso_id']);
            $dados = $this->lerDadosFormulario();

            if ($dados['nome'] === '') {
                $erro = 'Informe o nome da etapa.';
            } else {
                $erro = $this->validarPrazoSubmissao($dados);
            }

            if ($erro === null) {
                $this->etapas->atualizar(
                    $id,
                    $dados['nome'],
                    $dados['descricao'],
                    $dados['ordem'],
                    $dados['data_inicio'],
                    $dados['data_fim'],
                    $dados['formulario_dinamico_id'],
                    $dados['regra_transicao_tipo'],
                    $dados['regra_transicao_valor'],
                    $dados['config_avaliacao'],
                    $dados['prazo_final_submissao']
                );
                $etapa = $this->etapas->buscarPorId($id);
            }
        }

        $this->renderizar('admin/etapas/form', [
            'erro' => $erro,
            'trilha' => $trilha,
            'etapa' => $etapa,
            'formularios' => $this->formularios->listar($trilha['concurso_id']),
        ], 'Editar etapa', ['tipo' => 'etapa', 'id' => (int) $id]);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);

        // Fase 29 (ajuste pos-push): antes ia direto pro remover() sem
        // buscar a etapa - alem de nao existir checagem de concurso, nem
        // existencia era confirmada. $trilhaId do POST so' serve pro
        // redirect no final; a checagem usa o trilha_id de verdade, vindo
        // da propria etapa buscada no banco.
        $etapa = $this->etapas->buscarPorId($id);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);
        RoleMiddleware::exigir(['administrador'], $trilha !== null ? $trilha['concurso_id'] : null);

        try {
            $this->etapas->remover($id);
            $_SESSION['flash'] = 'Etapa removida.';
        } catch (\PDOException $e) {
            flashErro($e->getCode() === '23000'
                ? 'Não é possível remover: esta etapa já tem critérios, fórmula, avaliações ou submissões vinculadas.'
                : 'Não foi possível remover a etapa.');
        }

        $this->redirecionar('etapas/index/' . $trilhaId);
    }

    public function formularioVinculado($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);
        RoleMiddleware::exigir(['administrador', 'suporte'], $trilha !== null ? $trilha['concurso_id'] : null);

        $formulario = $etapa['formulario_dinamico_id'] !== null
            ? $this->formularios->buscarPorId($etapa['formulario_dinamico_id'])
            : null;

        $this->renderizar('admin/etapas/formulario_vinculado', [
            'etapa' => $etapa,
            'formulario' => $formulario,
        ], 'Formulário vinculado — ' . $etapa['nome'], ['tipo' => 'formulario_vinculado', 'id' => (int) $etapaId]);
    }

    /**
     * Fase 27: data_inicio, prazo_final_submissao e data_fim sao todos
     * datetime-local ("AAAA-MM-DDTHH:MM"), mesmo formato - da' pra comparar
     * as strings direto. prazo_final_submissao precisa ficar dentro de
     * [data_inicio, data_fim] quando esses campos existirem - pega erro de
     * digitacao (ex.: prazo depois do fim da etapa) sem impedir o caso de
     * uso real (prazo bem antes do fim, que e' o motivo da correcao: data_fim
     * continua aberta pros avaliadores).
     */
    private function validarPrazoSubmissao(array $dados)
    {
        if ($dados['prazo_final_submissao'] === '') {
            return null;
        }

        if ($dados['data_inicio'] !== '' && $dados['prazo_final_submissao'] < $dados['data_inicio']) {
            return 'O prazo final de submissão não pode ser anterior à Data de início.';
        }

        if ($dados['data_fim'] !== '' && $dados['prazo_final_submissao'] > $dados['data_fim']) {
            return 'O prazo final de submissão não pode ser posterior à Data de fim da etapa.';
        }

        return null;
    }

    private function lerDadosFormulario()
    {
        return [
            'nome' => trim(isset($_POST['nome']) ? $_POST['nome'] : ''),
            'descricao' => trim(isset($_POST['descricao']) ? $_POST['descricao'] : ''),
            'ordem' => (int) (isset($_POST['ordem']) ? $_POST['ordem'] : 0),
            'data_inicio' => isset($_POST['data_inicio']) ? $_POST['data_inicio'] : '',
            'data_fim' => isset($_POST['data_fim']) ? $_POST['data_fim'] : '',
            'prazo_final_submissao' => isset($_POST['prazo_final_submissao']) ? trim($_POST['prazo_final_submissao']) : '',
            'formulario_dinamico_id' => isset($_POST['formulario_dinamico_id']) ? $_POST['formulario_dinamico_id'] : '',
            'regra_transicao_tipo' => isset($_POST['regra_transicao_tipo']) ? $_POST['regra_transicao_tipo'] : '',
            'regra_transicao_valor' => isset($_POST['regra_transicao_valor']) ? str_replace(',', '.', $_POST['regra_transicao_valor']) : '',
            'config_avaliacao' => [
                'modo_designacao' => isset($_POST['modo_designacao']) ? $_POST['modo_designacao'] : '',
                'qtd_avaliadores_por_submissao' => isset($_POST['qtd_avaliadores_por_submissao']) ? $_POST['qtd_avaliadores_por_submissao'] : 1,
                'modo_consolidacao' => isset($_POST['modo_consolidacao']) ? $_POST['modo_consolidacao'] : 'unico',
                'modo_sigilo' => isset($_POST['modo_sigilo']) ? $_POST['modo_sigilo'] : 'aberto',
                'modo_avanco' => isset($_POST['modo_avanco']) ? $_POST['modo_avanco'] : 'manual',
                'mecanismo_avaliacao' => isset($_POST['mecanismo_avaliacao']) ? $_POST['mecanismo_avaliacao'] : 'nenhuma',
                'modo_feedback_avaliador' => isset($_POST['modo_feedback_avaliador']) ? $_POST['modo_feedback_avaliador'] : 'nenhum',
                'visibilidade_publica' => isset($_POST['visibilidade_publica']) ? $_POST['visibilidade_publica'] : 'apenas_classificados',
            ],
        ];
    }
}
