<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Mailer;
use App\Middleware\RoleMiddleware;
use App\Repositories\ConcursoRepository;
use App\Repositories\EquipeRepository;
use App\Repositories\GoogleConviteStatusRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\OficinaRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Repositories\UsuarioRepository;
use App\Services\GoogleCalendarSyncService;
use App\Services\PresencaMeetCapturaService;

/**
 * Fase 24: administrador/suporte cria horarios de oficina - encontro
 * coletivo com tema pre-definido conduzido pelo organizador do concurso
 * (sem "mentor" designado). Qualquer equipe interessada pode se inscrever,
 * sem exclusividade (diferente de MentoriaAdminController).
 */
class OficinaAdminController extends Controller
{
    private $oficinas;
    private $concursos;
    private $equipes;
    private $usuarioParticipante;
    private $notificacoes;
    private $usuarios;
    private $googleSync;
    private $conviteStatus;
    private $trilhas;
    private $etapas;

    public function __construct()
    {
        // Fase 29 (ajuste pos-push): exigirEmQualquerConcurso() na entrada +
        // exigir()/temPerfil() com o concurso resolvido dentro de cada acao -
        // exigir() sem concurso so' reconhece vinculo GLOBAL.
        RoleMiddleware::exigirEmQualquerConcurso(['administrador', 'suporte']);
        $this->oficinas = new OficinaRepository();
        $this->concursos = new ConcursoRepository();
        $this->equipes = new EquipeRepository();
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->notificacoes = new NotificacaoPainelRepository();
        $this->usuarios = new UsuarioRepository();
        $this->googleSync = new GoogleCalendarSyncService();
        $this->conviteStatus = new GoogleConviteStatusRepository();
        $this->trilhas = new TrilhaRepository();
        $this->etapas = new EtapaRepository();
    }

    public function index($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $concurso['id']);

        $horarios = $this->oficinas->listarPorConcurso($concursoId);

        foreach ($horarios as &$horario) {
            $horario['convite_status'] = !empty($horario['integracao_google'])
                ? $this->conviteStatus->listarComNomePorHorario('oficina', $horario['id'])
                : [];
        }
        unset($horario);

        $trilhaFiltro = !empty($_GET['trilha_id']) ? (int) $_GET['trilha_id'] : null;
        $etapaFiltro = $this->validarEtapaFiltro($trilhaFiltro, !empty($_GET['etapa_id']) ? (int) $_GET['etapa_id'] : null);
        $etapaSelecionada = $etapaFiltro !== null ? $this->etapas->buscarPorId($etapaFiltro) : null;

        $this->renderizar('admin/oficinas/index', [
            'concurso' => $concurso,
            'horarios' => $horarios,
            'trilhas' => $this->trilhas->listarPorConcurso($concursoId),
            'trilhaFiltro' => $trilhaFiltro,
            'etapasDaTrilha' => $trilhaFiltro !== null ? $this->etapas->listarPorTrilha($trilhaFiltro) : [],
            'etapaFiltro' => $etapaFiltro,
            'etapaAindaNaoIniciada' => $etapaSelecionada !== null
                && $etapaSelecionada['data_inicio'] !== null
                && date('Y-m-d H:i:s') < $etapaSelecionada['data_inicio'],
            'etapaSelecionada' => $etapaSelecionada,
            'equipesSemParticipacao' => $this->resolverEquipesSemParticipacao($concursoId, $trilhaFiltro, $etapaFiltro),
            'flash' => !empty($_SESSION['flash']) ? $_SESSION['flash'] : null,
        ], 'Oficinas de ' . $concurso['nome'], ['tipo' => 'oficinas', 'id' => (int) $concursoId]);

        unset($_SESSION['flash']);
    }

    public function novo($concursoId)
    {
        $concurso = $this->concursos->buscarPorId($concursoId);

        if ($concurso === null) {
            http_response_code(404);
            exit('Concurso não encontrado.');
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $concurso['id']);

        $organizador = $this->usuarios->buscarPorId(Auth::usuarioId());
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tema = trim(isset($_POST['tema']) ? $_POST['tema'] : '');
            $dataInicio = trim(isset($_POST['data_inicio']) ? $_POST['data_inicio'] : '');
            $dataFim = trim(isset($_POST['data_fim']) ? $_POST['data_fim'] : '');
            $linkMeet = trim(isset($_POST['link_meet']) ? $_POST['link_meet'] : '');
            $observacao = trim(isset($_POST['observacao']) ? $_POST['observacao'] : '');
            $integrarGoogle = !empty($_POST['integracao_google']);

            if ($tema === '') {
                $erro = 'Informe o tema da oficina.';
            } elseif ($dataInicio === '' || $dataFim === '') {
                $erro = 'Informe o início e o fim do horário.';
            } elseif (strtotime($dataFim) <= strtotime($dataInicio)) {
                $erro = 'O fim do horário deve ser depois do início.';
            } elseif ($integrarGoogle && ($organizador === null || !organizadorElegivelGoogle($organizador['email']))) {
                $erro = 'A integração com o Google Agenda só está disponível para quem loga com e-mail institucional @tjrr.jus.br.';
            } elseif ($integrarGoogle && $linkMeet !== '') {
                $erro = 'Com a integração com o Google Agenda ativa, o link do Meet é gerado automaticamente — não informe um link manual.';
            } elseif (!$integrarGoogle && $linkMeet !== '' && !linkHttpValido($linkMeet)) {
                $erro = 'O link do Google Meet deve começar com http:// ou https://.';
            } else {
                $id = $this->oficinas->criar(
                    $concursoId,
                    $tema,
                    $dataInicio,
                    $dataFim,
                    $linkMeet !== '' ? $linkMeet : null,
                    $observacao !== '' ? $observacao : null,
                    Auth::usuarioId(),
                    $integrarGoogle
                );

                if ($integrarGoogle) {
                    $resultado = $this->googleSync->criar(
                        $organizador['email'],
                        $this->dadosEventoGoogle($concurso, $tema, $dataInicio, $dataFim, $observacao),
                        'Oficinas ' . $concurso['nome']
                    );

                    if ($resultado !== null) {
                        $this->oficinas->atualizarGoogle($id, $resultado);
                    } else {
                        flashAlerta('Horário criado, mas não foi possível conectar com o Google Agenda agora. Você pode tentar novamente na listagem.');
                    }
                }

                $this->redirecionar('oficinaAdmin/index/' . $concursoId);
                return;
            }
        }

        $this->renderizar('admin/oficinas/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'organizadorElegivel' => $organizador !== null && organizadorElegivelGoogle($organizador['email']),
        ], 'Novo horário de oficina', ['tipo' => 'oficinas', 'id' => (int) $concursoId]);
    }

    private function dadosEventoGoogle(array $concurso, $tema, $dataInicio, $dataFim, $observacao)
    {
        $descricao = 'Oficina "' . $tema . '" do ' . $concurso['nome'] . '.';

        if ($observacao !== '') {
            $descricao .= ' ' . $observacao;
        }

        $descricao .= "\n\nDetalhes no sistema: " . urlAbsoluta('oficina/index');

        return [
            'titulo' => 'Oficina: ' . $tema . ' — ' . $concurso['nome'],
            'descricao' => $descricao,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
        ];
    }

    /**
     * Fase 31: etapa_id do GET so' vale se pertencer mesmo a trilha_id
     * selecionada - senao (trilha trocada mas o select de etapa ainda
     * mandou o valor antigo, ou etapa_id sem trilha_id) o filtro de etapa
     * e' ignorado silenciosamente, caindo no criterio de homologacao de
     * cadastro (mesmo comportamento de "nenhuma etapa selecionada").
     */
    private function validarEtapaFiltro($trilhaFiltro, $etapaId)
    {
        if ($trilhaFiltro === null || $etapaId === null) {
            return null;
        }

        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null || (int) $etapa['trilha_id'] !== $trilhaFiltro) {
            return null;
        }

        return $etapaId;
    }

    /**
     * Fase 31: "aprovada" muda de significado conforme a etapa escolhida -
     * na etapa 1 (cadastro) e' homologacao de integrantes; nas demais e' a
     * classificacao (resultados_etapa.classificado) na etapa ANTERIOR da
     * trilha, mesmo criterio que libera o acesso a etapa atual em
     * AcessoEtapaService::motivoBloqueio. Sem etapa selecionada, usa
     * homologacao de cadastro pra manter o comportamento anterior a esta
     * parte (filtro so' por trilha, ou nenhum filtro).
     */
    private function resolverEquipesSemParticipacao($concursoId, $trilhaFiltro, $etapaFiltro)
    {
        if ($trilhaFiltro === null || $etapaFiltro === null) {
            return $this->equipes->listarHomologadasSemParticipacaoEmEventos($concursoId, $trilhaFiltro);
        }

        $etapa = $this->etapas->buscarPorId($etapaFiltro);

        if ((int) $etapa['ordem'] <= 1) {
            return $this->equipes->listarHomologadasSemParticipacaoEmEventos($concursoId, $trilhaFiltro);
        }

        $etapaAnterior = $this->etapas->buscarAnteriorNaTrilha($trilhaFiltro, (int) $etapa['ordem']);

        if ($etapaAnterior === null) {
            return $this->equipes->listarHomologadasSemParticipacaoEmEventos($concursoId, $trilhaFiltro);
        }

        return $this->equipes->listarClassificadasNaEtapaSemParticipacaoEmEventos($concursoId, $trilhaFiltro, (int) $etapaAnterior['id']);
    }

    /**
     * Fase 29 (Melhoria 4): fragmento para o modal "Ver equipes inscritas" -
     * ate' aqui a tela so' mostrava o quantitativo (total_inscritos), sem
     * como saber quais equipes eram. Reaproveita OficinaRepository::
     * listarInscritos(), que ja existia (usado so' internamente por
     * remover() pra notificar as equipes).
     */
    public function inscritos($id)
    {
        $horario = $this->oficinas->buscarPorId($id);

        if ($horario === null) {
            http_response_code(404);
            exit('Horário não encontrado.');
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $horario['concurso_id']);

        $this->renderizar('admin/oficinas/inscritos', [
            'horario' => $horario,
            'inscritos' => $this->oficinas->listarInscritos($id),
        ]);
    }

    /**
     * Fase 32: relatorio de presenca real de um horario ja encerrado -
     * cruza convidado -> RSVP -> presenca no Meet. A captura em si e'
     * automatica (cron), esta tela so' le' o que ja foi capturado.
     */
    public function presenca($id)
    {
        $horario = $this->oficinas->buscarPorId($id);

        if ($horario === null) {
            http_response_code(404);
            exit('Horário não encontrado.');
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $horario['concurso_id']);

        $captura = new PresencaMeetCapturaService();
        $relatorio = $captura->montarRelatorio(
            'oficina',
            $horario,
            $this->conviteStatus->listarComNomePorHorario('oficina', $id)
        );

        $this->renderizar('admin/oficinas/presenca', [
            'horario' => $horario,
            'tipo' => 'oficina',
            'rotaModulo' => 'oficinaAdmin',
            'convidados' => $relatorio['convidados'],
            'naoIdentificados' => $relatorio['nao_identificados'],
            'maxTentativas' => PresencaMeetCapturaService::MAX_TENTATIVAS,
        ]);
    }

    /**
     * Fase 32: devolve um horario a fila do cron. Unico caminho de recuperacao
     * quando a captura ficou 'indisponivel' por causa sistemica (escopo nao
     * autorizado, por exemplo) que depois foi corrigida - sem isto, so'
     * restaria UPDATE direto no banco. Auditado por ser acao de usuario, ao
     * contrario da sincronizacao automatica feita pelo cron.
     */
    public function reprocessarPresenca()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $horario = $this->oficinas->buscarPorId($id);

        if ($horario === null) {
            http_response_code(404);
            exit('Horário não encontrado.');
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $horario['concurso_id']);

        $this->oficinas->atualizarPresenca($id, [
            'presenca_status' => 'pendente',
            'presenca_tentativas' => 0,
            'presenca_ultima_tentativa_em' => null,
        ]);

        Auditoria::registrar('reprocessar_presenca', 'oficina_horarios', $id, [
            'presenca_status' => $horario['presenca_status'],
            'presenca_tentativas' => $horario['presenca_tentativas'],
        ], ['presenca_status' => 'pendente', 'presenca_tentativas' => 0]);

        flashSucesso('A presença deste horário voltou para a fila e será buscada na próxima varredura automática.');
        $this->redirecionar('oficinaAdmin/index/' . (int) $horario['concurso_id']);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);
        $horario = $this->oficinas->buscarPorId($id);

        if ($horario === null) {
            $this->redirecionar('oficinaAdmin/index/' . $concursoId);
            return;
        }

        $souDono = (int) $horario['criado_por'] === (int) Auth::usuarioId();

        // Fase 29 (ajuste pos-push): Auth::possuiPerfil('administrador') era
        // global - um administrador escopado a OUTRO concurso passava aqui
        // igual, mesmo o horario sendo de um concurso fora do escopo dele.
        // Auth::temPerfil() com o concurso do horario resolve os dois casos
        // (administrador global continua passando; administrador de outro
        // concurso passa a ser barrado tambem).
        if (!$souDono && !Auth::temPerfil('administrador', $horario['concurso_id'])) {
            http_response_code(403);
            exit('Acesso negado: este horário pertence a outro organizador.');
        }

        foreach ($this->oficinas->listarInscritos($id) as $inscrito) {
            $this->notificarEquipe(
                (int) $inscrito['equipe_id'],
                'Oficina cancelada',
                'A oficina "' . $horario['tema'] . '" de ' . formatarDataHora($horario['data_inicio']) . ' ' . sufixoFusoHorario() . ' foi cancelada.'
            );
        }

        if (!empty($horario['integracao_google'])) {
            $organizador = $this->usuarios->buscarPorId($horario['criado_por']);

            if ($organizador !== null) {
                $this->googleSync->cancelar($organizador['email'], $horario['google_calendar_id'], $horario['google_event_id']);
            }

            $this->conviteStatus->removerPorHorario('oficina', $id);
        }

        $this->oficinas->remover($id);
        $_SESSION['flash'] = 'Horário removido.';
        $this->redirecionar('oficinaAdmin/index/' . $concursoId);
    }

    /**
     * Fase 31: mesmo padrao de MentoriaAdminController::verificarNovamente()
     * - ver comentario la'. Attendees recomputados a partir de TODAS as
     * equipes atualmente inscritas (N:N, diferente de mentoria).
     */
    public function verificarNovamente()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);
        $horario = $this->oficinas->buscarPorId($id);

        if ($horario === null || empty($horario['integracao_google'])) {
            $this->redirecionar('oficinaAdmin/index/' . $concursoId);
            return;
        }

        $souDono = (int) $horario['criado_por'] === (int) Auth::usuarioId();

        if (!$souDono && !Auth::temPerfil('administrador', $horario['concurso_id'])) {
            http_response_code(403);
            exit('Acesso negado: este horário pertence a outro organizador.');
        }

        $organizador = $this->usuarios->buscarPorId($horario['criado_por']);
        $concurso = $this->concursos->buscarPorId($horario['concurso_id']);

        if ($organizador === null || $concurso === null) {
            $this->redirecionar('oficinaAdmin/index/' . $concursoId);
            return;
        }

        if (empty($horario['google_event_id'])) {
            $resultado = $this->googleSync->criar(
                $organizador['email'],
                $this->dadosEventoGoogle($concurso, $horario['tema'], $horario['data_inicio'], $horario['data_fim'], (string) $horario['observacao']),
                'Oficinas ' . $concurso['nome']
            );

            if ($resultado !== null) {
                $this->oficinas->atualizarGoogle($id, $resultado);

                $equipeIds = array_column($this->oficinas->listarInscritos($id), 'equipe_id');

                if (!empty($equipeIds)) {
                    $emails = $this->equipes->listarEmailsPorEquipes(array_map('intval', $equipeIds));
                    $this->googleSync->sincronizarAttendees(
                        'oficina', $id, $organizador['email'], $resultado['google_calendar_id'], $resultado['google_event_id'],
                        array_keys($emails), $emails
                    );
                }

                $_SESSION['flash'] = 'Integração com o Google Agenda concluída.';
            } else {
                flashErro('Ainda não foi possível conectar com o Google Agenda. Tente novamente em alguns instantes.');
            }
        } else {
            $resultado = $this->googleSync->reconciliar('oficina', $horario, $organizador['email']);

            if ($resultado !== null) {
                $this->oficinas->atualizarGoogle($id, $resultado);
                $_SESSION['flash'] = 'Status atualizado.';
            } else {
                flashAlerta('Nenhuma novidade agora (ou aguarde um pouco antes de verificar de novo).');
            }
        }

        $this->redirecionar('oficinaAdmin/index/' . $concursoId);
    }

    private function notificarEquipe($equipeId, $titulo, $mensagem)
    {
        foreach ($this->equipes->listarParticipantes($equipeId) as $participante) {
            foreach ($this->usuarioParticipante->usuariosDoParticipante($participante['id']) as $usuarioId) {
                $this->notificacoes->criar($usuarioId, 'oficina', $titulo, $mensagem, ['url' => url('oficina/index')]);
            }

            if (!empty($participante['email'])) {
                Mailer::enviar($participante['email'], $titulo, '<p>' . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . '</p>');
            }
        }
    }
}
