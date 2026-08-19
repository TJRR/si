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
use App\Repositories\MentoriaRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\TrilhaRepository;
use App\Repositories\UsuarioParticipanteRepository;
use App\Repositories\UsuarioRepository;
use App\Services\GoogleCalendarSyncService;
use App\Services\PresencaMeetCapturaService;

/**
 * Fase 19 (#106): qualquer administrador/suporte cria horarios de
 * mentoria - por padrao pra si mesmo, mas pode escolher outro
 * administrador/suporte como mentor do horario. Quem pode editar/remover
 * depois e' quem FICOU como mentor (mentor_usuario_id), nao
 * necessariamente quem criou - Admin global sempre pode, pra moderacao.
 */
class MentoriaAdminController extends Controller
{
    private $mentorias;
    private $concursos;
    private $equipes;
    private $usuarioParticipante;
    private $notificacoes;
    private $perfis;
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
        $this->mentorias = new MentoriaRepository();
        $this->concursos = new ConcursoRepository();
        $this->equipes = new EquipeRepository();
        $this->usuarioParticipante = new UsuarioParticipanteRepository();
        $this->notificacoes = new NotificacaoPainelRepository();
        $this->perfis = new PerfilRepository();
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

        $horarios = $this->mentorias->listarPorConcurso($concursoId);

        foreach ($horarios as &$horario) {
            $horario['convite_status'] = !empty($horario['integracao_google'])
                ? $this->conviteStatus->listarComNomePorHorario('mentoria', $horario['id'])
                : [];
        }
        unset($horario);

        $trilhaFiltro = !empty($_GET['trilha_id']) ? (int) $_GET['trilha_id'] : null;
        $etapaFiltro = $this->validarEtapaFiltro($trilhaFiltro, !empty($_GET['etapa_id']) ? (int) $_GET['etapa_id'] : null);
        $etapaSelecionada = $etapaFiltro !== null ? $this->etapas->buscarPorId($etapaFiltro) : null;

        $this->renderizar('admin/mentorias/index', [
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
        ], 'Mentorias de ' . $concurso['nome'], ['tipo' => 'mentorias', 'id' => (int) $concursoId]);

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

        $mentores = $this->mentoresDisponiveis($concursoId);
        $erro = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dataInicio = trim(isset($_POST['data_inicio']) ? $_POST['data_inicio'] : '');
            $dataFim = trim(isset($_POST['data_fim']) ? $_POST['data_fim'] : '');
            $linkMeet = trim(isset($_POST['link_meet']) ? $_POST['link_meet'] : '');
            $observacao = trim(isset($_POST['observacao']) ? $_POST['observacao'] : '');
            $mentorEscolhido = (int) (isset($_POST['mentor_usuario_id']) ? $_POST['mentor_usuario_id'] : 0);
            $mentorValido = in_array($mentorEscolhido, array_column($mentores, 'id'), false);
            $integrarGoogle = !empty($_POST['integracao_google']);
            $mentorUsuario = null;

            foreach ($mentores as $mentor) {
                if ((int) $mentor['id'] === $mentorEscolhido) {
                    $mentorUsuario = $mentor;
                    break;
                }
            }

            if ($dataInicio === '' || $dataFim === '') {
                $erro = 'Informe o início e o fim do horário.';
            } elseif (strtotime($dataFim) <= strtotime($dataInicio)) {
                $erro = 'O fim do horário deve ser depois do início.';
            } elseif (!$mentorValido) {
                $erro = 'Selecione um mentor válido (administrador ou suporte).';
            } elseif ($integrarGoogle && !organizadorElegivelGoogle($mentorUsuario['email'])) {
                $erro = 'A integração com o Google Agenda só está disponível para mentores com e-mail institucional @tjrr.jus.br.';
            } elseif ($integrarGoogle && $linkMeet !== '') {
                $erro = 'Com a integração com o Google Agenda ativa, o link do Meet é gerado automaticamente — não informe um link manual.';
            } elseif (!$integrarGoogle && $linkMeet !== '' && !linkHttpValido($linkMeet)) {
                $erro = 'O link do Google Meet deve começar com http:// ou https://.';
            } else {
                $id = $this->mentorias->criar($concursoId, $mentorEscolhido, $dataInicio, $dataFim, $linkMeet !== '' ? $linkMeet : null, $observacao !== '' ? $observacao : null, $integrarGoogle);

                if ($integrarGoogle) {
                    $resultado = $this->googleSync->criar(
                        $mentorUsuario['email'],
                        $this->dadosEventoGoogle($concurso, $dataInicio, $dataFim, $observacao),
                        'Mentorias ' . $concurso['nome']
                    );

                    if ($resultado !== null) {
                        $this->mentorias->atualizarGoogle($id, $resultado);
                    } else {
                        flashAlerta('Horário criado, mas não foi possível conectar com o Google Agenda agora. Você pode tentar novamente na listagem.');
                    }
                }

                $this->redirecionar('mentoriaAdmin/index/' . $concursoId);
                return;
            }
        }

        $this->renderizar('admin/mentorias/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'mentores' => $mentores,
        ], 'Novo horário de mentoria', ['tipo' => 'mentorias', 'id' => (int) $concursoId]);
    }

    /**
     * Fase 31: titulo/descricao do evento no Google Agenda - descricao
     * inclui o link de volta pro compromisso no sistema (RF6 do documento
     * original: mesmo com varios convites do Google no mesmo dia, o
     * usuario consegue confirmar qual e' qual pelo proprio sistema).
     */
    private function dadosEventoGoogle(array $concurso, $dataInicio, $dataFim, $observacao)
    {
        $descricao = 'Mentoria do ' . $concurso['nome'] . '.';

        if ($observacao !== '') {
            $descricao .= ' ' . $observacao;
        }

        $descricao .= "\n\nDetalhes no sistema: " . urlAbsoluta('mentoria/index');

        return [
            'titulo' => 'Mentoria — ' . $concurso['nome'],
            'descricao' => $descricao,
            'data_inicio' => $dataInicio,
            'data_fim' => $dataFim,
        ];
    }

    /**
     * Administrador/suporte globais (concurso_id NULL) + os escopados a
     * este concurso, sem duplicar quem tiver os dois perfis.
     */
    private function mentoresDisponiveis($concursoId)
    {
        $porId = [];

        foreach (['administrador', 'suporte'] as $perfilChave) {
            foreach ($this->perfis->listarUsuariosPorPerfilConcurso($perfilChave, $concursoId) as $usuario) {
                $porId[(int) $usuario['id']] = $usuario;
            }
        }

        usort($porId, function ($a, $b) {
            return strcmp($a['nome'], $b['nome']);
        });

        return array_values($porId);
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
     * Fase 32: relatorio de presenca real de um horario ja encerrado -
     * cruza convidado -> RSVP -> presenca no Meet. A captura em si e'
     * automatica (cron), esta tela so' le' o que ja foi capturado.
     */
    public function presenca($id)
    {
        $horario = $this->mentorias->buscarPorId($id);

        if ($horario === null) {
            http_response_code(404);
            exit('Horário não encontrado.');
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $horario['concurso_id']);

        $captura = new PresencaMeetCapturaService();
        $relatorio = $captura->montarRelatorio(
            'mentoria',
            $horario,
            $this->conviteStatus->listarComNomePorHorario('mentoria', $id)
        );

        $this->renderizar('admin/mentorias/presenca', [
            'horario' => $horario,
            'tipo' => 'mentoria',
            'rotaModulo' => 'mentoriaAdmin',
            'convidados' => $relatorio['convidados'],
            'naoIdentificados' => $relatorio['nao_identificados'],
            'maxTentativas' => PresencaMeetCapturaService::MAX_TENTATIVAS,
        ]);
    }

    /**
     * Fase 32: devolve um horario a fila do cron - ver
     * OficinaAdminController::reprocessarPresenca() para a justificativa.
     */
    public function reprocessarPresenca()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $horario = $this->mentorias->buscarPorId($id);

        if ($horario === null) {
            http_response_code(404);
            exit('Horário não encontrado.');
        }

        RoleMiddleware::exigir(['administrador', 'suporte'], $horario['concurso_id']);

        $this->mentorias->atualizarPresenca($id, [
            'presenca_status' => 'pendente',
            'presenca_tentativas' => 0,
            'presenca_ultima_tentativa_em' => null,
        ]);

        Auditoria::registrar('reprocessar_presenca', 'mentoria_horarios', $id, [
            'presenca_status' => $horario['presenca_status'],
            'presenca_tentativas' => $horario['presenca_tentativas'],
        ], ['presenca_status' => 'pendente', 'presenca_tentativas' => 0]);

        flashSucesso('A presença deste horário voltou para a fila e será buscada na próxima varredura automática.');
        $this->redirecionar('mentoriaAdmin/index/' . (int) $horario['concurso_id']);
    }

    public function remover()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);
        $horario = $this->mentorias->buscarPorId($id);

        if ($horario === null) {
            $this->redirecionar('mentoriaAdmin/index/' . $concursoId);
            return;
        }

        $souDono = (int) $horario['mentor_usuario_id'] === (int) Auth::usuarioId();

        // Fase 29 (ajuste pos-push): Auth::possuiPerfil('administrador') era
        // global - trocado por temPerfil() com o concurso do horario, mesmo
        // ajuste de OficinaAdminController::remover().
        if (!$souDono && !Auth::temPerfil('administrador', $horario['concurso_id'])) {
            http_response_code(403);
            exit('Acesso negado: este horário pertence a outro mentor.');
        }

        if ($horario['equipe_id'] !== null) {
            $this->notificarEquipe(
                (int) $horario['equipe_id'],
                'Horário de mentoria cancelado',
                'O mentor cancelou o horário de ' . formatarDataHora($horario['data_inicio']) . ' ' . sufixoFusoHorario() . ' que sua equipe havia reservado.'
            );
        }

        if (!empty($horario['integracao_google'])) {
            $mentor = $this->usuarios->buscarPorId($horario['mentor_usuario_id']);

            if ($mentor !== null) {
                $this->googleSync->cancelar($mentor['email'], $horario['google_calendar_id'], $horario['google_event_id']);
            }

            $this->conviteStatus->removerPorHorario('mentoria', $id);
        }

        $this->mentorias->remover($id);
        $_SESSION['flash'] = 'Horário removido.';
        $this->redirecionar('mentoriaAdmin/index/' . $concursoId);
    }

    /**
     * Fase 31: botao unico "Verificar/Tentar novamente" - cobre tanto "o
     * evento nunca chegou a ser criado no Google" (retry completo) quanto
     * "Meet ainda pendente / RSVP desatualizado" (reconciliacao sob
     * demanda, throttled). Sem fila/cron no projeto - essa e' a unica forma
     * de reprocessar uma integracao que falhou na criacao.
     */
    public function verificarNovamente()
    {
        $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
        $concursoId = (int) (isset($_POST['concurso_id']) ? $_POST['concurso_id'] : 0);
        $horario = $this->mentorias->buscarPorId($id);

        if ($horario === null || empty($horario['integracao_google'])) {
            $this->redirecionar('mentoriaAdmin/index/' . $concursoId);
            return;
        }

        $souDono = (int) $horario['mentor_usuario_id'] === (int) Auth::usuarioId();

        if (!$souDono && !Auth::temPerfil('administrador', $horario['concurso_id'])) {
            http_response_code(403);
            exit('Acesso negado: este horário pertence a outro mentor.');
        }

        $mentor = $this->usuarios->buscarPorId($horario['mentor_usuario_id']);
        $concurso = $this->concursos->buscarPorId($horario['concurso_id']);

        if ($mentor === null || $concurso === null) {
            $this->redirecionar('mentoriaAdmin/index/' . $concursoId);
            return;
        }

        if (empty($horario['google_event_id'])) {
            $resultado = $this->googleSync->criar(
                $mentor['email'],
                $this->dadosEventoGoogle($concurso, $horario['data_inicio'], $horario['data_fim'], (string) $horario['observacao']),
                'Mentorias ' . $concurso['nome']
            );

            if ($resultado !== null) {
                $this->mentorias->atualizarGoogle($id, $resultado);

                if ($horario['equipe_id'] !== null) {
                    $emails = $this->equipes->listarEmailsPorEquipes([(int) $horario['equipe_id']]);
                    $this->googleSync->sincronizarAttendees(
                        'mentoria', $id, $mentor['email'], $resultado['google_calendar_id'], $resultado['google_event_id'],
                        array_keys($emails), $emails
                    );
                }

                $_SESSION['flash'] = 'Integração com o Google Agenda concluída.';
            } else {
                flashErro('Ainda não foi possível conectar com o Google Agenda. Tente novamente em alguns instantes.');
            }
        } else {
            $resultado = $this->googleSync->reconciliar('mentoria', $horario, $mentor['email']);

            if ($resultado !== null) {
                $this->mentorias->atualizarGoogle($id, $resultado);
                $_SESSION['flash'] = 'Status atualizado.';
            } else {
                flashAlerta('Nenhuma novidade agora (ou aguarde um pouco antes de verificar de novo).');
            }
        }

        $this->redirecionar('mentoriaAdmin/index/' . $concursoId);
    }

    private function notificarEquipe($equipeId, $titulo, $mensagem)
    {
        foreach ($this->equipes->listarParticipantes($equipeId) as $participante) {
            foreach ($this->usuarioParticipante->usuariosDoParticipante($participante['id']) as $usuarioId) {
                $this->notificacoes->criar($usuarioId, 'mentoria', $titulo, $mensagem, ['url' => url('mentoria/index')]);
            }

            if (!empty($participante['email'])) {
                Mailer::enviar($participante['email'], $titulo, '<p>' . htmlspecialchars($mensagem, ENT_QUOTES, 'UTF-8') . '</p>');
            }
        }
    }
}
