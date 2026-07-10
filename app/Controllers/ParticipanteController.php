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

    public function __construct()
    {
        RoleMiddleware::exigirEmQualquerConcurso(['participante']);
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->participantes = new ParticipanteRepository();
        $this->equipes = new EquipeRepository();
        $this->trilhas = new TrilhaRepository();
        $this->etapas = new EtapaRepository();
        $this->temas = new TemaDesafioRepository();
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

        $this->renderizar('participante/minha_equipe', [
            'equipe' => $equipe,
            'trilha' => $trilha,
            'tema' => $tema,
            'colegas' => $colegas,
            'participanteAtualId' => $participante['id'],
        ], 'Minha equipe');
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

        return !empty($participantes) ? $participantes[0] : null;
    }
}
