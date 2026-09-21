<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TrabalhoConfigRepository;
use App\Repositories\TrabalhoCriterioRepository;
use App\Repositories\TrabalhoDesignacaoRepository;
use App\Repositories\TrabalhoNotaRepository;
use App\Repositories\TrabalhoRepository;
use App\Services\TrabalhoArquivoValidador;

/**
 * Fase 49: tela do avaliador avulso de Trabalhos - modulo `avaliacaoTrabalhos`,
 * deliberadamente separado de `avaliacao/*` (Concurso). Autorizacao
 * conferida direto contra trabalho_designacoes, nunca via
 * Auth::possuiPerfil() (ver decisao de arquitetura confirmada no plano
 * desta fase). sigilo_cego do evento decide, em cada acao, se os dados de
 * autoria sao buscados/expostos - a ocultacao acontece na propria
 * consulta (TrabalhoRepository::buscarParaAvaliador()), nao so' filtrada
 * na view.
 */
class TrabalhoAvaliacaoController extends Controller
{
    private $eventos;
    private $config;
    private $designacoes;
    private $trabalhos;
    private $criterios;
    private $notas;

    public function __construct()
    {
        if (!Auth::autenticado()) {
            $this->redirecionar('auth/login');
            exit;
        }

        $this->eventos = new SemanaInovacaoRepository();
        $this->config = new TrabalhoConfigRepository();
        $this->designacoes = new TrabalhoDesignacaoRepository();
        $this->trabalhos = new TrabalhoRepository();
        $this->criterios = new TrabalhoCriterioRepository();
        $this->notas = new TrabalhoNotaRepository();
    }

    public function index()
    {
        $designacoes = $this->designacoes->listarTrabalhosDesignadosParaUsuario(Auth::usuarioId());

        $eventosComSigilo = [];

        foreach ($designacoes as $designacao) {
            $eventoId = (int) $designacao['evento_id'];

            if (isset($eventosComSigilo[$eventoId])) {
                continue;
            }

            $config = $this->config->buscarPorEvento($eventoId);
            $sigiloCego = $config !== null && (int) $config['sigilo_cego'] === 1;
            $eventosComSigilo[$eventoId] = $sigiloCego;

            if ($sigiloCego) {
                $this->trabalhos->garantirNumerosSigilo($eventoId);
            }
        }

        $lista = [];

        foreach ($designacoes as $designacao) {
            $sigiloCego = $eventosComSigilo[(int) $designacao['evento_id']];
            $trabalho = $this->trabalhos->buscarParaAvaliador($designacao['trabalho_id'], $sigiloCego);

            if ($trabalho === null) {
                continue;
            }

            $criteriosNotados = $this->notas->contarCriteriosNotados($designacao['designacao_id']);
            $totalCriterios = count($this->criterios->listarPorEvento($designacao['evento_id']));

            $lista[] = [
                'designacao_id' => $designacao['designacao_id'],
                'trabalho' => $trabalho,
                'sigilo_cego' => $sigiloCego,
                'completo' => $totalCriterios > 0 && $criteriosNotados >= $totalCriterios,
            ];
        }

        $this->renderizar('avaliacaoTrabalhos/index', ['designacoes' => $lista], 'Trabalhos para avaliar');
    }

    private function autorizarDesignacao($trabalhoId)
    {
        $designacao = $this->designacoes->buscarPorTrabalhoEUsuario($trabalhoId, Auth::usuarioId());

        if ($designacao === null) {
            http_response_code(403);
            exit('Você não está designado para avaliar este trabalho.');
        }

        return $designacao;
    }

    public function notar($trabalhoId)
    {
        $designacao = $this->autorizarDesignacao($trabalhoId);

        $config = null;
        $trabalhoBase = $this->trabalhos->buscarPorId($trabalhoId);

        if ($trabalhoBase !== null) {
            $config = $this->config->buscarPorEvento($trabalhoBase['evento_id']);
        }

        $sigiloCego = $config !== null && (int) $config['sigilo_cego'] === 1;

        if ($sigiloCego) {
            $this->trabalhos->garantirNumerosSigilo($trabalhoBase['evento_id']);
        }

        $trabalho = $this->trabalhos->buscarParaAvaliador($trabalhoId, $sigiloCego);

        if ($trabalho === null) {
            http_response_code(404);
            exit('Trabalho não encontrado.');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $criterios = $this->criterios->listarPorEvento($trabalho['evento_id']);

            foreach ($criterios as $criterio) {
                $campo = 'nota_' . $criterio['id'];

                if (!isset($_POST[$campo]) || $_POST[$campo] === '') {
                    continue;
                }

                // Fase 49B, achado do usuário: o modo "campo numérico" da
                // tela aceita vírgula como separador decimal (convenção
                // brasileira) - normaliza antes de converter, (float) do
                // PHP não entende vírgula.
                $nota = (float) str_replace(',', '.', $_POST[$campo]);
                $nota = max(0, min((float) $criterio['nota_maxima'], $nota));
                $this->notas->salvar($designacao['id'], $criterio['id'], $nota);
            }

            flashSucesso('Notas salvas.');
            $this->redirecionar('avaliacaoTrabalhos/notar/' . $trabalhoId);
            return;
        }

        $this->renderizar('avaliacaoTrabalhos/notar', [
            'trabalho' => $trabalho,
            'sigiloCego' => $sigiloCego,
            'criterios' => $this->criterios->listarPorEvento($trabalho['evento_id']),
            'notasLancadas' => $this->indexarPorCriterio($this->notas->listarPorDesignacao($designacao['id'])),
            // Fase 49B, achado do usuário: resumo dos critérios de
            // avaliação (edital), editável pelo Admin na aba
            // Configurações - null enquanto ele não preencher, a view usa
            // um resumo montado a partir de $criterios como reserva.
            'criteriosResumoHtml' => $config !== null && !empty($config['criterios_resumo_html']) ? $config['criterios_resumo_html'] : null,
        ], 'Avaliar trabalho');
    }

    private function indexarPorCriterio(array $notas)
    {
        $indexado = [];

        foreach ($notas as $nota) {
            $indexado[(int) $nota['criterio_id']] = $nota['nota'];
        }

        return $indexado;
    }

    /**
     * Sempre serve arquivo_avaliacao_path (versao sem identificacao),
     * NUNCA arquivo_publicacao_path - mesmo com sigilo_cego desligado, o
     * download do avaliador so' existe para o metodo de documento, que so'
     * grava 1 arquivo nesse caso (arquivo_avaliacao_path recebe o unico
     * arquivo enviado quando nao ha 2 versoes).
     */
    public function arquivo($trabalhoId)
    {
        $this->autorizarDesignacao($trabalhoId);

        $trabalho = $this->trabalhos->buscarPorId($trabalhoId);

        if ($trabalho === null || empty($trabalho['arquivo_avaliacao_path'])) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        $caminho = TrabalhoArquivoValidador::caminhoFisico($trabalho['arquivo_avaliacao_path']);

        if ($caminho === null) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        header('Content-Type: ' . TrabalhoArquivoValidador::contentTypePara($trabalho['arquivo_avaliacao_path']));
        header('Content-Disposition: attachment; filename="trabalho-' . (int) $trabalhoId . '.' . pathinfo($caminho, PATHINFO_EXTENSION) . '"');
        header('Content-Length: ' . filesize($caminho));
        readfile($caminho);
        exit;
    }
}
