<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConfiguracaoSistemaRepository;
use App\Repositories\TemaVisualRepository;
use App\Services\ImagemService;

class ConfiguracaoAdminController extends Controller
{
    private $configuracoes;
    private $imagens;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->configuracoes = new ConfiguracaoSistemaRepository();
        $this->imagens = new ImagemService();
    }

    public function index()
    {
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $minutos = (int) (isset($_POST['sessao_timeout_minutos']) ? $_POST['sessao_timeout_minutos'] : 0);

            if ($minutos < 1) {
                $erro = 'Informe um tempo de expiração de sessão válido (em minutos, maior que zero).';
            } else {
                $this->configuracoes->atualizarSessaoTimeoutMinutos($minutos);
                $_SESSION['flash'] = 'Configurações atualizadas.';
                $this->redirecionar('configuracoes/index');
                return;
            }
        }

        $this->renderizar('admin/configuracoes/index', [
            'configuracao' => $this->configuracoes->buscar(),
            'erro' => $erro,
        ], 'Configurações', ['tipo' => 'configuracaoGeral', 'id' => null]);
    }

    public function desativarSistema()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->configuracoes->desativarSistema();
            $_SESSION['flash'] = 'Sistema desativado: apenas administradores conseguem acessar agora.';
        }

        $this->redirecionar('configuracoes/index');
    }

    public function reativarSistema()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->configuracoes->reativarSistema();
            $_SESSION['flash'] = 'Sistema reativado: acesso normal restabelecido para todos.';
        }

        $this->redirecionar('configuracoes/index');
    }

    public function salvarIdentidade()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $instituicao = trim(isset($_POST['instituicao']) ? $_POST['instituicao'] : '');
            $instituicaoNomeCompleto = trim(isset($_POST['instituicao_nome_completo']) ? $_POST['instituicao_nome_completo'] : '');
            $unidadeResponsavel = trim(isset($_POST['unidade_responsavel']) ? $_POST['unidade_responsavel'] : '');
            $unidadeResponsavelNomeCompleto = trim(isset($_POST['unidade_responsavel_nome_completo']) ? $_POST['unidade_responsavel_nome_completo'] : '');

            if ($instituicao === '' || $instituicaoNomeCompleto === '' || $unidadeResponsavel === '' || $unidadeResponsavelNomeCompleto === '') {
                flashErro('Informe o nome completo e a sigla da instituição e da unidade responsável.');
            } else {
                $this->configuracoes->atualizarIdentidadeInstitucional($instituicao, $instituicaoNomeCompleto, $unidadeResponsavel, $unidadeResponsavelNomeCompleto);
                flashSucesso('Identidade institucional atualizada.');
            }
        }

        $this->redirecionar('configuracoes/index');
    }

    /**
     * Fase 41: nome do aplicativo web instalavel (PWA) do Evento -
     * "Configurações Gerais" em vez de fixo no codigo (regra permanente do
     * projeto: toda configuracao de negocio precisa de tela administrativa).
     */
    public function salvarApp()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nome = trim(isset($_POST['nome_app']) ? $_POST['nome_app'] : '');
            $nomeCurto = trim(isset($_POST['nome_app_curto']) ? $_POST['nome_app_curto'] : '');

            $this->configuracoes->atualizarNomeApp($nome !== '' ? $nome : null, $nomeCurto !== '' ? $nomeCurto : null);
            flashSucesso('Nome do aplicativo atualizado.');
        }

        $this->redirecionar('configuracoes/index');
    }

    public function enviarIconeApp()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (empty($_FILES['icone_app']) || $_FILES['icone_app']['error'] === UPLOAD_ERR_NO_FILE) {
                flashErro('Selecione uma imagem para enviar.');
                $this->redirecionar('configuracoes/index');
                return;
            }

            if ($_FILES['icone_app']['error'] !== UPLOAD_ERR_OK) {
                flashErro('Falha ao enviar o ícone.');
                $this->redirecionar('configuracoes/index');
                return;
            }

            // Fase 48B: icone do app e' asset institucional, sempre no tema
            // padrao do sistema, nunca no tema pessoal de quem estiver logado.
            $corPrimaria = (new TemaVisualRepository())->buscarPadrao()['cor_primaria_inicio'];

            try {
                $this->imagens->salvarIconeApp($_FILES['icone_app'], $corPrimaria);
                $this->configuracoes->marcarIconeAppAtualizado();
                flashSucesso('Ícone do aplicativo atualizado.');
            } catch (\RuntimeException $e) {
                flashErro($e->getMessage());
            }
        }

        $this->redirecionar('configuracoes/index');
    }
}
