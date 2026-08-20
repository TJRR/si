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
use App\Services\EventoEtapaService;
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
    private $eventoEtapa;

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
        $this->eventoEtapa = new EventoEtapaService();
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
        $entrada = $this->entradaDoPost();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $mentorUsuario = null;

            foreach ($mentores as $mentor) {
                if ((int) $mentor['id'] === $entrada['mentor_usuario_id']) {
                    $mentorUsuario = $mentor;
                    break;
                }
            }

            $erro = $this->validarEntrada($entrada, $concursoId);

            if ($erro === null && $mentorUsuario === null) {
                $erro = 'Selecione um mentor válido (administrador ou suporte).';
            } elseif ($erro === null && $entrada['integrar_google'] && !organizadorElegivelGoogle($mentorUsuario['email'])) {
                $erro = 'A integração com o Google Agenda só está disponível para mentores com e-mail institucional @tjrr.jus.br.';
            }

            if ($erro === null) {
                $id = $this->mentorias->criar(
                    $concursoId,
                    $entrada['mentor_usuario_id'],
                    $entrada['data_inicio'],
                    $entrada['data_fim'],
                    $entrada['link_meet'] !== '' ? $entrada['link_meet'] : null,
                    $entrada['observacao'] !== '' ? $entrada['observacao'] : null,
                    $entrada['integrar_google'],
                    $entrada['etapa_id']
                );

                if ($entrada['integrar_google']) {
                    $resultado = $this->googleSync->criar(
                        $mentorUsuario['email'],
                        $this->dadosEventoGoogle($concurso, $entrada['data_inicio'], $entrada['data_fim'], $entrada['observacao']),
                        'Mentorias ' . $concurso['nome']
                    );

                    if ($resultado !== null) {
                        $this->mentorias->atualizarGoogle($id, $resultado);
                    } else {
                        flashAlerta('Horário criado, mas não foi possível conectar com o Google Agenda agora. Você pode tentar novamente na listagem.');
                    }
                }

                $this->avisarSeEtapaNaoRestringe($entrada['etapa_id']);
                $this->redirecionar('mentoriaAdmin/index/' . $concursoId);
                return;
            }
        }

        $this->renderizar('admin/mentorias/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'mentores' => $mentores,
            'horario' => null,
            'entrada' => $entrada,
            'trilhas' => $this->trilhas->listarPorConcurso($concursoId),
            'etapasPorTrilha' => $this->etapasParaVinculoPorTrilha($concursoId),
        ], 'Novo horário de mentoria', ['tipo' => 'mentorias', 'id' => (int) $concursoId]);
    }

    /**
     * Fase 34: edicao de horario. Trava por data (exigirAindaNaoIniciado) e
     * mesma checagem de dono de remover(). Mentor e integracao com o Google
     * NAO sao editaveis de proposito: trocar o mentor mudaria a agenda dona
     * do evento, e alternar a integracao exigiria criar/cancelar evento no
     * meio do fluxo. Para mudar qualquer um dos dois, remova e recrie o
     * horario enquanto ele ainda nao comecou.
     */
    public function editar($id)
    {
        $horario = $this->mentorias->buscarPorId($id);

        if ($horario === null) {
            http_response_code(404);
            exit('Horário não encontrado.');
        }

        $concurso = $this->concursos->buscarPorId($horario['concurso_id']);
        RoleMiddleware::exigir(['administrador', 'suporte'], $horario['concurso_id']);
        $this->exigirDonoOuAdministrador($horario);
        $this->exigirAindaNaoIniciado($horario);

        $erro = null;
        $entrada = $_SERVER['REQUEST_METHOD'] === 'POST'
            ? $this->entradaDoPost()
            : [
                'data_inicio' => substr($horario['data_inicio'], 0, 16),
                'data_fim' => substr($horario['data_fim'], 0, 16),
                'link_meet' => (string) $horario['link_meet'],
                'observacao' => (string) $horario['observacao'],
                'etapa_id' => $horario['etapa_id'] !== null ? (int) $horario['etapa_id'] : null,
                'mentor_usuario_id' => (int) $horario['mentor_usuario_id'],
                'integrar_google' => !empty($horario['integracao_google']),
            ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Mentor e integracao nao sao editaveis: valem sempre os do banco.
            $entrada['mentor_usuario_id'] = (int) $horario['mentor_usuario_id'];
            $entrada['integrar_google'] = !empty($horario['integracao_google']);

            if (!empty($horario['integracao_google'])) {
                $entrada['link_meet'] = (string) $horario['link_meet'];
            }

            $erro = $this->validarEntrada($entrada, (int) $horario['concurso_id']);

            if ($erro === null) {
                $this->mentorias->atualizar(
                    $id,
                    $entrada['data_inicio'],
                    $entrada['data_fim'],
                    $entrada['link_meet'] !== '' ? $entrada['link_meet'] : null,
                    $entrada['observacao'] !== '' ? $entrada['observacao'] : null,
                    $entrada['etapa_id']
                );

                $this->aposEditar($concurso, $horario, $entrada);
                $this->avisarSeEtapaNaoRestringe($entrada['etapa_id']);
                $this->redirecionar('mentoriaAdmin/index/' . (int) $horario['concurso_id']);
                return;
            }
        }

        $this->renderizar('admin/mentorias/form', [
            'erro' => $erro,
            'concurso' => $concurso,
            'mentores' => $this->mentoresDisponiveis((int) $horario['concurso_id']),
            'horario' => $horario,
            'entrada' => $entrada,
            'trilhas' => $this->trilhas->listarPorConcurso($horario['concurso_id']),
            'etapasPorTrilha' => $this->etapasParaVinculoPorTrilha((int) $horario['concurso_id']),
        ], 'Editar horário de mentoria', ['tipo' => 'mentorias', 'id' => (int) $horario['concurso_id']]);
    }

    /**
     * Fase 34: sincroniza o Google e avisa a equipe que ja reservou. Falha
     * do Google NUNCA desfaz a edicao local (decisao da fase) - vira aviso,
     * e "Verificar novamente" acerta depois.
     */
    private function aposEditar(array $concurso, array $antes, array $entrada)
    {
        if (!empty($antes['integracao_google']) && !empty($antes['google_event_id'])) {
            $mentor = $this->usuarios->buscarPorId($antes['mentor_usuario_id']);

            if ($mentor !== null) {
                $resultado = $this->googleSync->atualizar(
                    $mentor['email'],
                    $antes['google_calendar_id'],
                    $antes['google_event_id'],
                    $this->dadosEventoGoogle($concurso, $entrada['data_inicio'], $entrada['data_fim'], $entrada['observacao'])
                );

                if ($resultado !== null) {
                    $this->mentorias->atualizarGoogle($antes['id'], $resultado);
                } else {
                    flashAlerta('Alterações salvas, mas não foi possível atualizar o evento no Google Agenda agora. Use "Verificar novamente" na listagem para sincronizar.');
                }
            }
        }

        $mudouHorario = substr($antes['data_inicio'], 0, 16) !== $entrada['data_inicio']
            || substr($antes['data_fim'], 0, 16) !== $entrada['data_fim'];

        if (!$mudouHorario || $antes['equipe_id'] === null) {
            flashSucesso('Alterações salvas.');
            return;
        }

        $this->notificarEquipe(
            (int) $antes['equipe_id'],
            'Horário de mentoria alterado',
            'O horário de mentoria que sua equipe reservou mudou: agora é em ' . formatarDataHora($entrada['data_inicio']) . ' ' . sufixoFusoHorario() . '.'
        );

        flashSucesso('Alterações salvas e equipe avisada.');
    }

    private function entradaDoPost()
    {
        $etapaId = isset($_POST['etapa_id']) && $_POST['etapa_id'] !== '' ? (int) $_POST['etapa_id'] : null;

        return [
            'data_inicio' => trim(isset($_POST['data_inicio']) ? $_POST['data_inicio'] : ''),
            'data_fim' => trim(isset($_POST['data_fim']) ? $_POST['data_fim'] : ''),
            'link_meet' => trim(isset($_POST['link_meet']) ? $_POST['link_meet'] : ''),
            'observacao' => trim(isset($_POST['observacao']) ? $_POST['observacao'] : ''),
            'etapa_id' => $etapaId,
            'mentor_usuario_id' => (int) (isset($_POST['mentor_usuario_id']) ? $_POST['mentor_usuario_id'] : 0),
            'integrar_google' => !empty($_POST['integracao_google']),
        ];
    }

    private function validarEntrada(array $entrada, $concursoId)
    {
        if ($entrada['data_inicio'] === '' || $entrada['data_fim'] === '') {
            return 'Informe o início e o fim do horário.';
        }

        if (strtotime($entrada['data_fim']) <= strtotime($entrada['data_inicio'])) {
            return 'O fim do horário deve ser depois do início.';
        }

        if ($entrada['integrar_google'] && $entrada['link_meet'] !== '') {
            return 'Com a integração com o Google Agenda ativa, o link do Meet é gerado automaticamente — não informe um link manual.';
        }

        if (!$entrada['integrar_google'] && $entrada['link_meet'] !== '' && !linkHttpValido($entrada['link_meet'])) {
            return 'O link do Google Meet deve começar com http:// ou https://.';
        }

        return $this->eventoEtapa->erroDeVinculo($entrada['etapa_id'], $concursoId);
    }

    private function etapasParaVinculoPorTrilha($concursoId)
    {
        $mapa = [];

        foreach ($this->trilhas->listarPorConcurso($concursoId) as $trilha) {
            $mapa[(int) $trilha['id']] = $this->eventoEtapa->etapasParaVinculo((int) $trilha['id']);
        }

        return $mapa;
    }

    /**
     * Fase 34: reforca na gravacao o aviso que a tela ja da' - o admin pode
     * salvar sem nunca ter tocado no select (edicao), e a escolha e' valida
     * mas nao restringe ninguem.
     */
    private function avisarSeEtapaNaoRestringe($etapaId)
    {
        // Um flash sobrescreve o outro. Se ja ha' aviso na fila (falha de
        // sincronizacao com o Google, por exemplo), aquele e' mais grave e
        // fica - este aqui e' so' informativo.
        if ($etapaId === null || !empty($_SESSION['flash'])) {
            return;
        }

        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa !== null && !$this->eventoEtapa->restringe($etapa)) {
            flashAlerta('Salvo, mas a etapa escolhida não restringe ninguém (é a primeira da trilha, ou a etapa anterior não é avaliada por avaliadores) — na prática o compromisso está aberto a todos.');
        }
    }

    private function exigirDonoOuAdministrador(array $horario)
    {
        $souDono = (int) $horario['mentor_usuario_id'] === (int) Auth::usuarioId();

        if (!$souDono && !Auth::temPerfil('administrador', $horario['concurso_id'])) {
            http_response_code(403);
            exit('Acesso negado: este horário pertence a outro mentor.');
        }
    }

    /**
     * Fase 34: depois de comecado, o horario nao pode mais ser alterado nem
     * removido - ver comentario equivalente em OficinaAdminController.
     */
    private function exigirAindaNaoIniciado(array $horario)
    {
        if (strtotime($horario['data_inicio']) <= time()) {
            http_response_code(403);
            exit('Este horário já começou e não pode mais ser alterado ou removido.');
        }
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

        // Fase 29 (ajuste pos-push): Auth::possuiPerfil('administrador') era
        // global - exigirDonoOuAdministrador() usa temPerfil() com o concurso
        // do horario, mesmo ajuste de OficinaAdminController::remover().
        $this->exigirDonoOuAdministrador($horario);

        // Fase 34: horario ja iniciado nao pode mais ser removido - mesma
        // trava da edicao.
        $this->exigirAindaNaoIniciado($horario);

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
