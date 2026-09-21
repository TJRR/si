<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EventoCampoInscricaoRepository;
use App\Repositories\SemanaInovacaoRepository;

/**
 * Fase 39 (correcao pos-teste): sub-aba "Formulario de inscricao" do
 * evento - o Admin define os campos configuraveis que aparecem na
 * inscricao publica, alem do campo "Documento" (estrutural, sempre
 * presente, nunca editavel aqui).
 */
class EventoFormularioAdminController extends Controller
{
    public const TIPOS = [
        'texto' => 'Texto',
        'lista_opcoes' => 'Lista de opções',
    ];

    private $eventos;
    private $campos;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->campos = new EventoCampoInscricaoRepository();
    }

    public function index($eventoId)
    {
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $this->renderizar('admin/evento_formulario/index', [
            'evento' => $evento,
            'campos' => $this->campos->listarPorEvento($eventoId),
        ], 'Formulário de inscrição: ' . $evento['nome'], ['tipo' => 'eventoFormulario', 'id' => (int) $eventoId]);
    }

    public function novo($eventoId)
    {
        RoleMiddleware::exigir(['administrador']);
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        $erro = null;
        $campo = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->dadosDoFormulario();
            $erro = $this->validar($dados);

            if ($erro === null) {
                $this->campos->criar($eventoId, $dados);
                $this->redirecionar('eventoFormulario/index/' . $eventoId);
                return;
            }

            $campo = $dados;
        }

        $this->renderizar('admin/evento_formulario/form', [
            'erro' => $erro,
            'evento' => $evento,
            'campo' => $campo,
        ], 'Novo campo: ' . $evento['nome']);
    }

    public function editar($id)
    {
        RoleMiddleware::exigir(['administrador']);
        $campo = $this->campos->buscarPorId($id);

        if ($campo === null) {
            http_response_code(404);
            exit('Campo não encontrado.');
        }

        $evento = $this->eventos->buscarPorId($campo['evento_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->dadosDoFormulario();
            $erro = $this->validar($dados);

            if ($erro === null) {
                $this->campos->atualizar($id, $dados);
                $campo = $this->campos->buscarPorId($id);
            } else {
                $campo = $dados + ['id' => $campo['id'], 'evento_id' => $campo['evento_id']];
            }
        }

        $this->renderizar('admin/evento_formulario/form', [
            'erro' => $erro,
            'evento' => $evento,
            'campo' => $campo,
        ], 'Editar campo: ' . $evento['nome']);
    }

    public function mover()
    {
        RoleMiddleware::exigir(['administrador']);
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $eventoId = (int) (isset($_POST['evento_id']) ? $_POST['evento_id'] : 0);
        $direcao = isset($_POST['direcao']) && $_POST['direcao'] === 'baixo' ? 'baixo' : 'cima';

        $this->campos->mover($id, $direcao);
        $this->redirecionar('eventoFormulario/index/' . $eventoId);
    }

    public function remover()
    {
        RoleMiddleware::exigir(['administrador']);
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $eventoId = (int) (isset($_POST['evento_id']) ? $_POST['evento_id'] : 0);

        $this->campos->remover($id);
        flashSucesso('Campo removido.');
        $this->redirecionar('eventoFormulario/index/' . $eventoId);
    }

    private function dadosDoFormulario()
    {
        $tipo = isset($_POST['tipo']) && isset(self::TIPOS[$_POST['tipo']]) ? $_POST['tipo'] : 'texto';
        $opcoesTexto = isset($_POST['opcoes']) ? $_POST['opcoes'] : '';

        return [
            'rotulo' => trim(isset($_POST['rotulo']) ? $_POST['rotulo'] : ''),
            'tipo' => $tipo,
            'obrigatorio' => isset($_POST['obrigatorio']) ? 1 : 0,
            'texto_ajuda' => $this->campoOuNulo('texto_ajuda'),
            'config' => $this->montarConfig($tipo, $opcoesTexto),
        ];
    }

    private function montarConfig($tipo, $opcoesTexto)
    {
        if ($tipo !== 'lista_opcoes') {
            return null;
        }

        $opcoes = array_values(array_filter(array_map('trim', explode("\n", (string) $opcoesTexto)), function ($opcao) {
            return $opcao !== '';
        }));

        return ['opcoes' => $opcoes];
    }

    private function validar(array $dados)
    {
        if ($dados['rotulo'] === '') {
            return 'Informe o rótulo do campo.';
        }

        if ($dados['tipo'] === 'lista_opcoes' && empty($dados['config']['opcoes'])) {
            return 'Informe ao menos uma opção da lista.';
        }

        return null;
    }

    private function campoOuNulo($chave)
    {
        $valor = trim(isset($_POST[$chave]) ? $_POST[$chave] : '');

        return $valor !== '' ? $valor : null;
    }
}
