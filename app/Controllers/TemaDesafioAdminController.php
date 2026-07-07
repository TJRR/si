<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\TemaDesafioRepository;
use App\Repositories\TrilhaRepository;

class TemaDesafioAdminController extends Controller
{
    private $temas;
    private $trilhas;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->temas = new TemaDesafioRepository();
        $this->trilhas = new TrilhaRepository();
    }

    public function index($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha nao encontrada.');
        }

        $lista = $this->temas->listarPorTrilha($trilhaId);
        $this->renderizar('admin/temas/index', [
            'trilha' => $trilha,
            'temas' => $lista,
        ], 'Temas/Desafios de ' . $trilha['nome']);
    }

    public function novo($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha nao encontrada.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $descricaoLonga = trim(isset($_POST['descricao_longa']) ? $_POST['descricao_longa'] : '');
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            if ($nome === '') {
                $erro = 'Informe o nome do tema/desafio.';
            } else {
                $this->temas->criar($trilhaId, $nome, $descricaoLonga, $ativo);
                $this->redirecionar('temas/index/' . $trilhaId);
                return;
            }
        }

        $this->renderizar('admin/temas/form', [
            'erro' => $erro,
            'trilha' => $trilha,
            'tema' => null,
        ], 'Novo tema/desafio');
    }

    public function editar($id)
    {
        $tema = $this->temas->buscarPorId($id);

        if ($tema === null) {
            http_response_code(404);
            exit('Tema/desafio nao encontrado.');
        }

        $trilha = $this->trilhas->buscarPorId($tema['trilha_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome']) ? $_POST['nome'] : '');
            $descricaoLonga = trim(isset($_POST['descricao_longa']) ? $_POST['descricao_longa'] : '');
            $ativo = isset($_POST['ativo']) ? 1 : 0;

            if ($nome === '') {
                $erro = 'Informe o nome do tema/desafio.';
            } else {
                $this->temas->atualizar($id, $nome, $descricaoLonga, $ativo);
                $tema = $this->temas->buscarPorId($id);
            }
        }

        $this->renderizar('admin/temas/form', [
            'erro' => $erro,
            'trilha' => $trilha,
            'tema' => $tema,
        ], 'Editar tema/desafio');
    }
}
