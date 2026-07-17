<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EtapaRepository;
use App\Repositories\NotaLancadaRepository;
use App\Repositories\ResultadoEtapaRepository;
use App\Repositories\ResultadoTrilhaRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TrilhaRepository;
use App\Services\ConteudoSubmissaoService;
use App\Services\ImagemService;
use App\Services\ResultadoEtapaService;
use App\Services\ResultadoTrilhaService;

class ResultadoAdminController extends Controller
{
    private $etapas;
    private $trilhas;
    private $resultadosEtapa;
    private $resultadosTrilha;
    private $submissoes;
    private $notas;
    private $conteudoSubmissao;
    private $servicoEtapa;
    private $servicoTrilha;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->resultadosEtapa = new ResultadoEtapaRepository();
        $this->resultadosTrilha = new ResultadoTrilhaRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->notas = new NotaLancadaRepository();
        $this->conteudoSubmissao = new ConteudoSubmissaoService();
        $this->servicoEtapa = new ResultadoEtapaService();
        $this->servicoTrilha = new ResultadoTrilhaService();
        $this->imagens = new ImagemService();
    }

    /**
     * Fase 18 (4.7) - resumo de destaque + imagem do case vencedor, editado
     * separadamente do calculo/publicacao (que continua 100% automatico).
     */
    public function editarDestaque($resultadoTrilhaId)
    {
        $resultado = $this->resultadosTrilha->buscarPorId($resultadoTrilhaId);

        if ($resultado === null) {
            http_response_code(404);
            exit('Resultado não encontrado.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $imagemPath = $resultado['imagem_destaque_path'];
            $imagemAlt = $resultado['imagem_destaque_alt'];

            try {
                if (!empty($_FILES['imagem_destaque']) && $_FILES['imagem_destaque']['error'] === UPLOAD_ERR_OK) {
                    $alt = trim(isset($_POST['imagem_destaque_alt']) ? $_POST['imagem_destaque_alt'] : '');

                    if ($alt === '') {
                        $erro = 'Informe o texto alternativo (alt) da imagem.';
                    } else {
                        $imagemPath = $this->imagens->salvar($_FILES['imagem_destaque'], 'resultados', 900, 900);
                        $imagemAlt = $alt;
                        $this->imagens->remover($resultado['imagem_destaque_path']);
                    }
                } elseif (isset($_POST['imagem_destaque_alt'])) {
                    $imagemAlt = trim($_POST['imagem_destaque_alt']);
                }
            } catch (\RuntimeException $e) {
                $erro = $e->getMessage();
            }

            if ($erro === null) {
                $this->resultadosTrilha->atualizarDestaque(
                    $resultadoTrilhaId,
                    trim(isset($_POST['resumo_destaque']) ? $_POST['resumo_destaque'] : '') ?: null,
                    $imagemPath,
                    $imagemAlt
                );
                $resultado = $this->resultadosTrilha->buscarPorId($resultadoTrilhaId);
            }
        }

        $trilha = $this->trilhas->buscarPorId($resultado['trilha_id']);

        $this->renderizar('admin/resultados/destaque', [
            'erro' => $erro,
            'trilha' => $trilha,
            'resultado' => $resultado,
        ], 'Destaque do case — ' . $resultado['nome_equipe'], ['tipo' => 'apuracao', 'id' => (int) $resultado['trilha_id']]);
    }

    /**
     * Fase 17 (Bug 5): fragmento para o modal "Ver submissão" na aba
     * Resultado - reaproveita ConteudoSubmissaoService (mesma logica da
     * tela do avaliador). Admin ve' tudo, sem sigilo.
     */
    public function popupSubmissao($submissaoId)
    {
        $submissao = $this->submissoes->buscarPorId($submissaoId);

        if ($submissao === null) {
            http_response_code(404);
            exit('Submissão não encontrada.');
        }

        $this->renderizar('admin/resultados/popup_submissao', [
            'submissao' => $submissao,
            'conteudoSubmissao' => $this->conteudoSubmissao->montar($submissao),
        ]);
    }

    /**
     * Fase 17 (Bug 5): fragmento para o modal "Ver avaliações" - notas de
     * todos os avaliadores designados, agrupadas por avaliador.
     */
    public function popupNotas($submissaoId)
    {
        $submissao = $this->submissoes->buscarPorId($submissaoId);

        if ($submissao === null) {
            http_response_code(404);
            exit('Submissão não encontrada.');
        }

        $notasPorAvaliador = [];

        foreach ($this->notas->listarPorSubmissaoComDetalhes($submissaoId) as $nota) {
            $usuarioId = (int) $nota['usuario_id'];

            if (!isset($notasPorAvaliador[$usuarioId])) {
                $notasPorAvaliador[$usuarioId] = ['avaliador_nome' => $nota['usuario_nome'], 'notas' => []];
            }

            $notasPorAvaliador[$usuarioId]['notas'][] = $nota;
        }

        $this->renderizar('admin/resultados/popup_notas', [
            'notasPorAvaliador' => $notasPorAvaliador,
        ]);
    }

    public function etapa($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $erro = null;
        $ranking = [];
        $publicado = $this->servicoEtapa->jaPublicado($etapaId);

        try {
            $ranking = $publicado
                ? $this->resultadosEtapa->listarPorEtapa($etapaId)
                : $this->servicoEtapa->calcularRanking($etapaId);
        } catch (\RuntimeException $e) {
            $erro = $e->getMessage();
        }

        $this->renderizar('admin/resultados/etapa', [
            'etapa' => $etapa,
            'ranking' => $ranking,
            'publicado' => $publicado,
            'erro' => $erro,
        ], 'Resultado — ' . $etapa['nome'], ['tipo' => 'resultado_etapa', 'id' => (int) $etapaId]);
    }

    public function publicarEtapa()
    {
        $etapaId = (int) (isset($_POST['etapa_id']) ? $_POST['etapa_id'] : 0);

        try {
            $this->servicoEtapa->publicar($etapaId, Auth::usuarioId());
        } catch (\RuntimeException $e) {
            $_SESSION['flash'] = $e->getMessage();
        }

        $this->redirecionar('resultados/etapa/' . $etapaId);
    }

    public function reabrirEtapa()
    {
        $etapaId = (int) (isset($_POST['etapa_id']) ? $_POST['etapa_id'] : 0);
        $this->servicoEtapa->reabrir($etapaId);
        $this->redirecionar('resultados/etapa/' . $etapaId);
    }

    public function trilha($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha não encontrada.');
        }

        $erro = null;
        $ranking = [];
        $publicado = $this->servicoTrilha->jaPublicado($trilhaId);

        try {
            $ranking = $publicado
                ? $this->resultadosTrilha->listarPorTrilha($trilhaId)
                : $this->servicoTrilha->calcularRanking($trilhaId);
        } catch (\RuntimeException $e) {
            $erro = $e->getMessage();
        }

        $this->renderizar('admin/resultados/trilha', [
            'trilha' => $trilha,
            'ranking' => $ranking,
            'publicado' => $publicado,
            'erro' => $erro,
        ], 'Resultado final — ' . $trilha['nome'], ['tipo' => 'apuracao', 'id' => (int) $trilhaId]);
    }

    public function publicarTrilha()
    {
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);

        try {
            $this->servicoTrilha->publicar($trilhaId, Auth::usuarioId());
        } catch (\RuntimeException $e) {
            $_SESSION['flash'] = $e->getMessage();
        }

        $this->redirecionar('apuracao/index/' . $trilhaId);
    }

    public function reabrirTrilha()
    {
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);
        $this->servicoTrilha->reabrir($trilhaId);
        $this->redirecionar('apuracao/index/' . $trilhaId);
    }
}
