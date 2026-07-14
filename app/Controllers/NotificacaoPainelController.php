<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\NotificacaoPainelRepository;

class NotificacaoPainelController extends Controller
{
    private $notificacoes;

    public function __construct()
    {
        if (!Auth::autenticado()) {
            header('Location: ' . url('auth/login'));
            exit;
        }

        $this->notificacoes = new NotificacaoPainelRepository();
    }

    public function abrir($id)
    {
        $notificacao = $this->notificacoes->buscarPorId($id);

        if ($notificacao === null) {
            // A notificacao pode ter sido removida pelo auto-cura (ex.: CPF
            // corrigido em outra aba) entre a lista do sino carregar e o
            // clique - nao e' um erro do usuario, so segue pro painel dele.
            $this->redirecionar(Auth::destinoPainel());
            return;
        }

        if ((int) $notificacao['usuario_id'] !== (int) Auth::usuarioId()) {
            http_response_code(403);
            exit('Acesso negado.');
        }

        $this->notificacoes->marcarLida($id);

        $dados = $notificacao['dados'] !== null ? json_decode($notificacao['dados'], true) : null;
        $destino = $dados !== null && !empty($dados['url']) ? $dados['url'] : url('home/index');

        header('Location: ' . $destino);
        exit;
    }

    public function marcarLida($id)
    {
        $notificacao = $this->notificacoes->buscarPorId($id);

        if ($notificacao === null) {
            $this->redirecionar(Auth::destinoPainel());
            return;
        }

        if ((int) $notificacao['usuario_id'] !== (int) Auth::usuarioId()) {
            http_response_code(403);
            exit('Acesso negado.');
        }

        $this->notificacoes->marcarLida($id);

        $voltar = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('home/index');
        header('Location: ' . $voltar);
        exit;
    }

    public function marcarTodasLidas()
    {
        $this->notificacoes->marcarTodasLidas(Auth::usuarioId());

        $voltar = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('home/index');
        header('Location: ' . $voltar);
        exit;
    }
}
