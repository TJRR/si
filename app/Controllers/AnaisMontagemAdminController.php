<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EventoAnaisComissaoRepository;
use App\Repositories\EventoAnaisGeracaoRepository;
use App\Repositories\EventoAnaisMontagemRepository;
use App\Repositories\EventoAnaisRepository;
use App\Repositories\EventoAnaisTrabalhoRepository;
use App\Repositories\SemanaInovacaoRepository;
use App\Repositories\TrabalhoConfigRepository;
use App\Services\ArquivoPrivadoService;
use App\Services\ArquivoService;
use App\Services\EventoAnaisMontagemService;
use App\Services\EventoAnaisPdfFinalAvisoService;

/**
 * Fase 54: sub-aba "Montagem dos Anais" de Trabalhos (modulo de rota
 * anaisMontagem). O sistema monta o volume a partir do PDF final de cada
 * trabalho (enviado pelo autor principal no aplicativo), dos dados
 * editoriais e das comissoes; aqui o Administrador so' prepara e pede. A
 * geracao roda na rotina agendada e a versao gerada vai para a aba Anais,
 * onde a publicacao continua como na Fase 53.
 */
class AnaisMontagemAdminController extends Controller
{
    private $eventos;
    private $montagem;
    private $comissoes;
    private $geracoes;
    private $servico;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->eventos = new SemanaInovacaoRepository();
        $this->montagem = new EventoAnaisMontagemRepository();
        $this->comissoes = new EventoAnaisComissaoRepository();
        $this->geracoes = new EventoAnaisGeracaoRepository();
        $this->servico = new EventoAnaisMontagemService();
    }

    private function eventoOu404($eventoId)
    {
        $evento = $this->eventos->buscarPorId((int) $eventoId);

        if ($evento === null) {
            http_response_code(404);
            exit('Evento não encontrado.');
        }

        return $evento;
    }

    private function voltar($eventoId, $bloco = '')
    {
        $this->redirecionar('anaisMontagem/index/' . (int) $eventoId . ($bloco !== '' ? '#' . $bloco : ''));
    }

    private function exigirPost($eventoId, $bloco = '')
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->voltar($eventoId, $bloco);
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
        $this->renderizarTela($this->eventoOu404($eventoId));
    }

    /**
     * $extras: valores digitados e mensagem de erro de um formulario que
     * voltou por validacao (a tela e' mostrada de novo, sem redirecionar,
     * para o texto digitado nao se perder).
     */
    private function renderizarTela(array $evento, array $extras = [])
    {
        $eventoId = (int) $evento['id'];
        $config = (new TrabalhoConfigRepository())->buscarPorEvento($eventoId);
        $resultadoPublicado = $config !== null && !empty($config['resultado_publicado_em']);
        $trabalhos = $resultadoPublicado ? $this->servico->montarTrabalhos($eventoId) : [];

        $dados = array_merge([
            'evento' => $evento,
            'montagem' => $this->montagem->buscarPorEvento($eventoId),
            'anais' => (new EventoAnaisRepository())->buscarPorEvento($eventoId),
            'comissoes' => $this->comissoes->listarComMembros($eventoId),
            'resultadoPublicado' => $resultadoPublicado,
            'trabalhos' => $trabalhos,
            'impedimento' => $this->servico->motivoQueImpedeGerar($eventoId, $trabalhos),
            'geracoes' => $this->geracoes->listarPorEvento($eventoId, 10),
            'limiteCapaMB' => ArquivoService::limiteMaximoMB(),
            'valoresPrazo' => null,
            'erroPrazo' => null,
            'valoresEditorial' => null,
            'erroEditorial' => null,
        ], $extras);

        $this->renderizar('admin/anais_montagem/index', $dados, 'Montagem dos Anais: ' . $evento['nome'], ['tipo' => 'trabalhosAnaisMontagem', 'id' => $eventoId]);
    }

    public function salvarPrazo($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $this->exigirPost($eventoId, 'bloco-prazo');

        try {
            $resultado = $this->servico->salvarPrazo((int) $eventoId, $_POST);
        } catch (\Throwable $e) {
            flashErro($this->mensagemDoErro($e));
            $this->voltar($eventoId, 'bloco-prazo');
            return;
        }

        if ($resultado['erro'] !== null) {
            $this->renderizarTela($evento, ['valoresPrazo' => $resultado['dados'], 'erroPrazo' => $resultado['erro']]);
            return;
        }

        if (empty($_POST['avisar_autores'])) {
            flashSucesso('Prazo e instruções salvos. Nenhum aviso foi enviado aos autores.');
        } else {
            $this->avisarAutores((int) $eventoId);
        }

        $this->voltar($eventoId, 'bloco-prazo');
    }

    /**
     * O prazo ja foi salvo; uma falha ao criar os avisos (sino e e-mail)
     * nunca desfaz isso, so' avisa o Administrador.
     */
    private function avisarAutores($eventoId)
    {
        try {
            $avisos = (new EventoAnaisPdfFinalAvisoService())->avisarAutores($eventoId, Auth::usuarioId());
            flashSucesso(
                'Prazo e instruções salvos. Avisos aos autores principais: ' . $avisos['sinos'] . ' no aplicativo e '
                . $avisos['emails'] . ' e-mail(s) na fila de envio (saem dez por minuto).'
            );
        } catch (\Throwable $e) {
            if ($e instanceof \RuntimeException && !($e instanceof \PDOException)) {
                flashAlerta('Prazo e instruções salvos, mas nenhum aviso foi enviado: ' . $e->getMessage());
                return;
            }

            error_log('[Anais] falha ao avisar os autores sobre a versao final do evento ' . (int) $eventoId . ': ' . $e->getMessage());
            flashAlerta('Prazo e instruções salvos, mas não foi possível criar os avisos aos autores. Avise-os por outro meio.');
        }
    }

    public function salvarEditorial($eventoId)
    {
        $evento = $this->eventoOu404($eventoId);
        $this->exigirPost($eventoId, 'bloco-editorial');

        try {
            $resultado = $this->servico->salvarEditorial((int) $eventoId, $_POST);
        } catch (\Throwable $e) {
            flashErro($this->mensagemDoErro($e));
            $this->voltar($eventoId, 'bloco-editorial');
            return;
        }

        if ($resultado['erro'] !== null) {
            $this->renderizarTela($evento, ['valoresEditorial' => $resultado['dados'], 'erroEditorial' => $resultado['erro']]);
            return;
        }

        flashSucesso('Dados editoriais salvos.');
        $this->voltar($eventoId, 'bloco-editorial');
    }

    public function enviarCapa($eventoId)
    {
        $this->eventoOu404($eventoId);
        $this->exigirPost($eventoId, 'bloco-editorial');

        try {
            $this->servico->enviarCapa((int) $eventoId, isset($_FILES['capa']) ? $_FILES['capa'] : []);
            flashSucesso('Capa enviada. Ela entra no volume no lugar da capa simples.');
        } catch (\Throwable $e) {
            flashErro($this->mensagemDoErro($e));
        }

        $this->voltar($eventoId, 'bloco-editorial');
    }

    public function removerCapa($eventoId)
    {
        $this->eventoOu404($eventoId);
        $this->exigirPost($eventoId, 'bloco-editorial');

        try {
            if ($this->servico->removerCapa((int) $eventoId)) {
                flashSucesso('Capa removida. O volume volta a usar a capa simples gerada pelo sistema.');
            } else {
                flashAlerta('Não havia capa enviada.');
            }
        } catch (\Throwable $e) {
            flashErro($this->mensagemDoErro($e));
        }

        $this->voltar($eventoId, 'bloco-editorial');
    }

    public function baixarCapa($eventoId)
    {
        $this->eventoOu404($eventoId);
        $montagem = $this->montagem->buscarPorEvento((int) $eventoId);

        if ($montagem === null || empty($montagem['capa_path'])) {
            http_response_code(404);
            exit('Capa não encontrada.');
        }

        ArquivoPrivadoService::servir($montagem['capa_path'], 'capa-anais.pdf');
    }

    /**
     * PDF final enviado pelo autor, so' se o registro for deste evento.
     */
    public function baixarPdfTrabalho($eventoId, $trabalhoId = null)
    {
        $this->eventoOu404($eventoId);
        $registro = (new EventoAnaisTrabalhoRepository())->buscarPorTrabalho((int) $trabalhoId);

        if ($registro === null || (int) $registro['evento_id'] !== (int) $eventoId || empty($registro['arquivo_path'])) {
            http_response_code(404);
            exit('Arquivo não encontrado.');
        }

        ArquivoPrivadoService::servir($registro['arquivo_path'], 'trabalho-' . (int) $trabalhoId . '-versao-final.pdf');
    }

    public function novaComissao($eventoId)
    {
        $this->eventoOu404($eventoId);
        $this->exigirPost($eventoId, 'bloco-comissoes');

        try {
            $this->servico->criarComissao((int) $eventoId, isset($_POST['nome']) ? $_POST['nome'] : '');
            flashSucesso('Comissão incluída.');
        } catch (\Throwable $e) {
            flashErro($this->mensagemDoErro($e));
        }

        $this->voltar($eventoId, 'bloco-comissoes');
    }

    public function editarComissao($eventoId, $id = null)
    {
        $evento = $this->eventoOu404($eventoId);
        $comissao = $this->comissoes->buscarDoEvento((int) $eventoId, (int) $id);

        if ($comissao === null) {
            http_response_code(404);
            exit('Comissão não encontrada.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->servico->atualizarComissao((int) $eventoId, (int) $id, isset($_POST['nome']) ? $_POST['nome'] : '');
                flashSucesso('Comissão atualizada.');
                $this->voltar($eventoId, 'bloco-comissoes');
                return;
            } catch (\Throwable $e) {
                $erro = $this->mensagemDoErro($e);
                $comissao['nome'] = isset($_POST['nome']) ? (string) $_POST['nome'] : $comissao['nome'];
            }
        }

        $this->renderizar('admin/anais_montagem/comissao_form', [
            'evento' => $evento,
            'comissao' => $comissao,
            'erro' => $erro,
        ], 'Editar comissão: ' . $evento['nome'], ['tipo' => 'trabalhosAnaisMontagem', 'id' => (int) $eventoId]);
    }

    public function removerComissao($eventoId)
    {
        $this->eventoOu404($eventoId);
        $this->exigirPost($eventoId, 'bloco-comissoes');

        try {
            $this->servico->removerComissao((int) $eventoId, (int) (isset($_POST['id']) ? $_POST['id'] : 0));
            flashSucesso('Comissão removida, com os seus membros.');
        } catch (\Throwable $e) {
            flashErro($this->mensagemDoErro($e));
        }

        $this->voltar($eventoId, 'bloco-comissoes');
    }

    public function reordenarComissoes($eventoId)
    {
        $this->eventoOu404($eventoId);

        $this->responderReordenacao(function (array $ids) use ($eventoId) {
            $this->comissoes->reordenar((int) $eventoId, $ids);

            return true;
        });
    }

    public function novoMembro($eventoId)
    {
        $this->eventoOu404($eventoId);
        $this->exigirPost($eventoId, 'bloco-comissoes');

        try {
            $this->servico->criarMembro((int) $eventoId, $_POST);
            flashSucesso('Membro incluído.');
        } catch (\Throwable $e) {
            flashErro($this->mensagemDoErro($e));
        }

        $this->voltar($eventoId, 'bloco-comissoes');
    }

    public function editarMembro($eventoId, $id = null)
    {
        $evento = $this->eventoOu404($eventoId);
        $membro = $this->comissoes->buscarMembroDoEvento((int) $eventoId, (int) $id);

        if ($membro === null) {
            http_response_code(404);
            exit('Membro não encontrado.');
        }

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->servico->atualizarMembro((int) $eventoId, (int) $id, $_POST);
                flashSucesso('Membro atualizado.');
                $this->voltar($eventoId, 'bloco-comissoes');
                return;
            } catch (\Throwable $e) {
                $erro = $this->mensagemDoErro($e);

                foreach (['nome', 'funcao', 'instituicao'] as $campo) {
                    $membro[$campo] = isset($_POST[$campo]) ? (string) $_POST[$campo] : $membro[$campo];
                }
            }
        }

        $this->renderizar('admin/anais_montagem/membro_form', [
            'evento' => $evento,
            'membro' => $membro,
            'erro' => $erro,
        ], 'Editar membro de comissão: ' . $evento['nome'], ['tipo' => 'trabalhosAnaisMontagem', 'id' => (int) $eventoId]);
    }

    public function removerMembro($eventoId)
    {
        $this->eventoOu404($eventoId);
        $this->exigirPost($eventoId, 'bloco-comissoes');

        try {
            $this->servico->removerMembro((int) $eventoId, (int) (isset($_POST['id']) ? $_POST['id'] : 0));
            flashSucesso('Membro removido.');
        } catch (\Throwable $e) {
            flashErro($this->mensagemDoErro($e));
        }

        $this->voltar($eventoId, 'bloco-comissoes');
    }

    public function reordenarMembros($eventoId, $comissaoId = null)
    {
        $this->eventoOu404($eventoId);

        $this->responderReordenacao(function (array $ids) use ($eventoId, $comissaoId) {
            return $this->comissoes->reordenarMembros((int) $eventoId, (int) $comissaoId, $ids);
        });
    }

    public function reordenarTrabalhos($eventoId)
    {
        $this->eventoOu404($eventoId);

        $this->responderReordenacao(function (array $ids) use ($eventoId) {
            (new EventoAnaisTrabalhoRepository())->reordenar((int) $eventoId, $ids);

            return true;
        });
    }

    public function gerar($eventoId)
    {
        $this->eventoOu404($eventoId);
        $this->exigirPost($eventoId, 'bloco-gerar');

        try {
            $this->servico->solicitarGeracao((int) $eventoId, Auth::usuarioId());
            flashSucesso('Pedido registrado. A prévia fica pronta em alguns minutos e aparece na aba Anais.');
        } catch (\Throwable $e) {
            flashErro($this->mensagemDoErro($e));
        }

        $this->voltar($eventoId, 'bloco-gerar');
    }

    /**
     * Ponto unico dos tres arrastar e soltar da tela. A lista vem no CORPO
     * da requisicao em JSON (reordenar-arrastar.js), nunca em $_POST: ler
     * $_POST fazia a ordem nunca ser gravada, sem erro na tela (defeito real
     * da Fase 51). $gravar devolve false quando o alvo nao e' deste evento.
     */
    private function responderReordenacao(callable $gravar)
    {
        header('Content-Type: application/json; charset=utf-8');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['ok' => false]);
            return;
        }

        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        try {
            $gravou = $gravar($ids);
        } catch (\Throwable $e) {
            error_log('[Anais] falha ao reordenar: ' . get_class($e) . ': ' . $e->getMessage());
            http_response_code(500);
            echo json_encode(['ok' => false]);
            return;
        }

        if ($gravou === false) {
            http_response_code(404);
            echo json_encode(['ok' => false]);
            return;
        }

        echo json_encode(['ok' => true]);
    }
}
