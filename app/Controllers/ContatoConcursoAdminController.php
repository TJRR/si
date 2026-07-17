<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\ContatoConcursoRepository;
use App\Repositories\MensagemContatoRepository;

class ContatoConcursoAdminController extends Controller
{
    private $contatos;
    private $concursos;
    private $mensagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->contatos = new ContatoConcursoRepository();
        $this->concursos = new ConcursoRepository();
        $this->mensagens = new MensagemContatoRepository();
    }

    public function index($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $redesSociais = [];

            foreach (ContatoConcursoRepository::REDES_SUPORTADAS as $rede) {
                $valor = trim(isset($_POST['rede_' . $rede]) ? $_POST['rede_' . $rede] : '');

                if ($valor !== '') {
                    $redesSociais[$rede] = $valor;
                }
            }

            $this->contatos->salvar($concursoId, [
                'email' => $this->campoOuNulo('email'),
                'telefone' => $this->campoOuNulo('telefone'),
                'whatsapp' => $this->campoOuNulo('whatsapp'),
                'endereco' => $this->campoOuNulo('endereco'),
                'redes_sociais' => $redesSociais,
                'formulario_contato_ativo' => isset($_POST['formulario_contato_ativo']) ? 1 : 0,
            ]);

            $_SESSION['flash'] = 'Contato atualizado.';
            $this->redirecionar('contatosConcurso/index/' . $concursoId);
            return;
        }

        $this->renderizar('admin/contatos_concurso/form', [
            'concurso' => $concurso,
            'contato' => $this->contatos->buscarPorConcurso($concursoId),
        ], 'Contato de ' . $concurso['nome'], ['tipo' => 'contatosConcurso', 'id' => (int) $concursoId]);
    }

    public function mensagens($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        $this->renderizar('admin/contatos_concurso/mensagens', [
            'concurso' => $concurso,
            'mensagens' => $this->mensagens->listarPorConcurso($concursoId),
        ], 'Mensagens recebidas — ' . $concurso['nome'], ['tipo' => 'contatosConcurso', 'id' => (int) $concursoId]);
    }

    private function campoOuNulo($chave)
    {
        $valor = trim(isset($_POST[$chave]) ? $_POST[$chave] : '');

        return $valor !== '' ? $valor : null;
    }
}
