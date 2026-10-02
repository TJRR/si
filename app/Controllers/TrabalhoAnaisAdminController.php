<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EventoAnaisRepository;
use App\Repositories\EventoAnaisVersaoRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TrabalhoConfigRepository;
use App\Services\ArquivoPrivadoService;
use App\Services\ArquivoService;
use App\Services\EventoAnaisAvisoService;
use App\Services\EventoAnaisService;

/**
 * Fase 53: as duas sub-abas de Anais dentro de Trabalhos ("Anais", com a
 * identificacao, as versoes do PDF e a publicacao, e "Trabalhos nos Anais",
 * com a lista de quem consta no volume). So' o Administrador: publicar torna
 * o arquivo acessivel a qualquer pessoa com o endereco e avisa autores.
 * O volume e' montado fora do sistema e chega como um unico PDF.
 */
class TrabalhoAnaisAdminController extends Controller
{
    private $eventos;
    private $anais;
    private $versoes;
    private $servico;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->anais = new EventoAnaisRepository();
        $this->versoes = new EventoAnaisVersaoRepository();
        $this->servico = new EventoAnaisService();
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

    private function exigirPost($eventoId)
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirecionar('trabalhoAnais/index/' . (int) $eventoId);
            exit;
        }
    }

    /**
     * Erro esperado (regra de negocio) vira mensagem para a tela; falha de
     * banco ou qualquer outra nunca mostra o texto tecnico ao Administrador.
     * PDOException herda de RuntimeException, por isso a checagem explicita.
     */
    private function mensagemDoErro(\Throwable $e)
    {
        if ($e instanceof \RuntimeException && !($e instanceof \PDOException)) {
            return $e->getMessage();
        }

        error_log('[Anais] ' . get_class($e) . ': ' . $e->getMessage());

        return 'Não foi possível concluir a operação agora. Tente de novo e, se persistir, avise o suporte técnico.';
    }

    public function index($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $erro = null;
        $valores = null;
        $atual = $this->anais->buscarPorEvento($eventoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'salvar_dados') {
            $erro = $this->servico->salvarDados($eventoId, $_POST);

            if ($erro === null) {
                flashSucesso('Dados dos Anais salvos.');
                $this->redirecionar('trabalhoAnais/index/' . $eventoId);
                return;
            }

            $valores = $this->servico->validarDados($_POST, $atual)['dados'];
        }

        $publicado = $this->anais->buscarPublicadoParaParticipante($eventoId);
        $enderecoPublico = null;

        if ($publicado !== null) {
            $esquema = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
            $enderecoPublico = $esquema . '://' . $host . $publicado['url'];
        }

        $this->renderizar('admin/trabalhos_anais/index', [
            'evento' => $evento,
            'anais' => $atual,
            'valores' => $valores,
            'erro' => $erro,
            'versoes' => $this->versoes->listarPorEvento($eventoId),
            'publicado' => $publicado,
            'enderecoPublico' => $enderecoPublico,
            'impedimento' => $this->anais->motivoQueImpedePublicar($eventoId),
            'primeiraPublicacao' => !$this->versoes->jaFoiPublicadaAlguma($eventoId),
            'limiteMB' => ArquivoService::limiteMaximoMB(),
        ], 'Anais: ' . $evento['nome'], ['tipo' => 'trabalhosAnais', 'id' => (int) $eventoId]);
    }

    public function enviarVersao($eventoId)
    {
        $this->eventoOu404($eventoId);
        $this->exigirPost($eventoId);

        try {
            $numero = $this->servico->enviarVersao(
                $eventoId,
                isset($_FILES['arquivo']) ? $_FILES['arquivo'] : [],
                isset($_POST['observacao']) ? $_POST['observacao'] : '',
                Auth::usuarioId()
            );
            flashSucesso('Versão ' . (int) $numero . ' enviada. Ela ainda não está publicada: confira o arquivo e publique quando estiver pronto.');
        } catch (\Throwable $e) {
            flashErro($this->mensagemDoErro($e));
        }

        $this->redirecionar('trabalhoAnais/index/' . $eventoId);
    }

    /**
     * Baixa (abre) o PDF de uma versao, publicada ou nao, pela area privada.
     */
    public function baixar($eventoId, $versaoId = null)
    {
        $this->eventoOu404($eventoId);
        $versao = $this->versoes->buscarDoEvento($eventoId, (int) $versaoId);

        if ($versao === null) {
            http_response_code(404);
            exit('Versão não encontrada.');
        }

        ArquivoPrivadoService::servir($versao['arquivo_path'], 'anais-versao-' . (int) $versao['numero'] . '.pdf');
    }

    public function removerVersao($eventoId)
    {
        $this->eventoOu404($eventoId);
        $this->exigirPost($eventoId);

        try {
            $versao = $this->servico->removerVersao($eventoId, (int) (isset($_POST['id']) ? $_POST['id'] : 0));
            flashSucesso('Versão ' . (int) $versao['numero'] . ' removida.');
        } catch (\Throwable $e) {
            flashErro($this->mensagemDoErro($e));
        }

        $this->redirecionar('trabalhoAnais/index/' . $eventoId);
    }

    public function publicar($eventoId)
    {
        $this->eventoOu404($eventoId);
        $this->exigirPost($eventoId);

        try {
            $resultado = $this->servico->publicar($eventoId, (int) (isset($_POST['id']) ? $_POST['id'] : 0), Auth::usuarioId());
        } catch (\Throwable $e) {
            flashErro($this->mensagemDoErro($e));
            $this->redirecionar('trabalhoAnais/index/' . $eventoId);
            return;
        }

        if (empty($_POST['avisar_autores'])) {
            flashSucesso('Anais publicados (versão ' . (int) $resultado['numero'] . '). Nenhum aviso foi enviado aos autores.');
        } else {
            $modo = isset($_POST['modo_aviso']) && $_POST['modo_aviso'] === 'todos' ? 'todos' : 'novos';
            $this->avisarAutores($eventoId, (int) $resultado['numero'], $modo);
        }

        $this->redirecionar('trabalhoAnais/index/' . $eventoId);
    }

    public function despublicar($eventoId)
    {
        $this->eventoOu404($eventoId);
        $this->exigirPost($eventoId);

        try {
            $this->servico->despublicar($eventoId, Auth::usuarioId());
            flashSucesso('Anais despublicados: o botão some da página e do aplicativo. As versões continuam guardadas.');
        } catch (\Throwable $e) {
            flashErro($this->mensagemDoErro($e));
        }

        $this->redirecionar('trabalhoAnais/index/' . $eventoId);
    }

    /**
     * Trabalhos nos Anais: lista dos aprovados, com a marcacao de quem consta
     * no volume (todos por padrao; so' quem sai fica gravado).
     */
    public function trabalhos($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->servico->salvarSelecao($eventoId, $_POST, Auth::usuarioId());
                flashSucesso('Lista de trabalhos dos Anais salva.');
            } catch (\Throwable $e) {
                flashErro($this->mensagemDoErro($e));
            }

            $this->redirecionar('trabalhoAnais/trabalhos/' . $eventoId);
            return;
        }

        $config = (new TrabalhoConfigRepository())->buscarPorEvento($eventoId);
        $resultadoPublicado = $config !== null && !empty($config['resultado_publicado_em']);
        $estaPublicado = $this->anais->estaPublicado($eventoId);
        $linhas = $resultadoPublicado ? $this->servico->montarSelecao($eventoId) : [];
        $totalApresentados = 0;

        foreach ($linhas as $linha) {
            if ($linha['apresentado']) {
                $totalApresentados++;
            }
        }

        $sugestao = null;

        if (!empty($_GET['sugerir']) && !$estaPublicado && !empty($linhas)) {
            $sugestao = $totalApresentados > 0 ? 'aplicada' : 'sem_marcas';

            if ($sugestao === 'aplicada') {
                foreach ($linhas as $indice => $linha) {
                    if ($linha['selecionado'] && !$linha['apresentado']) {
                        $linhas[$indice]['incluido'] = false;
                        $linhas[$indice]['sugerido'] = true;

                        if ($linha['motivo'] === '') {
                            $linhas[$indice]['motivo'] = 'Trabalho não apresentado';
                        }
                    }
                }
            }
        }

        $this->renderizar('admin/trabalhos_anais/trabalhos', [
            'evento' => $evento,
            'resultadoPublicado' => $resultadoPublicado,
            'estaPublicado' => $estaPublicado,
            'linhas' => $linhas,
            'totalApresentados' => $totalApresentados,
            'sugestao' => $sugestao,
            'casasDecimais' => TrabalhoConfigRepository::casasDecimais($config),
        ], 'Trabalhos nos Anais: ' . $evento['nome'], ['tipo' => 'trabalhosAnaisSelecao', 'id' => (int) $eventoId]);
    }

    /**
     * Os Anais ja foram publicados e confirmados; uma falha ao criar os
     * avisos (sino e e-mail) nunca desfaz a publicacao, so' avisa o
     * Administrador para comunicar os autores por outro meio.
     */
    private function avisarAutores($eventoId, $numeroVersao, $modo)
    {
        try {
            $avisos = (new EventoAnaisAvisoService())->avisarAutores($eventoId, Auth::usuarioId(), $modo, $numeroVersao);

            if ($avisos['trabalhos'] === 0) {
                flashSucesso('Anais publicados (versão ' . $numeroVersao . '). Nenhum trabalho novo desde o último aviso, então ninguém foi avisado de novo.');
                return;
            }

            flashSucesso(
                'Anais publicados (versão ' . $numeroVersao . '). Avisos aos autores: ' . $avisos['sinos'] . ' no aplicativo e '
                . $avisos['emails'] . ' e-mail(s) na fila de envio (saem dez por minuto).'
            );
        } catch (\Throwable $e) {
            error_log('[Anais] falha ao avisar os autores do evento ' . (int) $eventoId . ': ' . $e->getMessage());
            flashAlerta('Anais publicados (versão ' . $numeroVersao . '), mas não foi possível criar os avisos aos autores. Avise-os por outro meio.');
        }
    }
}
