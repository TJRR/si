<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ContatoConcursoRepository;
use App\Repositories\MensagemContatoRepository;

class ContatoConcursoAdminController extends Controller
{
    private $contatos;
    private $mensagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->contatos = new ContatoConcursoRepository();
        $this->mensagens = new MensagemContatoRepository();
    }

    public function index()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $redesSociais = [];

            foreach (ContatoConcursoRepository::REDES_SUPORTADAS as $rede) {
                $valor = trim(isset($_POST['rede_' . $rede]) ? $_POST['rede_' . $rede] : '');

                if ($valor !== '') {
                    $redesSociais[$rede] = $valor;
                }
            }

            $this->contatos->salvar([
                'email' => $this->campoOuNulo('email'),
                'telefone' => $this->campoOuNulo('telefone'),
                'whatsapp' => $this->campoOuNulo('whatsapp'),
                'endereco' => $this->campoOuNulo('endereco'),
                'texto_institucional' => isset($_POST['texto_institucional']) ? $_POST['texto_institucional'] : null,
                'redes_sociais' => $redesSociais,
                'formulario_contato_ativo' => isset($_POST['formulario_contato_ativo']) ? 1 : 0,
            ]);

            $_SESSION['flash'] = 'Contato atualizado.';
            $this->redirecionar('contatosConcurso/index');
            return;
        }

        $this->renderizar('admin/contatos_concurso/form', [
            'contato' => $this->contatos->buscar(),
        ], 'Contato', ['tipo' => 'configuracaoContato', 'id' => null]);
    }

    public function mensagens()
    {
        $this->renderizar('admin/contatos_concurso/mensagens', [
            'mensagens' => $this->mensagens->listar(),
        ], 'Mensagens recebidas', ['tipo' => 'configuracaoContato', 'id' => null]);
    }

    private function campoOuNulo($chave)
    {
        $valor = trim(isset($_POST[$chave]) ? $_POST[$chave] : '');

        return $valor !== '' ? $valor : null;
    }
}
