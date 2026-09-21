<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Repositories\ApresentacaoPitchRepository;
use App\Repositories\AvaliadorDesignacaoRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\GoogleConviteStatusRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\UsuarioRepository;
use App\Services\GoogleCalendarSyncService;
use App\Services\PresencaMeetCapturaService;

/**
 * Fase 38B: administração da grade de apresentações de pitch da Etapa 3
 * (Edital NPI n.9/2026, item 2.3). Mesmo padrão de MentoriaAdminController,
 * mas o evento no Google só é criado no momento da reserva (participante
 * ou atribuição manual), não na criação do slot — a modalidade
 * (presencial/online) só é conhecida nesse momento.
 */
class ApresentacaoPitchAdminController extends Controller
{
    private $apresentacoes;
    private $etapas;
    private $equipes;
    private $submissoes;
    private $designacoes;
    private $usuarios;
    private $googleSync;
    private $conviteStatus;

    public function __construct()
    {
        RoleMiddleware::exigirEmQualquerConcurso(['administrador', 'suporte']);
        $this->apresentacoes = new ApresentacaoPitchRepository();
        $this->etapas = new EtapaRepository();
        $this->equipes = new EquipeRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->designacoes = new AvaliadorDesignacaoRepository();
        $this->usuarios = new UsuarioRepository();
        $this->googleSync = new GoogleCalendarSyncService();
        $this->conviteStatus = new GoogleConviteStatusRepository();
    }

    public function index($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $this->concursoIdDaEtapa($etapa));

        $slots = $this->apresentacoes->listarPorEtapa($etapaId);

        foreach ($slots as &$slot) {
            $slot['convite_status'] = !empty($slot['integracao_google'])
                ? $this->conviteStatus->listarComNomePorHorario('apresentacao_pitch', $slot['id'])
                : [];
        }
        unset($slot);

        $this->renderizar('admin/apresentacaoPitch/index', [
            'etapa' => $etapa,
            'slots' => $slots,
            'config' => $this->apresentacoes->buscarConfig($etapaId),
            'equipesParaAtribuir' => $this->equipesSemSlot($etapaId),
        ], 'Apresentação de pitch: ' . $etapa['nome'], ['tipo' => 'apresentacao_pitch', 'id' => (int) $etapaId]);

        unset($_SESSION['flash']);
    }

    public function novoSlot($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $this->concursoIdDaEtapa($etapa));

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dataInicio = trim(isset($_POST['data_inicio']) ? $_POST['data_inicio'] : '');
            $dataFim = trim(isset($_POST['data_fim']) ? $_POST['data_fim'] : '');

            if ($dataInicio === '' || $dataFim === '' || strtotime($dataFim) <= strtotime($dataInicio)) {
                flashErro('Informe início e fim válidos (o fim precisa ser depois do início).');
            } else {
                $this->apresentacoes->criarSlot($etapaId, $dataInicio, $dataFim);
                flashSucesso('Horário criado.');
            }
        }

        $this->redirecionar('apresentacaoPitchAdmin/index/' . (int) $etapaId);
    }

    /**
     * Fase 38B (correcao pos-teste de fumaca): so' para slot ainda VAGO -
     * um slot ja reservado nao pode ter as datas alteradas por aqui
     * (mudaria o compromisso sem avisar a equipe/banca), mesmo espirito da
     * trava exigirAindaNaoIniciado() de Mentoria.
     */
    public function editarSlot($id)
    {
        $slot = $this->apresentacoes->buscarPorId($id);

        if ($slot === null) {
            http_response_code(404);
            exit('Horário não encontrado.');
        }

        if ($slot['equipe_id'] !== null) {
            http_response_code(403);
            exit('Este horário já foi reservado e não pode mais ser editado: remova e crie outro, se necessário.');
        }

        $etapa = $this->etapas->buscarPorId($slot['etapa_id']);
        RoleMiddleware::exigir(['administrador', 'suporte'], $this->concursoIdDaEtapa($etapa));

        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dataInicio = trim(isset($_POST['data_inicio']) ? $_POST['data_inicio'] : '');
            $dataFim = trim(isset($_POST['data_fim']) ? $_POST['data_fim'] : '');

            if ($dataInicio === '' || $dataFim === '' || strtotime($dataFim) <= strtotime($dataInicio)) {
                $erro = 'Informe início e fim válidos (o fim precisa ser depois do início).';
            } else {
                $this->apresentacoes->atualizarDatas($id, $dataInicio, $dataFim);
                flashSucesso('Horário atualizado.');
                $this->redirecionar('apresentacaoPitchAdmin/index/' . (int) $slot['etapa_id']);
                return;
            }
        }

        $this->renderizar('admin/apresentacaoPitch/editarSlot', [
            'erro' => $erro,
            'slot' => $slot,
            'etapa' => $etapa,
        ], 'Editar horário: ' . $etapa['nome']);
    }

    public function removerSlot()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $slot = $this->apresentacoes->buscarPorId($id);

        if ($slot === null) {
            $this->redirecionar('apresentacaoPitchAdmin/index/0');
            return;
        }

        if ($slot['equipe_id'] !== null) {
            flashErro('Não é possível remover um horário já reservado: cancele a reserva primeiro.');
            $this->redirecionar('apresentacaoPitchAdmin/index/' . (int) $slot['etapa_id']);
            return;
        }

        $this->apresentacoes->remover($id);
        flashSucesso('Horário removido.');
        $this->redirecionar('apresentacaoPitchAdmin/index/' . (int) $slot['etapa_id']);
    }

    public function salvarConfig()
    {
        $etapaId = (int) (isset($_POST['etapa_id']) ? $_POST['etapa_id'] : 0);
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            http_response_code(404);
            exit('Etapa não encontrada.');
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $this->concursoIdDaEtapa($etapa));

        $janelaInicio = trim(isset($_POST['janela_escolha_inicio']) ? $_POST['janela_escolha_inicio'] : '');
        $janelaFim = trim(isset($_POST['janela_escolha_fim']) ? $_POST['janela_escolha_fim'] : '');
        $emailOrganizador = trim(isset($_POST['email_organizador_google']) ? $_POST['email_organizador_google'] : '');
        $enderecoPresencial = trim(isset($_POST['endereco_presencial']) ? $_POST['endereco_presencial'] : '');

        $this->apresentacoes->salvarConfig(
            $etapaId,
            $janelaInicio !== '' ? $janelaInicio : null,
            $janelaFim !== '' ? $janelaFim : null,
            $emailOrganizador !== '' ? $emailOrganizador : null,
            $enderecoPresencial !== '' ? $enderecoPresencial : null
        );

        flashSucesso('Configuração salva.');
        $this->redirecionar('apresentacaoPitchAdmin/index/' . $etapaId);
    }

    /**
     * Item 2.3.8 do edital: equipe que não se manifestou na janela recebe
     * data/horário atribuídos pelo NPI, sempre em modalidade online.
     */
    public function atribuir()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $equipeId = (int) (isset($_POST['equipe_id']) ? $_POST['equipe_id'] : 0);
        $slot = $this->apresentacoes->buscarPorId($id);

        if ($slot === null) {
            $this->redirecionar('apresentacaoPitchAdmin/index/0');
            return;
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $this->concursoIdDaEtapa($this->etapas->buscarPorId($slot['etapa_id'])));

        $equipe = $this->equipes->buscarPorId($equipeId);

        if ($equipe === null) {
            flashErro('Equipe não encontrada.');
            $this->redirecionar('apresentacaoPitchAdmin/index/' . (int) $slot['etapa_id']);
            return;
        }

        if (!$this->apresentacoes->atribuirManual($id, $equipeId)) {
            flashAlerta('Esse horário acabou de ser reservado por outra equipe.');
            $this->redirecionar('apresentacaoPitchAdmin/index/' . (int) $slot['etapa_id']);
            return;
        }

        $this->sincronizarGoogleAposReserva($id, (int) $slot['etapa_id'], $equipeId);

        flashSucesso('Equipe atribuída ao horário (modalidade online).');
        $this->redirecionar('apresentacaoPitchAdmin/index/' . (int) $slot['etapa_id']);
    }

    /**
     * Cria o evento no Google (se ainda não existe) e sincroniza attendees
     * (equipe + banca designada para a submissão da equipe nesta etapa).
     * Reaproveitado por reservar() do lado do participante e por
     * atribuir() aqui — mesma lógica, chamada em dois pontos, como já
     * acontece entre MentoriaAdminController/MentoriaController.
     */
    private function sincronizarGoogleAposReserva($slotId, $etapaId, $equipeId)
    {
        $config = $this->apresentacoes->buscarConfig($etapaId);

        if ($config === null || empty($config['email_organizador_google']) || !organizadorElegivelGoogle($config['email_organizador_google'])) {
            return;
        }

        $slot = $this->apresentacoes->buscarPorId($slotId);
        $emailOrganizador = $config['email_organizador_google'];
        $etapa = $this->etapas->buscarPorId($etapaId);

        $resultado = $this->googleSync->criar(
            $emailOrganizador,
            $this->dadosEventoGoogle($etapa, $slot['data_inicio'], $slot['data_fim'], $equipeId),
            'Apresentações de pitch: ' . $etapa['nome']
        );

        if ($resultado === null) {
            flashAlerta('Reserva salva, mas não foi possível conectar com o Google Agenda agora. Use "Verificar novamente" na listagem.');
            return;
        }

        $this->apresentacoes->marcarIntegracaoGoogle($slotId, true);
        $this->apresentacoes->atualizarGoogle($slotId, $resultado);

        $emails = $this->emailsDaBanca($etapaId, $equipeId);

        $sincronizacao = $this->googleSync->sincronizarAttendees(
            'apresentacao_pitch', $slotId, $emailOrganizador, $resultado['google_calendar_id'], $resultado['google_event_id'],
            array_keys($emails), $emails
        );

        if ($sincronizacao !== null) {
            $this->apresentacoes->atualizarGoogle($slotId, $sincronizacao);
        }
    }

    /**
     * Equipe + avaliadores designados para a submissão da equipe nesta
     * etapa (a banca formal, ver plano da Fase 38B) - reaproveita a
     * designação já feita para a avaliação, não recria "quem é a banca".
     */
    private function emailsDaBanca($etapaId, $equipeId)
    {
        $emails = $this->equipes->listarEmailsPorEquipes([$equipeId]);

        $submissao = $this->submissoes->buscarPorEquipeEEtapa($equipeId, $etapaId);

        if ($submissao !== null) {
            foreach ($this->designacoes->listarPorSubmissao($submissao['id']) as $designacao) {
                $avaliador = $this->usuarios->buscarPorId($designacao['usuario_id']);

                if ($avaliador !== null && !empty($avaliador['email'])) {
                    $emails[$avaliador['email']] = null;
                }
            }
        }

        return $emails;
    }

    private function dadosEventoGoogle(array $etapa, $dataInicio, $dataFim, $equipeId)
    {
        $equipe = $this->equipes->buscarPorId($equipeId);

        return [
            'titulo' => 'Apresentação de pitch: ' . ($equipe !== null ? $equipe['nome_equipe'] : 'Equipe #' . $equipeId),
            'descricao' => 'Apresentação de pitch da Etapa 3 (' . $etapa['nome'] . ").\n\nDetalhes no sistema: " . urlAbsoluta('apresentacaoPitch/index'),
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
        ];
    }

    public function presenca($id)
    {
        $slot = $this->apresentacoes->buscarPorId($id);

        if ($slot === null) {
            http_response_code(404);
            exit('Horário não encontrado.');
        }

        $etapa = $this->etapas->buscarPorId($slot['etapa_id']);
        RoleMiddleware::exigir(['administrador', 'suporte'], $this->concursoIdDaEtapa($etapa));

        $captura = new PresencaMeetCapturaService();
        $relatorio = $captura->montarRelatorio(
            'apresentacao_pitch',
            $slot,
            $this->conviteStatus->listarComNomePorHorario('apresentacao_pitch', $id)
        );

        $this->renderizar('admin/apresentacaoPitch/presenca', [
            'horario' => $slot,
            'tipo' => 'apresentacao_pitch',
            'rotaModulo' => 'apresentacaoPitchAdmin',
            'convidados' => $relatorio['convidados'],
            'naoIdentificados' => $relatorio['nao_identificados'],
            'maxTentativas' => PresencaMeetCapturaService::MAX_TENTATIVAS,
        ]);
    }

    public function reprocessarPresenca()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $slot = $this->apresentacoes->buscarPorId($id);

        if ($slot === null) {
            http_response_code(404);
            exit('Horário não encontrado.');
        }

        $etapa = $this->etapas->buscarPorId($slot['etapa_id']);
        RoleMiddleware::exigir(['administrador', 'suporte'], $this->concursoIdDaEtapa($etapa));

        $this->apresentacoes->atualizarPresenca($id, [
            'presenca_status' => 'pendente',
            'presenca_tentativas' => 0,
            'presenca_ultima_tentativa_em' => null,
        ]);

        Auditoria::registrar('reprocessar_presenca', 'apresentacoes_pitch', $id, [
            'presenca_status' => $slot['presenca_status'],
            'presenca_tentativas' => $slot['presenca_tentativas'],
        ], ['presenca_status' => 'pendente', 'presenca_tentativas' => 0]);

        flashSucesso('A presença deste horário voltou para a fila e será buscada na próxima varredura automática.');
        $this->redirecionar('apresentacaoPitchAdmin/index/' . (int) $slot['etapa_id']);
    }

    public function verificarNovamente()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $slot = $this->apresentacoes->buscarPorId($id);

        if ($slot === null || $slot['equipe_id'] === null) {
            $this->redirecionar('apresentacaoPitchAdmin/index/0');
            return;
        }

        $etapaId = (int) $slot['etapa_id'];
        $etapa = $this->etapas->buscarPorId($etapaId);
        RoleMiddleware::exigir(['administrador', 'suporte'], $this->concursoIdDaEtapa($etapa));

        $config = $this->apresentacoes->buscarConfig($etapaId);

        if ($config === null || empty($config['email_organizador_google'])) {
            flashErro('Configure o e-mail organizador do Google Agenda antes.');
            $this->redirecionar('apresentacaoPitchAdmin/index/' . $etapaId);
            return;
        }

        $emailOrganizador = $config['email_organizador_google'];

        if (empty($slot['google_event_id'])) {
            $this->sincronizarGoogleAposReserva($id, $etapaId, (int) $slot['equipe_id']);
            $_SESSION['flash'] = 'Integração com o Google Agenda concluída.';
        } else {
            $resultado = $this->googleSync->reconciliar('apresentacao_pitch', $slot, $emailOrganizador);

            if ($resultado !== null) {
                $this->apresentacoes->atualizarGoogle($id, $resultado);
                $_SESSION['flash'] = 'Status atualizado.';
            } else {
                flashAlerta('Nenhuma novidade agora (ou aguarde um pouco antes de verificar de novo).');
            }
        }

        $this->redirecionar('apresentacaoPitchAdmin/index/' . $etapaId);
    }

    private function equipesSemSlot($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);
        $etapaAnterior = $this->etapas->buscarAnteriorNaTrilha((int) $etapa['trilha_id'], (int) $etapa['ordem']);

        if ($etapaAnterior === null) {
            return [];
        }

        return $this->apresentacoes->listarEquipesClassificadasSemSlot($etapaId, (int) $etapaAnterior['id']);
    }

    private function concursoIdDaEtapa(array $etapa = null)
    {
        if ($etapa === null) {
            return null;
        }

        $trilha = (new \App\Repositories\TrilhaRepository())->buscarPorId($etapa['trilha_id']);

        return $trilha !== null ? (int) $trilha['concurso_id'] : null;
    }
}
