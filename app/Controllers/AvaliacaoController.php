<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\AvaliadorDesignacaoRepository;
use App\Repositories\CampoDinamicoRepository;
use App\Repositories\ConcursoRepository;
use App\Repositories\CriterioAvaliacaoRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\NotaLancadaRepository;
use App\Repositories\ResultadoEtapaRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TemaDesafioRepository;
use App\Repositories\TrilhaRepository;

class AvaliacaoController extends Controller
{
    private $concursos;
    private $trilhas;
    private $etapas;
    private $criterios;
    private $submissoes;
    private $designacoes;
    private $notas;
    private $resultadosEtapa;
    private $camposDinamicos;
    private $temas;

    public function __construct()
    {
        RoleMiddleware::exigirEmQualquerConcurso(['avaliador']);
        $this->concursos = new ConcursoRepository();
        $this->trilhas = new TrilhaRepository();
        $this->etapas = new EtapaRepository();
        $this->criterios = new CriterioAvaliacaoRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->designacoes = new AvaliadorDesignacaoRepository();
        $this->notas = new NotaLancadaRepository();
        $this->resultadosEtapa = new ResultadoEtapaRepository();
        $this->camposDinamicos = new CampoDinamicoRepository();
        $this->temas = new TemaDesafioRepository();
    }

    public function index()
    {
        $hoje = date('Y-m-d');
        $etapasDisponiveis = [];

        foreach ($this->concursos->listar() as $concurso) {
            if (!Auth::temPerfil('avaliador', $concurso['id'])) {
                continue;
            }

            foreach ($this->trilhas->listarPorConcurso($concurso['id']) as $trilha) {
                foreach ($this->etapas->listarPorTrilha($trilha['id']) as $etapa) {
                    if ($this->criterios->contarPorEtapa($etapa['id']) === 0) {
                        continue;
                    }

                    if ($etapa['data_inicio'] !== null && $hoje < $etapa['data_inicio']) {
                        continue;
                    }

                    if ($etapa['data_fim'] !== null && $hoje > $etapa['data_fim']) {
                        continue;
                    }

                    $etapa['trilha_nome'] = $trilha['nome'];
                    $etapa['concurso_nome'] = $concurso['nome'];
                    $etapasDisponiveis[] = $etapa;
                }
            }
        }

        $this->renderizar('avaliacao/index', [
            'etapas' => $etapasDisponiveis,
        ], 'Avaliação — minhas etapas');
    }

    public function submissoes($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);

        if (!Auth::temPerfil('avaliador', $trilha['concurso_id'])) {
            http_response_code(403);
            exit('Acesso negado: você não é avaliador deste concurso.');
        }

        $todasDaEtapa = $this->submissoes->listarPorEtapa($etapaId);

        if ($etapa['modo_designacao'] === 'aberto') {
            $lista = $todasDaEtapa;
        } else {
            $designadasIds = array_map('intval', $this->designacoes->listarSubmissoesDesignadasNaEtapa(Auth::usuarioId(), $etapaId));
            $lista = array_values(array_filter($todasDaEtapa, function ($submissao) use ($designadasIds) {
                return in_array((int) $submissao['id'], $designadasIds, true);
            }));
        }

        $totalCriterios = $this->criterios->contarPorEtapa($etapaId);

        foreach ($lista as &$submissao) {
            $criteriosNotados = $this->notas->contarNotasPorUsuario($submissao['id'], Auth::usuarioId());
            $submissao['criterios_notados'] = $criteriosNotados;
            $submissao['total_criterios'] = $totalCriterios;
            $submissao['avaliacao_completa'] = $totalCriterios > 0 && $criteriosNotados >= $totalCriterios;
            $submissao['resultado_publicado'] = $this->resultadosEtapa->buscarPorSubmissaoEEtapa($submissao['id'], $etapaId) !== null;
        }
        unset($submissao);

        $this->renderizar('avaliacao/submissoes', [
            'etapa' => $etapa,
            'trilha' => $trilha,
            'submissoes' => $lista,
            'sigiloCego' => $etapa['modo_sigilo'] === 'cego',
        ], 'Submissões — ' . $etapa['nome']);
    }

    public function notar($submissaoId)
    {
        $contexto = $this->carregarSubmissaoAutorizada($submissaoId);
        $submissao = $contexto['submissao'];
        $etapa = $contexto['etapa'];

        $resultadoPublicado = $this->resultadosEtapa->buscarPorSubmissaoEEtapa($submissaoId, $etapa['id']) !== null;
        $criteriosDaEtapa = $this->criterios->listarPorEtapa($etapa['id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$resultadoPublicado) {
            foreach ($criteriosDaEtapa as $criterio) {
                $bruto = isset($_POST['nota'][$criterio['id']]) ? str_replace(',', '.', $_POST['nota'][$criterio['id']]) : null;

                if ($bruto === null || $bruto === '') {
                    continue;
                }

                $nota = (float) $bruto;

                if ($nota < (float) $criterio['escala_min'] || $nota > (float) $criterio['escala_max']) {
                    $erro = 'A nota de "' . $criterio['nome'] . '" deve estar entre ' .
                        $criterio['escala_min'] . ' e ' . $criterio['escala_max'] . '.';
                    break;
                }

                $this->notas->salvar($submissaoId, $criterio['id'], Auth::usuarioId(), $nota);
            }

            if ($erro === null) {
                $this->redirecionar('avaliacao/submissoes/' . (int) $etapa['id']);
                return;
            }
        }

        $notasAtuais = $this->notas->listarPorSubmissaoEUsuario($submissaoId, Auth::usuarioId());

        $this->renderizar('avaliacao/notar', [
            'submissao' => $submissao,
            'etapa' => $etapa,
            'criterios' => $criteriosDaEtapa,
            'notasAtuais' => $notasAtuais,
            'resultadoPublicado' => $resultadoPublicado,
            'sigiloCego' => $etapa['modo_sigilo'] === 'cego',
            'erro' => $erro,
            'conteudoSubmissao' => $this->montarConteudoSubmissao($submissao),
        ], 'Lançar notas — Submissão #' . (int) $submissaoId);
    }

    public function baixarArquivo($submissaoId, $campoId)
    {
        $contexto = $this->carregarSubmissaoAutorizada($submissaoId);
        $submissao = $contexto['submissao'];

        $dados = json_decode((string) $submissao['dados_json'], true);
        $valores = isset($dados['campos']) && is_array($dados['campos']) ? $dados['campos'] : [];
        $valor = isset($valores[(string) $campoId]) ? $valores[(string) $campoId] : null;

        if (!is_array($valor) || !isset($valor['caminho_relativo'])) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        $baseDir = realpath(__DIR__ . '/../../storage/uploads');
        $caminho = realpath($baseDir . '/' . $valor['caminho_relativo']);

        if ($caminho === false || strpos($caminho, $baseDir . DIRECTORY_SEPARATOR) !== 0 || !is_file($caminho)) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . basename($valor['nome_original']) . '"');
        header('Content-Length: ' . filesize($caminho));
        readfile($caminho);
        exit;
    }

    private function carregarSubmissaoAutorizada($submissaoId)
    {
        $submissao = $this->submissoes->buscarPorId($submissaoId);

        if ($submissao === null) {
            http_response_code(404);
            exit('Submissão não encontrada.');
        }

        $etapa = $this->etapas->buscarPorId($submissao['etapa_id']);
        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);

        if (!Auth::temPerfil('avaliador', $trilha['concurso_id'])) {
            http_response_code(403);
            exit('Acesso negado: você não é avaliador deste concurso.');
        }

        if ($etapa['modo_designacao'] !== 'aberto' && !$this->designacoes->existeDesignacao($submissaoId, Auth::usuarioId())) {
            http_response_code(403);
            exit('Acesso negado: esta submissão não foi designada a você.');
        }

        return ['submissao' => $submissao, 'etapa' => $etapa, 'trilha' => $trilha];
    }

    private function montarConteudoSubmissao(array $submissao)
    {
        if ($submissao['formulario_dinamico_id'] === null) {
            return [];
        }

        $campos = $this->camposDinamicos->listarPorFormulario($submissao['formulario_dinamico_id']);
        $dados = json_decode((string) $submissao['dados_json'], true);
        $valores = isset($dados['campos']) && is_array($dados['campos']) ? $dados['campos'] : [];

        $conteudo = [];

        foreach ($campos as $campo) {
            $valor = array_key_exists((string) $campo['id'], $valores) ? $valores[(string) $campo['id']] : null;

            if ($campo['tipo'] === 'selecao_tema_desafio' && $valor !== null) {
                $tema = $this->temas->buscarPorId((int) $valor);
                $valor = $tema !== null ? $tema['nome'] : $valor;
            }

            $conteudo[] = ['campo' => $campo, 'valor' => $valor];
        }

        return $conteudo;
    }
}
