<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\CriterioAvaliacaoRepository;
use App\Repositories\DesafioRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\FeedbackSubmissaoRepository;
use App\Repositories\NotaLancadaRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\ResultadoEtapaRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TemaRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Services\AcessoEtapaService;
use App\Validation\CpfValidador;

class ParticipanteController extends Controller
{
    private $usuarioParticipante;
    private $participantes;
    private $equipes;
    private $trilhas;
    private $etapas;
    private $temas;
    private $desafios;
    private $submissoes;
    private $resultadosEtapa;
    private $notas;
    private $feedbackSubmissao;
    private $criterios;
    private $acessoEtapa;
    private $notificacoes;

    public function __construct()
    {
        RoleMiddleware::exigirEmQualquerConcurso(['participante']);
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->participantes = new ParticipanteRepository();
        $this->equipes = new EquipeRepository();
        $this->trilhas = new TrilhaRepository();
        $this->etapas = new EtapaRepository();
        $this->temas = new TemaRepository();
        $this->desafios = new DesafioRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->resultadosEtapa = new ResultadoEtapaRepository();
        $this->notas = new NotaLancadaRepository();
        $this->feedbackSubmissao = new FeedbackSubmissaoRepository();
        $this->criterios = new CriterioAvaliacaoRepository();
        $this->acessoEtapa = new AcessoEtapaService();
        $this->notificacoes = new NotificacaoPainelRepository();
    }

    public function index()
    {
        $participante = $this->participanteAtual();

        if ($participante === null) {
            http_response_code(404);
            exit('Nenhum participante vinculado a esta conta.');
        }

        $equipe = $this->equipes->buscarPorParticipante($participante['id']);

        if ($equipe === null) {
            http_response_code(404);
            exit('Nenhuma equipe encontrada para este participante.');
        }

        $trilha = $this->trilhas->buscarPorId($equipe['trilha_id']);
        $desafio = $equipe['desafio_id'] !== null ? $this->desafios->buscarPorId($equipe['desafio_id']) : null;
        $tema = $desafio !== null ? $this->temas->buscarPorId($desafio['tema_id']) : null;
        $colegas = $this->equipes->listarParticipantes($equipe['id']);
        $vinculoAtual = $this->equipes->buscarVinculo($equipe['id'], $participante['id']);
        $homologado = $vinculoAtual !== null && $vinculoAtual['status_homologacao'] === 'homologado';

        $etapas = [];
        if ($homologado) {
            $etapas = array_values(array_filter(
                $this->etapas->listarPorTrilha($equipe['trilha_id']),
                function ($etapa) {
                    return (int) $etapa['ordem'] > 1 && $etapa['formulario_dinamico_id'] !== null;
                }
            ));

            foreach ($etapas as &$etapaDaLista) {
                $etapaDaLista['feedback_disponivel'] = false;
                $etapaDaLista['submissao_id_feedback'] = null;
                $etapaDaLista['motivo_bloqueio'] = $this->acessoEtapa->motivoBloqueio($etapaDaLista, $equipe['id']);

                if ($etapaDaLista['modo_feedback_avaliador'] === 'nenhum') {
                    continue;
                }

                $submissaoDaEquipe = $this->submissoes->buscarPorEquipeEEtapa($equipe['id'], $etapaDaLista['id']);

                if ($submissaoDaEquipe === null) {
                    continue;
                }

                $resultadoPublicado = $this->resultadosEtapa->buscarPorSubmissaoEEtapa($submissaoDaEquipe['id'], $etapaDaLista['id']) !== null;

                if ($resultadoPublicado) {
                    $etapaDaLista['feedback_disponivel'] = true;
                    $etapaDaLista['submissao_id_feedback'] = $submissaoDaEquipe['id'];
                }
            }
            unset($etapaDaLista);
        }

        $this->renderizar('participante/painel', [
            'equipe' => $equipe,
            'trilha' => $trilha,
            'tema' => $tema,
            'desafio' => $desafio,
            'colegas' => $colegas,
            'participanteAtualId' => $participante['id'],
            'ehLider' => $vinculoAtual !== null && $vinculoAtual['papel'] === 'lider',
            'homologado' => $homologado,
            'etapas' => $etapas,
        ], 'Minha inscrição');
    }

    /**
     * Fase 17 (Melhoria 1): feedback do avaliador, visivel ao participante so'
     * depois do resultado da etapa publicado - anonimato bidirecional mantido
     * (nunca identifica qual avaliador escreveu qual texto).
     */
    public function verFeedback($submissaoId)
    {
        $participante = $this->participanteAtual();

        if ($participante === null) {
            http_response_code(404);
            exit('Nenhum participante vinculado a esta conta.');
        }

        $equipe = $this->equipes->buscarPorParticipante($participante['id']);
        $submissao = $this->submissoes->buscarPorId($submissaoId);

        if ($equipe === null || $submissao === null || (int) $submissao['equipe_id'] !== (int) $equipe['id']) {
            http_response_code(404);
            exit('Submissão não encontrada.');
        }

        $etapa = $this->etapas->buscarPorId($submissao['etapa_id']);
        $resultadoPublicado = $this->resultadosEtapa->buscarPorSubmissaoEEtapa($submissaoId, $etapa['id']) !== null;

        if (!$resultadoPublicado || $etapa['modo_feedback_avaliador'] === 'nenhum') {
            http_response_code(404);
            exit('Feedback não disponível para esta submissão.');
        }

        $feedbacksPorCriterio = [];
        $feedbacksPorSubmissao = [];

        if ($etapa['modo_feedback_avaliador'] === 'criterio') {
            foreach ($this->notas->listarPorSubmissao($submissaoId) as $nota) {
                if (empty($nota['feedback'])) {
                    continue;
                }

                $criterioId = (int) $nota['criterio_avaliacao_id'];
                if (!isset($feedbacksPorCriterio[$criterioId])) {
                    $feedbacksPorCriterio[$criterioId] = [];
                }
                $feedbacksPorCriterio[$criterioId][] = $nota['feedback'];
            }
        } else {
            foreach ($this->feedbackSubmissao->listarPorSubmissao($submissaoId) as $linha) {
                $feedbacksPorSubmissao[] = $linha['feedback'];
            }
        }

        $this->renderizar('participante/feedback', [
            'etapa' => $etapa,
            'criterios' => $this->criterios->listarPorEtapa($etapa['id']),
            'feedbacksPorCriterio' => $feedbacksPorCriterio,
            'feedbacksPorSubmissao' => $feedbacksPorSubmissao,
        ], 'Feedback — ' . $etapa['nome']);
    }

    public function meusDados()
    {
        $participante = $this->participanteAtual();

        if ($participante === null) {
            http_response_code(404);
            exit('Nenhum participante vinculado a esta conta.');
        }

        $this->processarEdicaoDados($participante, url('participante/meusDados'), 'Meus dados');
    }

    /**
     * Fase 17 (Bug 4): so permite autoedicao - validarPermissaoEdicao barra
     * qualquer tentativa de editar outro participante, mesmo sendo lider.
     * Rota mantida por defesa em profundidade (a tela ja nao linka mais para
     * ela com um id de colega).
     */
    public function editarIntegrante($participanteId)
    {
        $euAtual = $this->participanteAtual();

        if ($euAtual === null) {
            http_response_code(404);
            exit('Nenhum participante vinculado a esta conta.');
        }

        $alvo = $this->validarPermissaoEdicao($euAtual, $participanteId);
        $titulo = (int) $alvo['id'] === (int) $euAtual['id']
            ? 'Meus dados'
            : 'Editar dados de ' . $alvo['nome'];

        $this->processarEdicaoDados($alvo, url('participante/editarIntegrante/' . (int) $alvo['id']), $titulo);
    }

    /**
     * Promove outro integrante homologado a lider da equipe - substitui a
     * antiga tela dedicada "Trocar lider" por uma acao inline na tabela de
     * integrantes (Fase 15). EquipeRepository::alterarLider() ja audita.
     */
    public function promoverLider($participanteId)
    {
        $equipe = $this->equipeDoLiderAtual();
        $novoLiderId = (int) $participanteId;
        $vinculoAlvo = $this->equipes->buscarVinculo($equipe['id'], $novoLiderId);

        if ($vinculoAlvo === null || $vinculoAlvo['status_homologacao'] !== 'homologado' || $vinculoAlvo['papel'] === 'lider') {
            $_SESSION['flash'] = 'Não foi possível promover: selecione um integrante homologado, diferente do líder atual.';
            $this->redirecionar('participante/index');
            return;
        }

        $alvo = $this->participantes->buscarPorId($novoLiderId);
        $this->equipes->alterarLider($equipe['id'], $novoLiderId);

        $_SESSION['flash'] = 'Liderança da equipe transferida para "' . $alvo['nome'] . '".';
        $this->redirecionar('participante/index');
    }

    /**
     * Fase 17 (Bug 4): exclusao de integrante pelo lider - reaproveita
     * EquipeRepository::desvincularParticipante() (ja existia e ja audita,
     * nunca tinha sido exposto numa tela). Nao existe hoje nenhuma forma de
     * incluir integrante numa equipe ja criada (tudo vem de uma vez em
     * InscricaoService::gravar()), entao so' a exclusao precisa de guarda.
     */
    public function excluirIntegrante($participanteId)
    {
        $equipe = $this->equipeDoLiderAtual();
        $participanteId = (int) $participanteId;
        $vinculoAlvo = $this->equipes->buscarVinculo($equipe['id'], $participanteId);

        if ($vinculoAlvo === null) {
            http_response_code(404);
            exit('Integrante não encontrado nesta equipe.');
        }

        if ($vinculoAlvo['papel'] === 'lider') {
            $_SESSION['flash'] = 'Não é possível excluir o líder da equipe. Promova outro integrante a líder antes.';
            $this->redirecionar('participante/index');
            return;
        }

        if (count($this->equipes->listarParticipantes($equipe['id'])) <= 2) {
            $_SESSION['flash'] = 'A equipe precisa ter no mínimo 2 integrantes — não é possível excluir.';
            $this->redirecionar('participante/index');
            return;
        }

        $alvo = $this->participantes->buscarPorId($participanteId);
        $this->equipes->desvincularParticipante($equipe['id'], $participanteId);

        $_SESSION['flash'] = 'Integrante "' . $alvo['nome'] . '" removido da equipe.';
        $this->redirecionar('participante/index');
    }

    public function editarEquipe()
    {
        $equipe = $this->equipeDoLiderAtual();

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nomeEquipe = trim(isset($_POST['nome_equipe']) ? $_POST['nome_equipe'] : '');
            $vinculoInstitucional = trim(isset($_POST['vinculo_institucional']) ? $_POST['vinculo_institucional'] : '');
            $observacoes = trim(isset($_POST['observacoes']) ? $_POST['observacoes'] : '');

            if ($nomeEquipe === '') {
                $erro = 'Informe o nome da equipe.';
            } else {
                $this->equipes->atualizar($equipe['id'], $nomeEquipe, $vinculoInstitucional, $observacoes);
                $this->redirecionar('participante/index');
                return;
            }
        }

        $this->renderizar('participante/editar_equipe', [
            'equipe' => $equipe,
            'erro' => $erro,
        ], 'Editar equipe');
    }

    private function participanteAtual()
    {
        $participantes = $this->usuarioParticipante->participantesDoUsuario(Auth::usuarioId());
        $participante = !empty($participantes) ? $participantes[0] : null;

        if ($participante !== null) {
            $this->sincronizarAlertaCpf($participante);
        }

        return $participante;
    }

    /**
     * CPF invalido/nao informado nao bloqueia o acesso - so gera um alerta no
     * sino de notificacoes, que some sozinho quando o participante corrigir
     * (ver App\Repositories\NotificacaoPainelRepository::garantirUnica/removerPorTipo).
     */
    private function sincronizarAlertaCpf(array $participante)
    {
        $usuarioId = Auth::usuarioId();

        if (!CpfValidador::valido($participante['cpf'])) {
            $this->notificacoes->garantirUnica(
                $usuarioId,
                'cpf_invalido',
                'CPF inválido',
                'Seu cadastro está com um CPF inválido ou não informado. Corrija em "Meus dados".',
                ['url' => url('participante/meusDados')]
            );
        } else {
            $this->notificacoes->removerPorTipo($usuarioId, 'cpf_invalido');
        }
    }

    private function equipeDoLiderAtual()
    {
        $participante = $this->participanteAtual();

        if ($participante === null) {
            http_response_code(404);
            exit('Nenhum participante vinculado a esta conta.');
        }

        $equipe = $this->equipes->buscarPorParticipante($participante['id']);

        if ($equipe === null) {
            http_response_code(404);
            exit('Nenhuma equipe encontrada para este participante.');
        }

        $vinculo = $this->equipes->buscarVinculo($equipe['id'], $participante['id']);

        if ($vinculo === null || $vinculo['papel'] !== 'lider') {
            http_response_code(403);
            exit('Acesso negado: apenas o líder da equipe pode gerenciar esses dados.');
        }

        return $equipe;
    }

    /**
     * Fluxo de edicao de cadastro (nome/telefone/CPF) compartilhado por
     * meusDados() (autoedicao) e editarIntegrante() (lider editando outro
     * integrante) - a unica diferenca entre os dois casos e' qual
     * participante e' passado aqui, ja validado por quem chamou.
     */
    private function processarEdicaoDados(array $participante, $actionUrl, $titulo)
    {
        $erro = null;
        $sucesso = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $telefone = trim(isset($_POST['telefone']) ? $_POST['telefone'] : '');
            $cpf = trim(isset($_POST['cpf']) ? $_POST['cpf'] : '');

            if ($nome === '') {
                $erro = 'Informe o nome.';
            } elseif ($cpf !== '' && !CpfValidador::valido($cpf)) {
                $erro = 'CPF inválido.';
            } else {
                $cpfNormalizado = CpfValidador::apenasDigitos($cpf);
                $cpfMudou = $cpfNormalizado !== $participante['cpf'];

                $this->participantes->atualizarDados($participante['id'], $nome, $telefone, $cpfNormalizado);

                if ($cpfMudou) {
                    $this->aposMudarCpf($participante);
                    $sucesso = 'Dados atualizados. Como o CPF mudou, a inscrição volta para conferência do Suporte.';
                } else {
                    $sucesso = 'Dados atualizados.';
                }

                $participante = $this->participantes->buscarPorId($participante['id']);
            }
        }

        $this->renderizar('participante/meus_dados', [
            'participante' => $participante,
            'erro' => $erro,
            'sucesso' => $sucesso,
            'actionUrl' => $actionUrl,
            'tituloPagina' => $titulo,
        ], $titulo);
    }

    /**
     * Quando o CPF muda, o vinculo do participante-alvo volta para pendente
     * e o alerta de "inscricao rejeitada" e' limpo nas contas de usuario
     * ligadas a ELE (usuariosDoParticipante), nao na de quem esta editando -
     * importante porque o lider pode estar editando o cadastro de outro
     * integrante (ver editarIntegrante()).
     */
    private function aposMudarCpf(array $participante)
    {
        $equipe = $this->equipes->buscarPorParticipante($participante['id']);

        if ($equipe === null) {
            return;
        }

        $vinculo = $this->equipes->buscarVinculo($equipe['id'], $participante['id']);

        if ($vinculo === null) {
            return;
        }

        $this->equipes->voltarParaPendente($vinculo['id']);

        foreach ($this->usuarioParticipante->usuariosDoParticipante($participante['id']) as $usuarioId) {
            $this->notificacoes->removerPorTipo($usuarioId, 'equipe_rejeitada');
        }
    }

    /**
     * Fase 17 (Bug 4): apenas autoedicao e' permitida - o lider deixou de
     * poder editar dados de outros integrantes (risco de exposicao/alteracao
     * indevida de dados pessoais de colegas). Validado aqui no servidor,
     * nunca so pela ausencia do icone na tela.
     */
    private function validarPermissaoEdicao(array $euAtual, $participanteId)
    {
        if ((int) $participanteId === (int) $euAtual['id']) {
            return $euAtual;
        }

        http_response_code(403);
        exit('Acesso negado: você só pode editar os seus próprios dados.');
    }
}
