<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Repositories\EquipeRepository;
use App\Repositories\HomologacaoPublicaRepository;
use App\Repositories\TrilhaRepository;

/**
 * Fase 19 (#17): exposicao publica (sem login) da lista de equipes
 * homologadas de uma trilha, so' depois que o Admin publicar
 * (HomologacaoController::publicar) - mesmo padrao de gate 404 usado em
 * ResultadoPublicoController::etapa(). So' nome de equipe + nome dos
 * integrantes homologados, nunca cpf/email/telefone.
 */
class HomologacaoPublicaController extends Controller
{
    private $trilhas;
    private $equipes;
    private $homologacaoPublica;

    public function __construct()
    {
        $this->trilhas = new TrilhaRepository();
        $this->equipes = new EquipeRepository();
        $this->homologacaoPublica = new HomologacaoPublicaRepository();
    }

    public function trilha($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null || !$this->homologacaoPublica->jaPublicado($trilhaId)) {
            http_response_code(404);
            exit('Lista de equipes homologadas nao encontrada ou ainda nao publicada.');
        }

        $equipes = $this->equipes->listarHomologadasPorTrilha($trilhaId, (int) $trilha['minimo_integrantes_homologados']);

        $this->renderizar('publico/equipes_homologadas', [
            'trilha' => $trilha,
            'equipes' => $equipes,
        ], 'Equipes homologadas — ' . $trilha['nome']);
    }
}
