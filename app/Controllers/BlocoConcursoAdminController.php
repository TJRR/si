<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\BlocoConcursoRepository;
use App\Repositories\ConcursoRepository;

/**
 * Fase 38 (#247): tela unica (upsert, sem listagem) do bloco de conteudo
 * rico opcional de um concurso - mesmo espirito de OficinaAdminController
 * (escopado por concurso), mas com o padrao de gravacao 1:1 de
 * ContatoConcursoAdminController.
 */
class BlocoConcursoAdminController extends Controller
{
    private $blocos;
    private $concursos;

    public function __construct()
    {
        RoleMiddleware::exigirEmQualquerConcurso(['administrador', 'suporte']);
        $this->blocos = new BlocoConcursoRepository();
        $this->concursos = new ConcursoRepository();
    }

    public function index($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $concurso['id']);

        $erro = null;
        $bloco = $this->blocos->buscarPorConcurso($concurso['id']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador'], $concurso['id']);

            $titulo = trim(isset($_POST['titulo']) ? $_POST['titulo'] : '');

            $bloco = [
                'titulo' => $titulo !== '' ? $titulo : null,
                'conteudo_html' => isset($_POST['conteudo_html']) ? sanitizarHtmlRico($_POST['conteudo_html']) : null,
                'ativo' => isset($_POST['ativo']) ? 1 : 0,
            ];

            $this->blocos->salvar($concurso['id'], $bloco);

            flashSucesso('Bloco da edição atualizado.');
            $this->redirecionar('blocoConcurso/index/' . $concurso['id']);
            return;
        }

        $this->renderizar('admin/blocos_concurso/form', [
            'concurso' => $concurso,
            'bloco' => $bloco,
            'erro' => $erro,
        ], 'Bloco da edição', ['tipo' => 'blocoConcurso', 'id' => (int) $concurso['id']]);
    }
}
