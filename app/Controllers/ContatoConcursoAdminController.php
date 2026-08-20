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
        $erro = null;
        $contato = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $redesSociais = [];

            foreach (ContatoConcursoRepository::REDES_SUPORTADAS as $rede) {
                $valor = trim(isset($_POST['rede_' . $rede]) ? $_POST['rede_' . $rede] : '');

                if ($valor !== '') {
                    $redesSociais[$rede] = $valor;
                }
            }

            // Estes dois campos sao texto livre digitado pelo Admin e viram
            // href no rodape publico: mesma convencao de link_meet (ver
            // linkHttpValido() em app/helpers.php) - valida na gravacao aqui
            // e de novo na exibicao, como defesa em profundidade. Sem isso,
            // "instagram.com/premio" (sem esquema) virava caminho relativo e
            // o link nascia quebrado, e "javascript:..." virava XSS
            // armazenado ao ser clicado.
            $mapaUrl = $this->campoOuNulo('mapa_url');

            foreach ($redesSociais as $rede => $link) {
                if (!linkHttpValido($link)) {
                    $rotulo = ContatoConcursoRepository::REDES_ROTULOS[$rede];
                    $erro = 'O link do ' . $rotulo . ' deve começar com http:// ou https://.';
                    break;
                }
            }

            if ($erro === null && $mapaUrl !== null && !linkHttpValido($mapaUrl)) {
                $erro = 'O link do mapa deve começar com http:// ou https://.';
            }

            // Nada de descartar o que foi digitado por causa de um link
            // errado: o formulario tem editor rico (texto institucional), e
            // perder isso a cada erro de validacao sairia caro. Devolve os
            // proprios valores do POST pra view, no mesmo formato que ela
            // espera do banco.
            $contato = [
                'email' => $this->campoOuNulo('email'),
                'telefone' => $this->campoOuNulo('telefone'),
                'whatsapp' => $this->campoOuNulo('whatsapp'),
                'endereco' => $this->campoOuNulo('endereco'),
                'mapa_url' => $mapaUrl,
                'nome_organizador_assinatura' => $this->campoOuNulo('nome_organizador_assinatura'),
                'texto_institucional' => isset($_POST['texto_institucional']) ? $_POST['texto_institucional'] : null,
                'redes_sociais' => $redesSociais,
                'formulario_contato_ativo' => isset($_POST['formulario_contato_ativo']) ? 1 : 0,
            ];

            if ($erro === null) {
                $this->contatos->salvar($contato);

                flashSucesso('Contato atualizado.');
                $this->redirecionar('contatosConcurso/index');
                return;
            }
        }

        $this->renderizar('admin/contatos_concurso/form', [
            'contato' => $contato !== null ? $contato : $this->contatos->buscar(),
            'erro' => $erro,
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
