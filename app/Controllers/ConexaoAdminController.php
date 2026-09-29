<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConexaoConfigRepository;
use App\Repositories\ConexaoRepository;
use App\Repositories\SemanaInovacaoRepository;

/**
 * Fase 55: Conexoes entre participantes do Evento (no da arvore irmao de
 * Atividades, Trabalhos e Estandes, sem no por conexao). Suporte le tudo,
 * como nos demais modulos do Evento; toda gravacao exige Administrador.
 *
 * O acompanhamento mostra so' numeros do evento: nao ha' lista de quem se
 * conectou com quem, porque o par e' dado pessoal de terceiro - a lista
 * nominal existe apenas para cada participante, na tela dele. Classificacao
 * e ranking sao da Fase 58.
 */
class ConexaoAdminController extends Controller
{
    private $eventos;
    private $conexoes;
    private $config;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador', 'suporte']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->conexoes = new ConexaoRepository();
        $this->config = new ConexaoConfigRepository();
    }

    private function eventoOu404($eventoId)
    {
        $evento = $this->eventos->buscarPorId($eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        return $evento;
    }

    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        $this->renderizar('admin/conexoes/index', [
            'evento' => $evento,
            'config' => $this->config->vigente($id),
            'totalConexoes' => $this->conexoes->contarPorEvento($id),
            'totalParticipantes' => $this->conexoes->contarParticipantesConectados($id),
            'totalPontos' => $this->conexoes->somarPontosPorEvento($id),
            'porDia' => $this->conexoes->contarPorDia($id),
            'podeEditar' => \App\Core\Auth::possuiPerfil('administrador'),
        ], 'Conexões: ' . $evento['nome'], ['tipo' => 'conexoes', 'id' => $id]);
    }

    public function configuracoes($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $id = (int) $evento['id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            RoleMiddleware::exigir(['administrador']);

            $this->config->salvar($id, [
                'ativo' => !empty($_POST['ativo']) ? 1 : 0,
                'pontos_por_conexao' => max(0, (int) (isset($_POST['pontos_por_conexao']) ? $_POST['pontos_por_conexao'] : 0)),
                'teto_conexoes_pontuadas' => max(0, (int) (isset($_POST['teto_conexoes_pontuadas']) ? $_POST['teto_conexoes_pontuadas'] : 0)),
            ]);

            flashSucesso('Configurações de Conexões salvas.');
            $this->redirecionar('conexoes/configuracoes/' . $id);
            return;
        }

        $this->renderizar('admin/conexoes/configuracoes', [
            'evento' => $evento,
            'config' => $this->config->vigente($id),
            'podeEditar' => \App\Core\Auth::possuiPerfil('administrador'),
        ], 'Configurações de Conexões: ' . $evento['nome'], ['tipo' => 'conexoesConfiguracoes', 'id' => $id]);
    }
}
