<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\DuvidaRepository;
use App\Repositories\DuvidaRespostaRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Services\ArquivoService;

/**
 * Fase 29 (Tira-Duvidas): canal do participante pra registrar duvida (com
 * anexo opcional), acompanhar status e reabrir se ja respondida. Qualquer
 * integrante da equipe pode ver/registrar/reabrir - ao contrario da maioria
 * das acoes de equipe em ParticipanteController, aqui NAO e' exclusivo do
 * lider (decisao confirmada com o usuario).
 */
class DuvidaController extends Controller
{
    private $duvidas;
    private $respostas;
    private $equipes;
    private $trilhas;
    private $usuarioParticipante;
    private $notificacoes;
    private $perfis;
    private $arquivos;

    public function __construct()
    {
        RoleMiddleware::exigirEmQualquerConcurso(['participante']);
        $this->duvidas = new DuvidaRepository();
        $this->respostas = new DuvidaRespostaRepository();
        $this->equipes = new EquipeRepository();
        $this->trilhas = new TrilhaRepository();
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->notificacoes = new NotificacaoPainelRepository();
        $this->perfis = new PerfilRepository();
        $this->arquivos = new ArquivoService();
    }

    public function index()
    {
        $contexto = $this->equipeAtual();

        $this->renderizar('participante/duvidas', [
            'duvidas' => $this->duvidas->listarPorEquipe($contexto['equipe']['id']),
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Dúvidas');

        unset($_SESSION['flash']);
    }

    public function novo()
    {
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $erro = $this->salvarNova();

            if ($erro === null) {
                $_SESSION['flash'] = 'Dúvida registrada. Você será notificado quando houver resposta.';
                $this->redirecionar('duvida/index');
                return;
            }
        }

        $this->renderizar('participante/duvida_form', [
            'erro' => $erro,
            'limiteMB' => ArquivoService::limiteMaximoMB(),
        ], 'Registrar dúvida');
    }

    public function ver($id)
    {
        $duvida = $this->duvidaDaEquipeAtual($id);

        $this->renderizar('participante/duvida_ver', [
            'duvida' => $duvida,
            'respostas' => $this->respostas->listarPorDuvida((int) $id),
            'limiteMB' => ArquivoService::limiteMaximoMB(),
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Dúvida');

        unset($_SESSION['flash']);
    }

    public function reabrir($id)
    {
        $duvida = $this->duvidaDaEquipeAtual($id);

        if ($duvida['status'] !== 'respondida') {
            $_SESSION['flash'] = 'Só é possível reabrir uma dúvida já respondida.';
            $this->redirecionar('duvida/ver/' . (int) $id);
            return;
        }

        $texto = trim(isset($_POST['pergunta']) ? $_POST['pergunta'] : '');

        if ($texto === '') {
            $_SESSION['flash'] = 'Descreva o que ainda falta esclarecer antes de reabrir.';
            $this->redirecionar('duvida/ver/' . (int) $id);
            return;
        }

        list($anexoPath, $anexoNomeOriginal, $erroAnexo) = $this->processarAnexoOpcional();

        if ($erroAnexo !== null) {
            $_SESSION['flash'] = $erroAnexo;
            $this->redirecionar('duvida/ver/' . (int) $id);
            return;
        }

        $this->duvidas->reabrir((int) $id);
        $this->respostas->criar((int) $id, Auth::usuarioId(), $texto, $anexoPath, $anexoNomeOriginal);
        $this->notificarAdministradoresNovaDuvida((int) $duvida['concurso_id'], $duvida['nome_equipe'], $duvida['participante_nome'], (int) $id);

        $_SESSION['flash'] = 'Dúvida reaberta.';
        $this->redirecionar('duvida/ver/' . (int) $id);
    }

    private function salvarNova()
    {
        $pergunta = trim(isset($_POST['pergunta']) ? $_POST['pergunta'] : '');

        if ($pergunta === '') {
            return 'Descreva sua dúvida.';
        }

        list($anexoPath, $anexoNomeOriginal, $erroAnexo) = $this->processarAnexoOpcional();

        if ($erroAnexo !== null) {
            return $erroAnexo;
        }

        $contexto = $this->equipeAtual();
        $equipe = $contexto['equipe'];
        $trilha = $this->trilhas->buscarPorId($equipe['trilha_id']);

        $duvidaId = $this->duvidas->criar(
            $contexto['participante']['id'],
            $equipe['id'],
            $trilha['concurso_id'],
            $trilha['id'],
            $pergunta,
            $anexoPath,
            $anexoNomeOriginal
        );

        $this->notificarAdministradoresNovaDuvida((int) $trilha['concurso_id'], $equipe['nome_equipe'], $contexto['participante']['nome'], $duvidaId);

        return null;
    }

    private function notificarAdministradoresNovaDuvida($concursoId, $nomeEquipe, $nomeParticipante, $duvidaId)
    {
        foreach ($this->perfis->listarUsuariosPorPerfilConcurso('administrador', $concursoId) as $admin) {
            $this->notificacoes->criar(
                (int) $admin['id'],
                'duvida_nova',
                'Nova dúvida registrada',
                '"' . $nomeParticipante . '" (equipe "' . $nomeEquipe . '") registrou uma dúvida.',
                ['url' => url('duvidaAdmin/ver/' . (int) $duvidaId)]
            );
        }
    }

    /**
     * PDF, imagem (webp/jpg/jpeg/png) ou nada - anexo e' sempre opcional.
     * Retorna [caminho, nome_original, erro] - so' um dos dois primeiros ou
     * o erro fica preenchido, nunca os dois ao mesmo tempo.
     */
    private function processarAnexoOpcional()
    {
        if (empty($_FILES['anexo']) || $_FILES['anexo']['error'] === UPLOAD_ERR_NO_FILE) {
            return [null, null, null];
        }

        if ($_FILES['anexo']['error'] !== UPLOAD_ERR_OK) {
            return [null, null, 'Falha no envio do anexo.'];
        }

        try {
            $caminho = $this->arquivos->salvar($_FILES['anexo'], 'duvidas');
        } catch (\RuntimeException $e) {
            return [null, null, $e->getMessage()];
        }

        return [$caminho, $_FILES['anexo']['name'], null];
    }

    private function equipeAtual()
    {
        $participantes = $this->usuarioParticipante->participantesDoUsuario(Auth::usuarioId());
        $participante = !empty($participantes) ? $participantes[0] : null;

        if ($participante === null) {
            http_response_code(404);
            exit('Nenhum participante vinculado a esta conta.');
        }

        $equipe = $this->equipes->buscarPorParticipante($participante['id']);

        if ($equipe === null) {
            http_response_code(404);
            exit('Nenhuma equipe encontrada para este participante.');
        }

        return ['participante' => $participante, 'equipe' => $equipe];
    }

    private function duvidaDaEquipeAtual($id)
    {
        $contexto = $this->equipeAtual();
        $duvida = $this->duvidas->buscarPorId((int) $id);

        if ($duvida === null || (int) $duvida['equipe_id'] !== (int) $contexto['equipe']['id']) {
            http_response_code(404);
            exit('Dúvida não encontrada.');
        }

        return $duvida;
    }
}
