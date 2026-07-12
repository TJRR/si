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
        $this->redirecionar('participante/minhaEquipe');
    }

    public function meusDados()
    {
        $participante = $this->participanteAtual();

        if ($participante === null) {
            http_response_code(404);
            exit('Nenhum participante vinculado a esta conta.');
        }

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
                    $equipe = $this->equipes->buscarPorParticipante($participante['id']);
                    if ($equipe !== null) {
                        $vinculo = $this->equipes->buscarVinculo($equipe['id'], $participante['id']);
                        if ($vinculo !== null) {
                            $this->equipes->voltarParaPendente($vinculo['id']);
                            $this->notificacoes->removerPorTipo(Auth::usuarioId(), 'equipe_rejeitada');
                        }
                    }
                    $sucesso = 'Dados atualizados. Como o CPF mudou, sua inscrição volta para conferência do Suporte.';
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
        ], 'Meus dados');
    }

    public function minhaEquipe()
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

        $this->renderizar('participante/minha_equipe', [
            'equipe' => $equipe,
            'trilha' => $trilha,
            'tema' => $tema,
            'colegas' => $colegas,
            'participanteAtualId' => $participante['id'],
            'ehLider' => $vinculoAtual !== null && $vinculoAtual['papel'] === 'lider',
        ], 'Minha equipe');
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
                $this->redirecionar('participante/minhaEquipe');
                return;
            }
        }

        $this->renderizar('participante/editar_equipe', [
            'equipe' => $equipe,
            'erro' => $erro,
        ], 'Editar equipe');
    }

    public function trocarLider()
    {
        $equipe = $this->equipeDoLiderAtual();
        $colegasHomologados = array_values(array_filter(
            $this->equipes->listarParticipantes($equipe['id']),
            function ($colega) {
                return $colega['status_homologacao'] === 'homologado';
            }
        ));

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $novoLiderId = isset($_POST['novo_lider_id']) ? (int) $_POST['novo_lider_id'] : 0;
            $idsValidos = array_map(function ($colega) {
                return (int) $colega['id'];
            }, $colegasHomologados);

            if (!in_array($novoLiderId, $idsValidos, true)) {
                $erro = 'Selecione um integrante homologado da equipe.';
            } else {
                $this->equipes->alterarLider($equipe['id'], $novoLiderId);
                $this->redirecionar('participante/minhaEquipe');
                return;
            }
        }

        $this->renderizar('participante/trocar_lider', [
            'equipe' => $equipe,
            'colegas' => $colegasHomologados,
            'erro' => $erro,
        ], 'Trocar líder');
    }

    public function submissoes()
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

        if ($vinculo === null || $vinculo['status_homologacao'] !== 'homologado') {
            $this->renderizar('participante/submissoes', [
                'equipe' => $equipe,
                'homologado' => false,
                'etapas' => [],
            ], 'Submissões');
            return;
        }

        $etapasDaTrilha = array_values(array_filter(
            $this->etapas->listarPorTrilha($equipe['trilha_id']),
            function ($etapa) {
                return (int) $etapa['ordem'] > 1 && $etapa['formulario_dinamico_id'] !== null;
            }
        ));

        $this->renderizar('participante/submissoes', [
            'equipe' => $equipe,
            'homologado' => true,
            'etapas' => $etapasDaTrilha,
        ], 'Submissões');
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
}
