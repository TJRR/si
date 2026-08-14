<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\EquipeRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Services\AcessoParticipanteService;

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

        if ($notificacao['tipo'] === 'participante_email_completo') {
            $this->convidarAPartirDaNotificacao($notificacao);
        } else {
            $this->notificacoes->marcarLida($id);
        }

        $voltar = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('home/index');
        header('Location: ' . $voltar);
        exit;
    }

    /**
     * Fase 31: o texto desta notificacao sempre disse "Convide-o", mas o
     * check so descartava o aviso - ninguem convidava de verdade, e os
     * casos se acumulavam em silencio. Agora o check cumpre o que a
     * mensagem promete: dispara o mesmo convite de
     * HomologacaoController::convidarAcesso() e so' entao remove o aviso
     * (dos demais admins tambem, via removerPorTipoEParticipante). Se o
     * usuario que clicou nao for administrador/suporte do concurso da
     * equipe (nao deveria acontecer - so admin recebe este tipo - mas por
     * seguranca), so descarta o aviso, sem convidar.
     */
    private function convidarAPartirDaNotificacao(array $notificacao)
    {
        $dados = $notificacao['dados'] !== null ? json_decode($notificacao['dados'], true) : null;
        $participanteId = isset($dados['participante_id']) ? (int) $dados['participante_id'] : null;

        if ($participanteId === null) {
            $this->notificacoes->marcarLida($notificacao['id']);
            return;
        }

        $participante = (new ParticipanteRepository())->buscarPorId($participanteId);
        $equipe = (new EquipeRepository())->buscarPorParticipante($participanteId);

        if ($participante === null || $equipe === null || empty($participante['email'])) {
            $this->notificacoes->marcarLida($notificacao['id']);
            return;
        }

        $trilha = (new TrilhaRepository())->buscarPorId($equipe['trilha_id']);
        $concursoId = $trilha !== null ? $trilha['concurso_id'] : null;

        if (!Auth::temPerfil('administrador', $concursoId) && !Auth::temPerfil('suporte', $concursoId)) {
            $this->notificacoes->marcarLida($notificacao['id']);
            return;
        }

        $usuarioParticipante = new UsuarioParticipanteRepository();

        if (empty($usuarioParticipante->usuariosDoParticipante($participanteId))) {
            (new AcessoParticipanteService())->liberarAcesso($participante, $equipe['trilha_id'], $equipe['nome_equipe']);
            $_SESSION['flash'] = 'Convite enviado para "' . $participante['nome'] . '".';
        }

        $this->notificacoes->removerPorTipoEParticipante('participante_email_completo', $participanteId);
    }

    public function marcarTodasLidas()
    {
        $this->notificacoes->marcarTodasLidas(Auth::usuarioId());

        $voltar = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : url('home/index');
        header('Location: ' . $voltar);
        exit;
    }
}
