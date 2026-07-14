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
use App\Repositories\EtapaRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\TemaDesafioRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Validation\CpfValidador;

class ParticipanteController extends Controller
{
    private $usuarioParticipante;
    private $participantes;
    private $equipes;
    private $trilhas;
    private $etapas;
    private $temas;
    private $notificacoes;

    public function __construct()
    {
        RoleMiddleware::exigirEmQualquerConcurso(['participante']);
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->participantes = new ParticipanteRepository();
        $this->equipes = new EquipeRepository();
        $this->trilhas = new TrilhaRepository();
        $this->etapas = new EtapaRepository();
        $this->temas = new TemaDesafioRepository();
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
        $tema = $equipe['tema_desafio_id'] !== null ? $this->temas->buscarPorId($equipe['tema_desafio_id']) : null;
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
        }

        $this->renderizar('participante/painel', [
            'equipe' => $equipe,
            'trilha' => $trilha,
            'tema' => $tema,
            'colegas' => $colegas,
            'participanteAtualId' => $participante['id'],
            'ehLider' => $vinculoAtual !== null && $vinculoAtual['papel'] === 'lider',
            'homologado' => $homologado,
            'etapas' => $etapas,
        ], 'Minha inscrição');
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
     * Reaproveita o mesmo formulario/fluxo de meusDados() para o lider
     * editar o cadastro de outro integrante da equipe - a permissao e'
     * sempre validada no servidor (validarPermissaoEdicao), nunca so pela
     * ausencia/presenca do icone na tela.
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
     * Autoedicao (participanteId == eu mesmo) e' sempre permitida; editar
     * outro integrante exige que eu seja o lider homologado da equipe E que
     * o alvo pertenca a essa mesma equipe - validado aqui no servidor,
     * nunca so pela ausencia do icone na tela (Fase 15, Melhoria 1).
     */
    private function validarPermissaoEdicao(array $euAtual, $participanteId)
    {
        $participanteId = (int) $participanteId;

        if ($participanteId === (int) $euAtual['id']) {
            return $euAtual;
        }

        $equipe = $this->equipes->buscarPorParticipante($euAtual['id']);
        $meuVinculo = $equipe !== null ? $this->equipes->buscarVinculo($equipe['id'], $euAtual['id']) : null;

        if ($equipe === null || $meuVinculo === null || $meuVinculo['papel'] !== 'lider') {
            http_response_code(403);
            exit('Acesso negado: apenas o líder da equipe pode editar dados de outro integrante.');
        }

        $vinculoAlvo = $this->equipes->buscarVinculo($equipe['id'], $participanteId);

        if ($vinculoAlvo === null) {
            http_response_code(403);
            exit('Acesso negado: este integrante não pertence à sua equipe.');
        }

        $alvo = $this->participantes->buscarPorId($participanteId);

        if ($alvo === null) {
            http_response_code(404);
            exit('Participante não encontrado.');
        }

        return $alvo;
    }
}
