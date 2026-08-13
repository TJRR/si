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
use App\Repositories\FormulaPontuacaoRepository;
use App\Repositories\MentoriaRepository;
use App\Repositories\NotaLancadaRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\OficinaRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\ResultadoEtapaRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TemaRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Services\AcessoEtapaService;
use App\Services\ResultadoEtapaService;
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
    private $mentorias;
    private $oficinas;
    private $perfis;
    private $formulas;
    private $resultadoEtapaService;

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
        $this->formulas = new FormulaPontuacaoRepository();
        $this->resultadoEtapaService = new ResultadoEtapaService();
        $this->notificacoes = new NotificacaoPainelRepository();
        $this->mentorias = new MentoriaRepository();
        $this->oficinas = new OficinaRepository();
        $this->perfis = new PerfilRepository();
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

                // Fase 27 (#4): o icone de "notas e feedback" agora depende so'
                // do resultado ter sido publicado - as notas por criterio/
                // avaliador (sempre existem quando ha' resultado) deixaram de
                // estar atreladas a modo_feedback_avaliador, que so' controla
                // se HA' texto qualitativo, nao se as notas aparecem.
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
            'mentoriaDisponivel' => $this->mentorias->existeParaConcurso($trilha['concurso_id']),
            'oficinaDisponivel' => $this->oficinas->existeParaConcurso($trilha['concurso_id']),
        ], 'Minha inscrição');
    }

    /**
     * Fase 17 (Melhoria 1): feedback do avaliador, visivel ao participante so'
     * depois do resultado da etapa publicado - anonimato bidirecional mantido
     * (nunca identifica qual avaliador escreveu qual texto).
     *
     * Fase 27 (#4): passou a mostrar tambem as NOTAS por criterio x
     * avaliador (nao so' o texto qualitativo) - disponivel sempre que o
     * resultado foi publicado, independente de modo_feedback_avaliador (que
     * so' controla se ha' texto, nunca controlou as notas). Avaliador
     * identificado so' por numero de ordem ("Avaliador 1, 2..."), nunca por
     * nome - mesmo padrao de anonimato ja usado no relatorio PDF do Admin
     * (Fase 26), so' que aqui nem as iniciais aparecem.
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

        if (!$resultadoPublicado) {
            http_response_code(404);
            exit('Notas e feedback não disponíveis para esta submissão.');
        }

        $criterios = $this->criterios->listarPorEtapa($etapa['id']);
        $feedbacksPorCriterio = [];
        $feedbacksPorSubmissao = [];
        $notasPorCriterioEAvaliador = [];
        $avaliadorOrdinal = [];

        $notas = $this->notas->listarPorSubmissao($submissaoId);
        usort($notas, function ($a, $b) {
            return ((int) $a['usuario_id']) <=> ((int) $b['usuario_id']);
        });

        foreach ($notas as $nota) {
            $usuarioId = (int) $nota['usuario_id'];

            if (!isset($avaliadorOrdinal[$usuarioId])) {
                $avaliadorOrdinal[$usuarioId] = count($avaliadorOrdinal) + 1;
            }

            $criterioId = (int) $nota['criterio_avaliacao_id'];
            $notasPorCriterioEAvaliador[$criterioId][$avaliadorOrdinal[$usuarioId]] = $nota['nota'];

            if ($etapa['modo_feedback_avaliador'] === 'criterio' && !empty($nota['feedback'])) {
                $feedbacksPorCriterio[$criterioId][] = $nota['feedback'];
            }
        }

        if ($etapa['modo_feedback_avaliador'] === 'submissao') {
            foreach ($this->feedbackSubmissao->listarPorSubmissao($submissaoId) as $linha) {
                $feedbacksPorSubmissao[] = $linha['feedback'];
            }
        }

        // Fase 27 (#2): media por criterio e nota final (NE) reaproveitam
        // exatamente o calculo oficial de ResultadoEtapaService (o mesmo
        // usado pra publicar o ranking) - nunca reimplementar a formula aqui,
        // pra nao correr risco de mostrar um numero diferente do real.
        $mediaPorCriterioId = $this->resultadoEtapaService->mediaPorCriterioId($submissaoId, $criterios);
        $formula = $this->formulas->buscarPorEtapa($etapa['id']);
        $notaFinal = $formula !== null
            ? $this->resultadoEtapaService->calcularNe($submissaoId, $criterios, $formula['expressao'], $etapa['modo_consolidacao'])
            : null;

        $this->renderizar('participante/feedback', [
            'etapa' => $etapa,
            'criterios' => $criterios,
            'feedbacksPorCriterio' => $feedbacksPorCriterio,
            'feedbacksPorSubmissao' => $feedbacksPorSubmissao,
            'notasPorCriterioEAvaliador' => $notasPorCriterioEAvaliador,
            'totalAvaliadores' => count($avaliadorOrdinal),
            'mediaPorCriterioId' => $mediaPorCriterioId,
            'notaFinal' => $notaFinal,
        ], 'Notas e Feedback — ' . $etapa['nome']);
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

        // Fase 27 (correcao de seguranca): sem e-mail, AcessoParticipanteService::
        // liberarAcesso() nunca criou conta pra esse participante - promove-lo
        // deixaria a equipe sem ninguem capaz de logar e gerenciar integrantes
        // (equipeDoLiderAtual() exige papel = 'lider').
        if (empty($alvo['email'])) {
            $_SESSION['flash'] = 'Não foi possível promover "' . $alvo['nome'] . '": ele(a) ainda não tem e-mail cadastrado. Inclua o e-mail dele(a) na lista de integrantes antes de promover.';
            $this->redirecionar('participante/index');
            return;
        }

        $this->equipes->alterarLider($equipe['id'], $novoLiderId);

        $_SESSION['flash'] = 'Liderança da equipe transferida para "' . $alvo['nome'] . '".';
        $this->redirecionar('participante/index');
    }

    /**
     * Fase 27 (correcao de seguranca): excecao estreita e auditada a' regra
     * da Fase 17 ("lider nao edita dados de outro integrante") - o lider
     * pode APENAS incluir um e-mail hoje vazio, nunca sobrescrever um ja
     * existente (o campo continua nao sendo livremente editavel). Resolve o
     * caso em que um integrante foi homologado sem e-mail e por isso nunca
     * ganhou conta de acesso (AcessoParticipanteService::liberarAcesso()
     * pula em silencio quando o e-mail esta vazio).
     *
     * Fase 29 (Bug 1): nao bloqueia mais quando o e-mail ja pertence a uma
     * conta em usuarios (antes barrava em silencio quando o integrante ja
     * tinha se auto-cadastrado pela tela publica de Cadastro com o mesmo
     * e-mail, deixando participantes.email pra sempre vazio - decisao
     * confirmada com o usuario: sempre reconciliar). A propria
     * AcessoParticipanteService::vincularUsuarioEPerfil() ja reaproveita
     * essa conta pelo e-mail quando o acesso for liberado (na homologacao
     * ou no convite manual do admin), aprovando-a se preciso.
     */
    public function incluirEmailIntegrante($participanteId)
    {
        $equipe = $this->equipeDoLiderAtual();
        $participanteId = (int) $participanteId;
        $vinculoAlvo = $this->equipes->buscarVinculo($equipe['id'], $participanteId);

        if ($vinculoAlvo === null) {
            http_response_code(404);
            exit('Integrante não encontrado nesta equipe.');
        }

        $alvo = $this->participantes->buscarPorId($participanteId);

        if (!empty($alvo['email'])) {
            $_SESSION['flash'] = 'Este integrante já tem e-mail cadastrado.';
            $this->redirecionar('participante/index');
            return;
        }

        $email = trim(isset($_POST['email']) ? $_POST['email'] : '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $_SESSION['flash'] = 'Informe um e-mail válido.';
            $this->redirecionar('participante/index');
            return;
        }

        if ($this->participantes->buscarPorEmail($email) !== null) {
            $_SESSION['flash'] = 'Este e-mail já está em uso por outro participante cadastrado.';
            $this->redirecionar('participante/index');
            return;
        }

        $this->participantes->atualizarEmail($participanteId, $email);
        $this->notificarAdminEmailCompleto($equipe, $alvo['nome'], $email);

        $_SESSION['flash'] = 'E-mail de "' . $alvo['nome'] . '" cadastrado. O Admin foi avisado para liberar o acesso dele(a).';
        $this->redirecionar('participante/index');
    }

    /**
     * Fase 27, Parte C: avisa todo administrador do concurso da trilha da
     * equipe (sino de notificacoes, Fase 12) - o convite em si (criar conta +
     * enviar link) fica a cargo do Admin, um clique na tela de Homologacao
     * (HomologacaoController::convidarAcesso()), nunca automatico.
     */
    private function notificarAdminEmailCompleto(array $equipe, $nomeIntegrante, $email)
    {
        $trilha = $this->trilhas->buscarPorId($equipe['trilha_id']);

        foreach ($this->perfis->listarUsuariosPorPerfilConcurso('administrador', $trilha['concurso_id']) as $admin) {
            $this->notificacoes->criar(
                $admin['id'],
                'participante_email_completo',
                'Participante com e-mail cadastrado',
                '"' . $nomeIntegrante . '" (equipe "' . $equipe['nome_equipe'] . '") teve o e-mail ' . $email . ' cadastrado pelo líder. Convide-o para liberar o acesso.',
                ['url' => url('homologacao/index/' . (int) $equipe['trilha_id'])]
            );
        }
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
