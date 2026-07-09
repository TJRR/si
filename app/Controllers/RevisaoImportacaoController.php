<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EquipeRepository;
use App\Repositories\ImportacaoPendenciaRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\SubmissaoRepository;
use App\Validation\CpfValidador;

class RevisaoImportacaoController extends Controller
{
    private $pendencias;
    private $participantes;
    private $equipes;
    private $submissoes;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->pendencias = new ImportacaoPendenciaRepository();
        $this->participantes = new ParticipanteRepository();
        $this->equipes = new EquipeRepository();
        $this->submissoes = new SubmissaoRepository();
    }

    public function index()
    {
        $this->renderizar('admin/revisao/index', [
            'pendencias' => $this->pendencias->listarPendentes(),
        ], 'Conferencia de inscricoes importadas');
    }

    public function equipes()
    {
        $this->renderizar('admin/revisao/equipes', [
            'equipes' => $this->equipes->listarComContagemParticipantes(),
        ], 'Equipes importadas');
    }

    public function equipe($id)
    {
        $equipe = $this->equipes->buscarPorId((int) $id);

        if ($equipe === null) {
            $_SESSION['flash'] = 'Equipe nao encontrada.';
            $this->redirecionar('revisao/equipes');
            return;
        }

        $this->renderizar('admin/revisao/equipe', [
            'equipe' => $equipe,
            'participantes' => $this->equipes->listarParticipantes($equipe['id']),
        ], 'Equipe: ' . $equipe['nome_equipe']);
    }

    public function corrigirCpf()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $novoCpf = trim(isset($_POST['novo_cpf']) ? $_POST['novo_cpf'] : '');
        $pendencia = $this->pendencias->buscarPorId($id);

        if ($pendencia === null || $pendencia['participante_id'] === null) {
            $_SESSION['flash'] = 'Pendencia nao encontrada.';
            $this->redirecionar('revisao/index');
            return;
        }

        $cpfDigitos = CpfValidador::apenasDigitos($novoCpf);

        if (!CpfValidador::valido($cpfDigitos)) {
            $_SESSION['flash'] = 'CPF informado ainda e invalido.';
            $this->redirecionar('revisao/index');
            return;
        }

        $this->participantes->atualizarCpf($pendencia['participante_id'], $cpfDigitos);

        $observacao = 'CPF corrigido para ' . $cpfDigitos . '.';

        if ($pendencia['equipe_id'] !== null) {
            $submissao = $this->submissoes->buscarPorEquipe($pendencia['equipe_id']);

            if ($submissao !== null) {
                try {
                    $this->submissoes->inserirCpf($submissao['id'], $pendencia['trilha_id'], $cpfDigitos);
                } catch (\PDOException $e) {
                    if ((int) $e->getCode() === 23000) {
                        $observacao .= ' Atencao: este CPF ja esta em uso em outra equipe desta trilha (verificar duplicidade manualmente).';
                    } else {
                        throw $e;
                    }
                }
            }
        }

        $this->pendencias->marcarResolvida($id, Auth::usuarioId(), $observacao);
        $_SESSION['flash'] = 'CPF corrigido com sucesso.';
        $this->redirecionar('revisao/index');
    }

    public function removerIntegrante()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $pendencia = $this->pendencias->buscarPorId($id);

        if ($pendencia === null || $pendencia['equipe_id'] === null || $pendencia['participante_id'] === null) {
            $_SESSION['flash'] = 'Pendencia nao encontrada.';
            $this->redirecionar('revisao/index');
            return;
        }

        $this->equipes->desvincularParticipante($pendencia['equipe_id'], $pendencia['participante_id']);
        $this->pendencias->marcarResolvida($id, Auth::usuarioId(), 'Integrante duplicado removido da equipe.');
        $_SESSION['flash'] = 'Integrante removido da equipe.';
        $this->redirecionar('revisao/index');
    }

    public function ignorar()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $observacao = trim(isset($_POST['observacao']) ? $_POST['observacao'] : '');

        $this->pendencias->marcarIgnorada($id, Auth::usuarioId(), $observacao);
        $_SESSION['flash'] = 'Pendencia marcada como ignorada.';
        $this->redirecionar('revisao/index');
    }
}
