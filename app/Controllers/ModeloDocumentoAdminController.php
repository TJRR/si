<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EtapaRepository;
use App\Repositories\ModeloDocumentoRepository;
use App\Repositories\RequerimentoRepository;
use App\Repositories\RequerimentoRespostaRepository;
use App\Repositories\TrilhaRepository;
use App\Services\ArquivoPrivadoService;
use App\Services\ModeloDocumentoService;

/**
 * Fase 30: cadastro de modelos de documento (Requerimento, Termo etc.)
 * dentro de uma Etapa - pode haver mais de um por etapa. So' Administrador
 * cadastra; a decisao final de aprovar/recusar/revogar um requerimento
 * tambem fica so' com Administrador (ver RequerimentoAdminController) - por
 * isso este controller so' aceita 'administrador', diferente do padrao de
 * EtapaAdminController (que tambem aceita Suporte).
 */
class ModeloDocumentoAdminController extends Controller
{
    private $modelos;
    private $etapas;
    private $trilhas;
    private $requerimentos;
    private $respostas;

    public function __construct()
    {
        RoleMiddleware::exigirEmQualquerConcurso(['administrador']);
        $this->modelos = new ModeloDocumentoRepository();
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->requerimentos = new RequerimentoRepository();
        $this->respostas = new RequerimentoRespostaRepository();
    }

    public function index($etapaId)
    {
        $etapa = $this->etapaAutorizada($etapaId);

        $this->renderizar('admin/modelos_documento/index', [
            'etapa' => $etapa,
            'modelos' => $this->modelos->listarPorEtapa($etapaId),
            'naoTerminaisPendentes' => $this->requerimentos->contarNaoTerminaisPorEtapa($etapaId),
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Modelos de Documento — ' . $etapa['nome'], ['tipo' => 'modelos_documento', 'id' => (int) $etapaId]);

        unset($_SESSION['flash']);
    }

    public function novo($etapaId)
    {
        $etapa = $this->etapaAutorizada($etapaId);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->lerDadosFormulario();
            $erro = $this->validar($dados);

            if ($erro === null) {
                $this->modelos->criar($etapaId, $dados['nome'], $dados['finalidade'], $dados['corpo_html'], $dados['ativo']);
                $this->redirecionar('modelosDocumento/index/' . $etapaId);
                return;
            }
        }

        $this->renderizar('admin/modelos_documento/form', [
            'erro' => $erro,
            'etapa' => $etapa,
            'modelo' => null,
        ], 'Novo modelo de documento', ['tipo' => 'modelos_documento', 'id' => (int) $etapaId]);
    }

    public function editar($id)
    {
        $modelo = $this->modelos->buscarPorId($id);

        if ($modelo === null) {
            http_response_code(404);
            exit('Modelo não encontrado.');
        }

        $etapa = $this->etapaAutorizada($modelo['etapa_id']);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = $this->lerDadosFormulario();
            $erro = $this->validar($dados);

            if ($erro === null) {
                $this->modelos->atualizar($id, $dados['nome'], $dados['finalidade'], $dados['corpo_html'], $dados['ativo']);
                $modelo = $this->modelos->buscarPorId($id);
            }
        }

        $this->renderizar('admin/modelos_documento/form', [
            'erro' => $erro,
            'etapa' => $etapa,
            'modelo' => $modelo,
        ], 'Editar modelo de documento', ['tipo' => 'modelos_documento', 'id' => (int) $etapa['id']]);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $etapaId = (int) (isset($_POST['etapa_id']) ? $_POST['etapa_id'] : 0);

        $modelo = $this->modelos->buscarPorId($id);

        if ($modelo === null) {
            http_response_code(404);
            exit('Modelo não encontrado.');
        }

        $this->etapaAutorizada($modelo['etapa_id']);

        try {
            $this->modelos->remover($id);
            $_SESSION['flash'] = 'Modelo removido.';
        } catch (\PDOException $e) {
            $_SESSION['flash'] = $e->getCode() === '23000'
                ? 'Não é possível remover: já existe pedido protocolado a partir deste modelo.'
                : 'Não foi possível remover o modelo.';
        }

        $this->redirecionar('modelosDocumento/index/' . $etapaId);
    }

    /**
     * Fase 30 (retencao/expurgo): apaga fisicamente os arquivos (PDF
     * assinado + anexos de resposta) so' dos requerimentos em estado
     * terminal (aprovado/recusado/revogado) desta etapa - pedidos ainda em
     * analise ficam de fora, mesmo que a etapa ja tenha terminado (o lider
     * pode ter enviado no ultimo dia, sem tempo do Administrador analisar
     * antes do cronograma virar). E' irreversivel, por isso as duas
     * travas: a etapa precisa ja ter passado da propria data final, e o
     * Administrador precisa redigitar o nome da etapa pra confirmar.
     */
    public function expurgar($etapaId)
    {
        $etapa = $this->etapaAutorizada($etapaId);

        if ($etapa['data_fim'] === null || strtotime($etapa['data_fim']) > time()) {
            $_SESSION['flash'] = 'Só é possível expurgar documentos de uma etapa que já passou da própria data final.';
            $this->redirecionar('modelosDocumento/index/' . $etapaId);
            return;
        }

        $confirmacao = trim(isset($_POST['confirmacao']) ? $_POST['confirmacao'] : '');

        if ($confirmacao !== $etapa['nome']) {
            $_SESSION['flash'] = 'Confirmação incorreta — digite exatamente o nome da etapa para expurgar.';
            $this->redirecionar('modelosDocumento/index/' . $etapaId);
            return;
        }

        $expurgados = 0;

        foreach ($this->requerimentos->listarTerminaisNaoExpurgadosPorEtapa($etapaId) as $requerimento) {
            ArquivoPrivadoService::remover($requerimento['pdf_assinado_path']);
            $this->requerimentos->marcarExpurgado($requerimento['id']);
            $expurgados++;

            foreach ($this->respostas->listarComAnexoNaoExpurgadoPorRequerimento($requerimento['id']) as $resposta) {
                ArquivoPrivadoService::remover($resposta['anexo_path']);
                $this->respostas->marcarAnexoExpurgado($resposta['id']);
            }
        }

        $pendentes = $this->requerimentos->contarNaoTerminaisPorEtapa($etapaId);

        $_SESSION['flash'] = $expurgados . ' documento(s) expurgado(s).'
            . ($pendentes > 0 ? ' ' . $pendentes . ' pedido(s) ficaram de fora por ainda estarem em análise.' : '');
        $this->redirecionar('modelosDocumento/index/' . $etapaId);
    }

    private function etapaAutorizada($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);
        RoleMiddleware::exigir(['administrador'], $trilha !== null ? $trilha['concurso_id'] : null);

        return $etapa;
    }

    private function validar(array $dados)
    {
        if ($dados['nome'] === '') {
            return 'Informe o nome do modelo.';
        }

        if ($dados['finalidade'] === '') {
            return 'Informe a finalidade do modelo.';
        }

        if ($dados['corpo_html'] === '') {
            return 'Escreva o corpo do documento.';
        }

        $desconhecidas = (new ModeloDocumentoService())->palavrasChaveDesconhecidas($dados['corpo_html']);

        if (!empty($desconhecidas)) {
            return 'Palavra-chave não reconhecida: [[' . implode(']], [[', $desconhecidas) . ']]. Confira o dicionário ao lado do editor.';
        }

        return null;
    }

    /**
     * Fase 30 (melhoria pos-teste): a ordem deixou de vir do formulário -
     * um modelo novo sempre entra no fim da lista, e a posição definitiva se
     * ajusta arrastando na tela de listagem (ver reordenar() acima).
     */
    public function reordenar($etapaId)
    {
        $this->etapaAutorizada($etapaId);

        header('Content-Type: application/json; charset=utf-8');
        $corpo = json_decode((string) file_get_contents('php://input'), true);
        $ids = isset($corpo['ids']) && is_array($corpo['ids']) ? array_map('intval', $corpo['ids']) : [];

        $this->modelos->reordenar($ids);

        echo json_encode(['ok' => true]);
    }

    private function lerDadosFormulario()
    {
        return [
            'nome' => trim(isset($_POST['nome']) ? $_POST['nome'] : ''),
            'finalidade' => trim(isset($_POST['finalidade']) ? $_POST['finalidade'] : ''),
            'corpo_html' => isset($_POST['corpo_html']) ? $_POST['corpo_html'] : '',
            'ativo' => isset($_POST['ativo']) ? 1 : 0,
        ];
    }
}
