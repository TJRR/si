<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\CategoriaAvaliadorRepository;
use App\Repositories\EtapaCategoriaAvaliadorRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\TrilhaRepository;

class VagaAvaliadorAdminController extends Controller
{
    private $etapas;
    private $trilhas;
    private $categorias;
    private $vagas;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->categorias = new CategoriaAvaliadorRepository();
        $this->vagas = new EtapaCategoriaAvaliadorRepository();
    }

    public function index($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);
        $flash = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $quantidades = isset($_POST['quantidade']) && is_array($_POST['quantidade']) ? $_POST['quantidade'] : [];
            $this->vagas->salvarQuantidades($etapaId, $quantidades);
            $_SESSION['flash'] = 'Vagas por categoria salvas.';
            $this->redirecionar('vagasAvaliador/index/' . $etapaId);
            return;
        }

        $categoriasDoConcurso = $this->categorias->listarPorConcurso($trilha['concurso_id']);
        $quantidadesAtuais = [];

        foreach ($this->vagas->listarPorEtapa($etapaId) as $vaga) {
            $quantidadesAtuais[(int) $vaga['categoria_avaliador_id']] = (int) $vaga['quantidade'];
        }

        $this->renderizar('admin/vagas_avaliador/index', [
            'etapa' => $etapa,
            'trilha' => $trilha,
            'categorias' => $categoriasDoConcurso,
            'quantidadesAtuais' => $quantidadesAtuais,
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Vagas por categoria — ' . $etapa['nome'], ['tipo' => 'vagas_avaliador', 'id' => (int) $etapaId]);

        unset($_SESSION['flash']);
    }
}
