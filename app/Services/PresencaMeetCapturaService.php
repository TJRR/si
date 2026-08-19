<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EquipeRepository;
use App\Repositories\GooglePresencaRepository;
use App\Repositories\MentoriaRepository;
use App\Repositories\NotificacaoPainelRepository;
use App\Repositories\OficinaRepository;
use App\Repositories\PerfilRepository;

/**
 * Fase 32: trabalho de captura de presenca de UM horario - resolver quem era
 * convidado, ler a sala no Meet, casar por nome, gravar as sessoes e mexer no
 * estado. Fica num service (e nao solto no script do cron) pelo mesmo motivo
 * de GoogleCalendarSyncService: e' chamado de mais de um lugar (o cron e,
 * potencialmente, uma acao manual), e duplicar essa logica sairia caro.
 *
 * Casamento de identidade e' por NOME, nunca por e-mail: a Meet API nao
 * devolve e-mail de quem entrou (ver App\Services\GoogleMeetService). Quem nao
 * casa com nenhum convidado do horario fica com participante_id NULL - e' o
 * mecanismo natural de detectar "intruso", e tambem o que acontece quando
 * alguem legitimo entra com uma conta cujo nome de exibicao difere do nome
 * cadastrado. A tela deixa essa ressalva explicita pro admin.
 */
class PresencaMeetCapturaService
{
    const MAX_TENTATIVAS = 30;

    // O Google apaga conferenceRecord (e participants/sessions) 30 dias depois
    // do fim da conferencia - passado isso nao adianta gastar tentativa.
    const DIAS_RETENCAO_GOOGLE = 30;

    const RESULTADO_CAPTURADA = 'capturada';
    const RESULTADO_EM_CURSO = 'em_curso';
    const RESULTADO_PENDENTE = 'pendente';
    const RESULTADO_INDISPONIVEL = 'indisponivel';
    const RESULTADO_EXPIRADA = 'expirada';

    private $presenca;
    private $equipes;
    private $mentorias;
    private $oficinas;
    private $notificacoes;
    private $perfis;
    private $meet;

    public function __construct()
    {
        $this->presenca = new GooglePresencaRepository();
        $this->equipes = new EquipeRepository();
        $this->mentorias = new MentoriaRepository();
        $this->oficinas = new OficinaRepository();
        $this->notificacoes = new NotificacaoPainelRepository();
        $this->perfis = new PerfilRepository();
        $this->meet = new GoogleMeetPresencaService();
    }

    /**
     * Processa um horario e devolve uma das constantes RESULTADO_*. Nunca
     * lanca excecao por conta propria; quem chama (o cron) ainda assim
     * envolve em try/catch, porque uma falha inesperada num horario nao pode
     * derrubar a varredura dos demais.
     */
    public function processarHorario($tipo, array $horario)
    {
        $horarioId = (int) $horario['id'];

        // Estruturalmente irrecuperavel: o dado ja foi apagado do lado do
        // Google. Marcar direto evita queimar 30 tentativas (horas de ciclo)
        // em algo impossivel - cenario real quando o backfill retroativo
        // alcanca horarios antigos.
        if ($this->passouDaRetencao($horario['data_fim'])) {
            $this->marcarIndisponivel($tipo, $horarioId);

            return self::RESULTADO_EXPIRADA;
        }

        $captura = $this->meet->capturar($horario['organizador_email'], $horario['google_conference_id']);

        if ($captura === null) {
            return $this->registrarTentativaFalha($tipo, $horario);
        }

        // Reuniao ainda rolando (horario marcado pra 14h-15h que se estendeu
        // ate' 18h, por exemplo): os dados existem mas NAO sao definitivos.
        // Gravar agora congelaria presenca pela metade sem nenhum sinal
        // visivel na tela - a falha seria invisivel, ao contrario de
        // 'indisponivel'. Conta como tentativa e volta pra fila.
        if (!$captura['conferencia_encerrada']) {
            $this->registrarTentativaFalha($tipo, $horario);

            return self::RESULTADO_EM_CURSO;
        }

        $participantes = $this->casarComConvidados($tipo, $horario, $captura['participantes']);

        $this->presenca->salvarSessoes($tipo, $horarioId, $participantes);

        $this->repositorio($tipo)->atualizarPresenca($horarioId, [
            'presenca_status' => 'capturada',
            'presenca_tentativas' => (int) $horario['presenca_tentativas'] + 1,
            'presenca_ultima_tentativa_em' => date('Y-m-d H:i:s'),
            'presenca_capturada_em' => date('Y-m-d H:i:s'),
        ]);

        $this->notificarCaptura($tipo, $horario, count($participantes));

        return self::RESULTADO_CAPTURADA;
    }

    /**
     * Resolve os convidados do horario e casa cada pessoa que entrou na sala
     * com um participante cadastrado, por nome normalizado.
     *
     * Mentoria tem 1 equipe (exclusiva); Oficina tem N equipes na mesma sala,
     * e por isso o mapa guarda tambem a equipe de origem - sem isso a presenca
     * de uma oficina viraria uma lista solta, sem dizer quem e' de qual equipe.
     */
    private function casarComConvidados($tipo, array $horario, array $participantesMeet)
    {
        $convidadosPorNome = [];

        foreach ($this->listarConvidados($tipo, $horario) as $convidado) {
            $chave = normalizarNomeParaComparacao($convidado['nome']);

            if ($chave === '') {
                continue;
            }

            // Nome repetido entre convidados do mesmo horario e' ambiguo -
            // marcamos como inutilizavel em vez de casar com o primeiro que
            // aparecer, senao a presenca de um seria atribuida ao outro.
            $convidadosPorNome[$chave] = isset($convidadosPorNome[$chave])
                ? null
                : (int) $convidado['participante_id'];
        }

        $resultado = [];

        foreach ($participantesMeet as $participante) {
            $chave = normalizarNomeParaComparacao($participante['nome_bruto']);
            $participanteId = ($chave !== '' && !empty($convidadosPorNome[$chave]))
                ? $convidadosPorNome[$chave]
                : null;

            $resultado[] = [
                'meet_ref' => $participante['meet_ref'],
                'nome_bruto' => $participante['nome_bruto'],
                'tipo_origem' => $participante['tipo_origem'],
                'participante_id' => $participanteId,
                'sessoes' => $participante['sessoes'],
            ];
        }

        return $resultado;
    }

    /**
     * Integrantes convidados do horario. Mentoria: a unica equipe que reservou
     * (equipe_id pode estar NULL se ninguem reservou - nesse caso todo mundo
     * que entrar e' "nao identificado"). Oficina: todas as equipes inscritas.
     */
    public function listarConvidados($tipo, array $horario)
    {
        if ($tipo === 'mentoria') {
            if (empty($horario['equipe_id'])) {
                return [];
            }

            return $this->equipes->listarParticipantesPorEquipes([(int) $horario['equipe_id']]);
        }

        $equipeIds = array_unique(array_map(
            'intval',
            array_column($this->oficinas->listarInscritos((int) $horario['id']), 'equipe_id')
        ));

        return $this->equipes->listarParticipantesPorEquipes($equipeIds);
    }

    /**
     * Monta o relatorio da tela: uma linha por convidado cruzando as tres
     * fontes (convidado -> RSVP -> presenca), mais a lista separada de quem
     * entrou sem casar com nenhum convidado.
     *
     * As tres fontes vivem em tabelas diferentes, sem uma view SQL unica que
     * as junte, entao o cruzamento e' feito em memoria por participante_id.
     *
     * "Nao identificado" NAO e' sinonimo de intruso: tambem cai aqui um
     * integrante legitimo que entrou com uma conta cujo nome de exibicao
     * difere do nome cadastrado. A view precisa deixar isso explicito.
     */
    public function montarRelatorio($tipo, array $horario, array $rsvp)
    {
        $horarioId = (int) $horario['id'];

        $presencaPorParticipante = [];
        $naoIdentificados = [];

        foreach ($this->presenca->listarAgregadoPorHorario($tipo, $horarioId) as $linha) {
            if ($linha['participante_id'] === null) {
                $naoIdentificados[] = $linha;
                continue;
            }

            $presencaPorParticipante[(int) $linha['participante_id']] = $linha;
        }

        // O RSVP e' chaveado por e-mail no Google, e o participante_id la' e'
        // so' rotulo (pode estar NULL se o e-mail nao casou com ninguem no
        // momento do convite) - por isso indexamos pelos dois e consultamos
        // na ordem participante_id -> e-mail.
        $rsvpPorParticipante = [];
        $rsvpPorEmail = [];

        foreach ($rsvp as $convite) {
            if (!empty($convite['participante_id'])) {
                $rsvpPorParticipante[(int) $convite['participante_id']] = $convite['status'];
            }

            $rsvpPorEmail[mb_strtolower(trim($convite['email']))] = $convite['status'];
        }

        $convidados = [];

        foreach ($this->listarConvidados($tipo, $horario) as $convidado) {
            $participanteId = (int) $convidado['participante_id'];
            $email = mb_strtolower(trim((string) $convidado['email']));

            if (isset($rsvpPorParticipante[$participanteId])) {
                $statusRsvp = $rsvpPorParticipante[$participanteId];
            } elseif ($email !== '' && isset($rsvpPorEmail[$email])) {
                $statusRsvp = $rsvpPorEmail[$email];
            } else {
                $statusRsvp = null;
            }

            $convidados[] = $convidado + [
                'rsvp' => $statusRsvp,
                'presenca' => isset($presencaPorParticipante[$participanteId]) ? $presencaPorParticipante[$participanteId] : null,
            ];
        }

        return [
            'convidados' => $convidados,
            'nao_identificados' => $naoIdentificados,
        ];
    }

    /**
     * Incrementa a tentativa; ao estourar o limite, encerra o ciclo como
     * indisponivel E notifica. A notificacao de falha existe porque uma causa
     * sistemica (escopo nao autorizado na Delegacao em Todo o Dominio, edicao
     * do Workspace sem rastreamento de presenca) faria TODOS os horarios
     * falharem em silencio por horas, sem ninguem perceber ate' abrir uma tela
     * manualmente.
     */
    private function registrarTentativaFalha($tipo, array $horario)
    {
        $tentativas = (int) $horario['presenca_tentativas'] + 1;
        $esgotou = $tentativas >= self::MAX_TENTATIVAS;

        $this->repositorio($tipo)->atualizarPresenca((int) $horario['id'], [
            'presenca_status' => $esgotou ? 'indisponivel' : 'pendente',
            'presenca_tentativas' => $tentativas,
            'presenca_ultima_tentativa_em' => date('Y-m-d H:i:s'),
        ]);

        if ($esgotou) {
            $this->notificarFalha($tipo, $horario);

            return self::RESULTADO_INDISPONIVEL;
        }

        return self::RESULTADO_PENDENTE;
    }

    private function marcarIndisponivel($tipo, $horarioId)
    {
        $this->repositorio($tipo)->atualizarPresenca($horarioId, [
            'presenca_status' => 'indisponivel',
            'presenca_ultima_tentativa_em' => date('Y-m-d H:i:s'),
        ]);
    }

    private function passouDaRetencao($dataFim)
    {
        $limite = strtotime($dataFim . ' +' . self::DIAS_RETENCAO_GOOGLE . ' days');

        return $limite !== false && time() > $limite;
    }

    private function notificarCaptura($tipo, array $horario, $totalPessoas)
    {
        $this->notificarAdministradores(
            $horario,
            'presenca_capturada',
            'Relatório de presença disponível',
            'A presença do horário de ' . $this->rotulo($tipo) . ' de '
                . formatarDataHora($horario['data_inicio']) . ' foi capturada ('
                . $totalPessoas . ' ' . ($totalPessoas === 1 ? 'pessoa entrou' : 'pessoas entraram') . ' na sala).',
            $tipo
        );
    }

    private function notificarFalha($tipo, array $horario)
    {
        $this->notificarAdministradores(
            $horario,
            'presenca_indisponivel',
            'Não foi possível obter a presença',
            'O horário de ' . $this->rotulo($tipo) . ' de ' . formatarDataHora($horario['data_inicio'])
                . ' esgotou as tentativas de captura de presença. Se vários horários apresentarem'
                . ' este aviso ao mesmo tempo, a causa provavelmente é única (autorização do escopo'
                . ' do Google Meet) — verifique isso antes de tratar caso a caso.',
            $tipo
        );
    }

    private function notificarAdministradores(array $horario, $tipoNotificacao, $titulo, $mensagem, $tipo)
    {
        $rota = ($tipo === 'mentoria' ? 'mentoriaAdmin' : 'oficinaAdmin') . '/presenca/' . (int) $horario['id'];

        foreach ($this->perfis->listarUsuariosPorPerfilConcurso('administrador', (int) $horario['concurso_id']) as $admin) {
            $this->notificacoes->criar(
                (int) $admin['id'],
                $tipoNotificacao,
                $titulo,
                $mensagem,
                ['url' => url($rota)]
            );
        }
    }

    private function rotulo($tipo)
    {
        return $tipo === 'mentoria' ? 'mentoria' : 'oficina';
    }

    private function repositorio($tipo)
    {
        return $tipo === 'mentoria' ? $this->mentorias : $this->oficinas;
    }
}
