<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auth;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\DuvidaEscalonamentoRepository;
use App\Repositories\DuvidaRepository;
use App\Repositories\DuvidaRespostaRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Services\ArquivoService;

/**
 * Fase 29 (Tira-Duvidas): atendimento (administrador/suporte) - responder,
 * escalar pra outro atendente, retomar uma escalada parada (painel de
 * gestao) e o dashboard consolidado.
 *
 * Autorizacao SEMPRE checada dentro de cada acao (nao so' no filtro da
 * listagem) - ver podeGerenciar()/podeAgir(). Administrador so' age dentro
 * do(s) concurso(s) que ele acessa (concursosDoUsuario() - null = global,
 * sem filtro); Suporte so' age na duvida que estiver com ele
 * (responsavel_atual_usuario_id), nunca na fila geral nem na de outro
 * atendente.
 */
class DuvidaAdminController extends Controller
{
    private $duvidas;
    private $respostas;
    private $escalonamentos;
    private $equipes;
    private $usuarioParticipante;
    private $notificacoes;
    private $perfis;
    private $arquivos;

    public function __construct()
    {
        // Fase 29 (ajuste pos-push): 'colaborador' entra so' aqui, nao nos
        // outros 10+ controllers que aceitam 'suporte' - podeAgir() ja
        // restringe a acao a duvida onde ele e' o responsavel atual,
        // entao nao precisa de mais nenhuma checagem especial pra ele.
        //
        // exigirEmQualquerConcurso() (nao exigir()) de proposito: exigir()
        // usa Auth::temPerfil(), que so' reconhece o vinculo se ele for
        // GLOBAL (concurso_id NULL) ou se o concurso for passado pra
        // comparar - e aqui, na entrada do controller, ainda nao se sabe
        // qual duvida vai ser aberta, entao nao ha concurso pra passar.
        // Um colaborador/suporte/administrador escopado a UM concurso
        // especifico (nao "Global") cairia em "Acesso negado" mesmo sendo
        // o perfil certo - mesmo bug ja corrigido antes em
        // HomeController::administrativo() (ver comentario la'). A checagem
        // fina por concurso de fato acontece depois, dentro de
        // podeGerenciar()/podeAgir(), por duvida especifica.
        RoleMiddleware::exigirEmQualquerConcurso(['administrador', 'suporte', 'colaborador']);
        $this->duvidas = new DuvidaRepository();
        $this->respostas = new DuvidaRespostaRepository();
        $this->escalonamentos = new DuvidaEscalonamentoRepository();
        $this->equipes = new EquipeRepository();
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->notificacoes = new NotificacaoPainelRepository();
        $this->perfis = new PerfilRepository();
        $this->arquivos = new ArquivoService();
    }

    /**
     * Fase 29 (ajuste pos-push): tela dedicada, exclusiva do perfil
     * Colaborador (pessoa externa ao NPI, sem acesso ao Painel
     * administrativo) - mesma listagem "Escaladas para mim" que
     * administrador/suporte ja veem dentro de home/administrativo, so' que
     * numa pagina propria e minima, sem estatisticas nem fila geral.
     */
    public function minhasEscaladas()
    {
        $statusFiltro = isset($_GET['status']) ? $_GET['status'] : null;
        $minhasEscaladas = $this->duvidas->listarPorResponsavel(Auth::usuarioId(), $statusFiltro);

        foreach ($minhasEscaladas as &$item) {
            $item['atrasada'] = $this->emAtraso($item);
        }
        unset($item);

        $this->renderizar('admin/duvidas/minhas_escaladas', [
            'minhasEscaladas' => $minhasEscaladas,
            'statusFiltro' => $statusFiltro,
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Dúvidas');

        unset($_SESSION['flash']);
    }

    public function ver($id)
    {
        $duvida = $this->duvidas->buscarPorId((int) $id);

        if ($duvida === null) {
            http_response_code(404);
            exit('Dúvida não encontrada.');
        }

        if (!$this->podeAgir($duvida)) {
            http_response_code(403);
            exit('Acesso negado: esta dúvida está com outro responsável.');
        }

        $this->renderizar('admin/duvidas/ver', [
            'duvida' => $duvida,
            'respostas' => $this->respostas->listarPorDuvida((int) $id),
            'escalonamentos' => $this->escalonamentos->listarPorDuvida((int) $id),
            'atendentesDisponiveis' => $this->atendentesDisponiveis((int) $duvida['concurso_id']),
            'limiteMB' => ArquivoService::limiteMaximoMB(),
            'atrasada' => $this->emAtraso($duvida),
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Dúvida');

        unset($_SESSION['flash']);
    }

    public function responder($id)
    {
        $duvida = $this->duvidas->buscarPorId((int) $id);

        if ($duvida === null) {
            http_response_code(404);
            exit('Dúvida não encontrada.');
        }

        if ($duvida['status'] === 'respondida' || !$this->podeAgir($duvida)) {
            http_response_code(403);
            exit('Acesso negado ou dúvida já respondida.');
        }

        $resposta = trim(isset($_POST['resposta']) ? $_POST['resposta'] : '');

        if ($resposta === '') {
            $_SESSION['flash'] = 'Escreva a resposta antes de enviar.';
            $this->redirecionar('duvidaAdmin/ver/' . (int) $id);
            return;
        }

        list($anexoPath, $anexoNomeOriginal, $erroAnexo) = $this->processarAnexoOpcional();

        if ($erroAnexo !== null) {
            $_SESSION['flash'] = $erroAnexo;
            $this->redirecionar('duvidaAdmin/ver/' . (int) $id);
            return;
        }

        $this->respostas->criar((int) $id, Auth::usuarioId(), $resposta, $anexoPath, $anexoNomeOriginal);
        $this->duvidas->responder((int) $id);
        $this->notificarEquipe($duvida, 'Dúvida respondida', 'Sua dúvida foi respondida.');

        $_SESSION['flash'] = 'Resposta enviada.';
        $this->redirecionar('duvidaAdmin/ver/' . (int) $id);
    }

    public function escalar($id)
    {
        $duvida = $this->duvidas->buscarPorId((int) $id);

        if ($duvida === null) {
            http_response_code(404);
            exit('Dúvida não encontrada.');
        }

        if ($duvida['status'] === 'respondida' || !$this->podeAgir($duvida)) {
            http_response_code(403);
            exit('Acesso negado ou dúvida já respondida.');
        }

        $novoResponsavelId = (int) (isset($_POST['usuario_id']) ? $_POST['usuario_id'] : 0);
        $disponiveis = $this->atendentesDisponiveis((int) $duvida['concurso_id']);

        if (!in_array($novoResponsavelId, array_column($disponiveis, 'id'), false)) {
            $_SESSION['flash'] = 'Selecione um administrador ou suporte válido.';
            $this->redirecionar('duvidaAdmin/ver/' . (int) $id);
            return;
        }

        $this->escalonamentos->criar((int) $id, Auth::usuarioId(), $novoResponsavelId);
        $this->duvidas->escalar((int) $id, $novoResponsavelId);
        $this->notificarResponsavel($novoResponsavelId, $duvida);

        $_SESSION['flash'] = 'Dúvida escalada.';
        $this->redirecionar('duvidaAdmin/ver/' . (int) $id);
    }

    /**
     * Admin puxando de volta uma escalada parada sem resposta (painel de
     * gestao) - diferente de DuvidaController::reabrir(), que e' o
     * participante reabrindo uma ja respondida.
     */
    public function retomar($id)
    {
        $duvida = $this->duvidas->buscarPorId((int) $id);

        if ($duvida === null) {
            http_response_code(404);
            exit('Dúvida não encontrada.');
        }

        if ($duvida['status'] !== 'escalada' || !$this->podeGerenciar($duvida)) {
            http_response_code(403);
            exit('Só é possível retomar (Administrador) uma dúvida escalada, dentro do seu escopo.');
        }

        $this->duvidas->retomar((int) $id);
        $this->notificarAdministradoresFilaGeral($duvida);

        $_SESSION['flash'] = 'Dúvida retomada — de volta à fila geral.';
        $this->redirecionar('home/administrativo');
    }

    /**
     * Administrador com escopo sobre o concurso da duvida - poder de
     * supervisao (responder/escalar/retomar QUALQUER duvida nao respondida
     * do(s) concurso(s) dele, nao so' as que estao com ele).
     */
    private function podeGerenciar(array $duvida)
    {
        if (!Auth::possuiPerfil('administrador')) {
            return false;
        }

        $concursosPermitidos = $this->perfis->concursosDoUsuario(Auth::usuarioId(), 'administrador');

        return $concursosPermitidos === null || in_array((int) $duvida['concurso_id'], $concursosPermitidos, true);
    }

    /**
     * Administrador no escopo (podeGerenciar) OU sou eu mesmo o responsavel
     * atual (Suporte, ou Administrador que recebeu escalonamento pessoal).
     */
    private function podeAgir(array $duvida)
    {
        if ($this->podeGerenciar($duvida)) {
            return true;
        }

        return $duvida['responsavel_atual_usuario_id'] !== null
            && (int) $duvida['responsavel_atual_usuario_id'] === (int) Auth::usuarioId();
    }

    private function emAtraso(array $duvida)
    {
        if ($duvida['status'] === 'respondida') {
            return false;
        }

        $referencia = $duvida['reaberta_em'] !== null ? $duvida['reaberta_em'] : $duvida['criado_em'];

        // Fase 30: calculo extraido pra App\Services\SlaService (usado
        // tambem por RequerimentoAdminController) - so' mudou de lugar,
        // mesma logica de antes.
        return \App\Services\SlaService::emAtraso($referencia);
    }

    /**
     * Administrador/suporte do concurso da duvida, exceto eu mesmo (nao faz
     * sentido "escalar pra mim mesmo") - mesmo padrao de
     * MentoriaAdminController::mentoresDisponiveis().
     */
    private function atendentesDisponiveis($concursoId)
    {
        $porId = [];

        foreach (['administrador', 'suporte', 'colaborador'] as $perfilChave) {
            foreach ($this->perfis->listarUsuariosPorPerfilConcurso($perfilChave, $concursoId) as $usuario) {
                if ((int) $usuario['id'] !== (int) Auth::usuarioId()) {
                    $porId[(int) $usuario['id']] = $usuario;
                }
            }
        }

        usort($porId, function ($a, $b) {
            return strcmp($a['nome'], $b['nome']);
        });

        return array_values($porId);
    }

    private function notificarEquipe(array $duvida, $titulo, $mensagem)
    {
        foreach ($this->equipes->listarParticipantes((int) $duvida['equipe_id']) as $participante) {
            foreach ($this->usuarioParticipante->usuariosDoParticipante($participante['id']) as $usuarioId) {
                $this->notificacoes->criar($usuarioId, 'duvida_respondida', $titulo, $mensagem, ['url' => url('duvida/ver/' . (int) $duvida['id'])]);
            }
        }
    }

    private function notificarResponsavel($usuarioId, array $duvida)
    {
        $this->notificacoes->criar(
            (int) $usuarioId,
            'duvida_escalada',
            'Dúvida escalada para você',
            '"' . $duvida['participante_nome'] . '" (equipe "' . $duvida['nome_equipe'] . '") - dúvida escalada pra você.',
            ['url' => url('duvidaAdmin/ver/' . (int) $duvida['id'])]
        );
    }

    private function notificarAdministradoresFilaGeral(array $duvida)
    {
        foreach ($this->perfis->listarUsuariosPorPerfilConcurso('administrador', (int) $duvida['concurso_id']) as $admin) {
            $this->notificacoes->criar(
                (int) $admin['id'],
                'duvida_nova',
                'Dúvida de volta à fila geral',
                '"' . $duvida['participante_nome'] . '" (equipe "' . $duvida['nome_equipe'] . '") - dúvida retomada, aguardando novo atendimento.',
                ['url' => url('duvidaAdmin/ver/' . (int) $duvida['id'])]
            );
        }
    }

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
}
