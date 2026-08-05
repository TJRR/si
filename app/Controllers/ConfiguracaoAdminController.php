<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConfiguracaoSistemaRepository;

class ConfiguracaoAdminController extends Controller
{
    private $configuracoes;

    public function __construct()
    {
        RoleMiddleware::exigir(['administrador']);
        $this->configuracoes = new ConfiguracaoSistemaRepository();
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
}
