<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\EquipeRepository;
use App\Repositories\HomologacaoPublicaRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\ParticipanteRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Services\AcessoParticipanteService;

class HomologacaoController extends Controller
{
    private $equipes;
    private $participantes;
    private $trilhas;
    private $usuarioParticipante;
    private $notificacoes;
    private $homologacaoPublica;

    public function __construct()
    {
        // Fase 29 (ajuste pos-push): exigirEmQualquerConcurso() na entrada +
        // exigir() com o concurso resolvido dentro de cada acao - exigir()
        // sem concurso so' reconhece vinculo GLOBAL. Nos metodos que so'
        // recebem vinculo_id/participante_id do POST, o concurso usado na
        // checagem vem sempre do registro real (equipe->trilha->concurso),
        // nunca do trilha_id cru do POST.
        RoleMiddleware::exigirEmQualquerConcurso(['administrador', 'suporte']);
        $this->equipes = new EquipeRepository();
        $this->participantes = new ParticipanteRepository();
        $this->trilhas = new TrilhaRepository();
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->notificacoes = new NotificacaoPainelRepository();
        $this->homologacaoPublica = new HomologacaoPublicaRepository();
    }

    public function index($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);

        if ($trilha === null) {
            http_response_code(404);
            exit('Trilha não encontrada.');
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $trilha['concurso_id']);

        $status = isset($_GET['status']) ? $_GET['status'] : '';
        $inscricoes = $this->equipes->listarTodosPorTrilha($trilhaId, $status);

        // Fase 27, Parte C: "Convidar acesso" so aparece pra quem ja tem
        // e-mail (incluido pelo lider via ParticipanteController::
        // incluirEmailIntegrante(), Fase 27) mas ainda nao tem conta -
        // liberarAcesso() so roda uma vez, na homologacao, e pula em
        // silencio quando o e-mail estava vazio naquele momento.
        foreach ($inscricoes as &$inscricao) {
            $inscricao['precisa_convite'] = $inscricao['status_homologacao'] === 'homologado'
                && !empty($inscricao['email'])
                && empty($this->usuarioParticipante->usuariosDoParticipante($inscricao['participante_id']));
        }
        unset($inscricao);

        $this->renderizar('admin/homologacao/index', [
            'trilha' => $trilha,
            'inscricoes' => $inscricoes,
            'statusFiltro' => $status,
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
            'homologacaoPublicada' => $this->homologacaoPublica->jaPublicado($trilhaId),
        ], 'Inscritos — ' . $trilha['nome'], ['tipo' => 'inscritos', 'id' => (int) $trilhaId]);

        unset($_SESSION['flash']);
    }

    /**
     * Fase 19 (#17): publica/despublica a pagina publica de equipes
     * homologadas desta trilha (fora do fluxo de homologar/rejeitar
     * integrante, que qualquer Suporte ja pode fazer - publicar dado pra
     * fora e' decisao de Admin).
     */
    public function publicar($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);
        RoleMiddleware::exigir(['administrador'], $trilha !== null ? $trilha['concurso_id'] : null);

        $this->homologacaoPublica->publicar($trilhaId, Auth::usuarioId());
        $_SESSION['flash'] = 'Lista de equipes homologadas publicada.';
        $this->redirecionar('homologacao/index/' . $trilhaId);
    }

    public function despublicar($trilhaId)
    {
        $trilha = $this->trilhas->buscarPorId($trilhaId);
        RoleMiddleware::exigir(['administrador'], $trilha !== null ? $trilha['concurso_id'] : null);

        $this->homologacaoPublica->reabrir($trilhaId);
        $_SESSION['flash'] = 'Lista de equipes homologadas despublicada.';
        $this->redirecionar('homologacao/index/' . $trilhaId);
    }

    public function homologar()
    {
        $vinculoId = (int) (isset($_POST['vinculo_id']) ? $_POST['vinculo_id'] : 0);
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);

        $this->homologarUmVinculo($vinculoId, $trilhaId);
        $_SESSION['flash'] = 'Participante homologado e acesso liberado.';

        $this->redirecionar('homologacao/index/' . $trilhaId);
    }

    public function rejeitar()
    {
        $vinculoId = (int) (isset($_POST['vinculo_id']) ? $_POST['vinculo_id'] : 0);
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);
        $motivo = trim(isset($_POST['motivo']) ? $_POST['motivo'] : '');

        $this->rejeitarUmVinculo($vinculoId, $motivo !== '' ? $motivo : null);
        $_SESSION['flash'] = 'Participante rejeitado.';

        $this->redirecionar('homologacao/index/' . $trilhaId);
    }

    /**
     * Fase 27, Parte C: convite manual pra quem foi homologado sem e-mail e
     * so' teve o e-mail incluido depois (pelo lider, via
     * ParticipanteController::incluirEmailIntegrante()) - liberarAcesso()
     * so' roda automaticamente uma vez, na homologacao, entao precisa desse
     * gatilho separado pra criar a conta retroativamente. Um clique do
     * Admin, nunca automatico.
     */
    public function convidarAcesso()
    {
        $participanteId = (int) (isset($_POST['participante_id']) ? $_POST['participante_id'] : 0);
        $trilhaIdPost = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);

        $participante = $this->participantes->buscarPorId($participanteId);
        $equipe = $this->equipes->buscarPorParticipante($participanteId);

        if ($participante === null || $equipe === null || empty($participante['email'])) {
            $_SESSION['flash'] = 'Não foi possível convidar: participante ou e-mail não encontrado.';
            $this->redirecionar('homologacao/index/' . $trilhaIdPost);
            return;
        }

        // Fase 29 (ajuste pos-push): concurso (pra checagem) e trilha (pro
        // liberarAcesso, mais embaixo) resolvidos a partir da EQUIPE de
        // verdade (equipe['trilha_id']), nao do trilha_id cru do POST -
        // que ate aqui era usado sem nenhuma validacao, inclusive na
        // propria liberacao de acesso (podia liberar acesso escopado a
        // uma trilha errada se o POST viesse adulterado).
        $trilhaReal = $this->trilhas->buscarPorId($equipe['trilha_id']);
        RoleMiddleware::exigir(['administrador'], $trilhaReal !== null ? $trilhaReal['concurso_id'] : null);

        if (!empty($this->usuarioParticipante->usuariosDoParticipante($participanteId))) {
            $_SESSION['flash'] = 'Este participante já tem acesso ao sistema.';
            $this->redirecionar('homologacao/index/' . $equipe['trilha_id']);
            return;
        }

        (new AcessoParticipanteService())->liberarAcesso($participante, $equipe['trilha_id'], $equipe['nome_equipe']);

        $_SESSION['flash'] = 'Convite enviado para "' . $participante['nome'] . '".';
        $this->redirecionar('homologacao/index/' . $equipe['trilha_id']);
    }

    public function homologarEmMassa()
    {
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);
        $vinculoIds = isset($_POST['vinculo_ids']) && is_array($_POST['vinculo_ids']) ? $_POST['vinculo_ids'] : [];

        foreach ($vinculoIds as $vinculoId) {
            $this->homologarUmVinculo((int) $vinculoId, $trilhaId);
        }

        $_SESSION['flash'] = count($vinculoIds) . ' inscrição(ões) homologada(s).';
        $this->redirecionar('homologacao/index/' . $trilhaId);
    }

    public function rejeitarEmMassa()
    {
        $trilhaId = (int) (isset($_POST['trilha_id']) ? $_POST['trilha_id'] : 0);
        $vinculoIds = isset($_POST['vinculo_ids']) && is_array($_POST['vinculo_ids']) ? $_POST['vinculo_ids'] : [];
        $motivo = trim(isset($_POST['motivo']) ? $_POST['motivo'] : '');

        foreach ($vinculoIds as $vinculoId) {
            $this->rejeitarUmVinculo((int) $vinculoId, $motivo !== '' ? $motivo : null);
        }

        $_SESSION['flash'] = count($vinculoIds) . ' inscrição(ões) rejeitada(s).';
        $this->redirecionar('homologacao/index/' . $trilhaId);
    }

    /**
     * Fase 29 (ajuste pos-push): checagem de concurso centralizada aqui
     * (usada por homologar()/homologarEmMassa()) - resolve a partir da
     * equipe de verdade do vinculo, nunca do trilha_id cru do POST (so'
     * usado pro redirect no metodo publico).
     */
    private function homologarUmVinculo($vinculoId, $trilhaId)
    {
        $vinculo = $this->equipes->buscarVinculoPorId($vinculoId);

        if ($vinculo === null) {
            return;
        }

        $equipe = $this->equipes->buscarPorId($vinculo['equipe_id']);
        $trilhaReal = $equipe !== null ? $this->trilhas->buscarPorId($equipe['trilha_id']) : null;
        RoleMiddleware::exigir(['administrador', 'suporte'], $trilhaReal !== null ? $trilhaReal['concurso_id'] : null);

        $participante = $this->participantes->buscarPorId($vinculo['participante_id']);

        $this->equipes->homologarVinculo($vinculoId, Auth::usuarioId());
        $this->limparNotificacaoRejeicao($vinculo['participante_id']);
        (new AcessoParticipanteService())->liberarAcesso($participante, $trilhaId, $equipe['nome_equipe']);
    }

    private function rejeitarUmVinculo($vinculoId, $motivo)
    {
        $vinculo = $this->equipes->buscarVinculoPorId($vinculoId);

        if ($vinculo === null) {
            return;
        }

        $equipe = $this->equipes->buscarPorId($vinculo['equipe_id']);
        $trilhaReal = $equipe !== null ? $this->trilhas->buscarPorId($equipe['trilha_id']) : null;
        RoleMiddleware::exigir(['administrador', 'suporte'], $trilhaReal !== null ? $trilhaReal['concurso_id'] : null);

        $this->equipes->rejeitarVinculo($vinculoId, Auth::usuarioId(), $motivo);

        $mensagem = 'Sua inscrição na equipe "' . $equipe['nome_equipe'] . '" foi rejeitada.'
            . ($motivo !== null ? ' Motivo: ' . $motivo : ' Nenhum motivo foi informado.');

        foreach ($this->usuarioParticipante->usuariosDoParticipante($vinculo['participante_id']) as $usuarioId) {
            $this->notificacoes->garantirUnica(
                $usuarioId,
                'equipe_rejeitada',
                'Inscrição rejeitada',
                $mensagem,
                ['url' => url('participante/index')]
            );
        }
    }

    private function limparNotificacaoRejeicao($participanteId)
    {
        foreach ($this->usuarioParticipante->usuariosDoParticipante($participanteId) as $usuarioId) {
            $this->notificacoes->removerPorTipo($usuarioId, 'equipe_rejeitada');
        }
    }
}
