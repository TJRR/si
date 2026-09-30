<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Repositories\BonusConfigRepository;
use App\Repositories\BonusCreditoRepository;
use App\Repositories\BonusRepository;
use App\Repositories\EventoCheckinResumoRepository;
use App\Repositories\PesquisaRespondenteRepository;

/**
 * Fase 57: apuracao dos bonus automaticos.
 *
 * Bonus e' entidade cadastravel: o Administrador cria quantos quiser, com o
 * nome que quiser. O que este servico faz e' apurar os TIPOS, que sao a
 * parte que mora no codigo, porque cada um e' uma consulta. Tipo novo, numa
 * fase futura, e' uma entrada em TIPOS mais um ramo em atingiu() e em
 * progressoAtual(), sem migration.
 *
 * NAO abre transacao e NAO bloqueia linha, ao contrario de ConexaoService e
 * DivulgacaoService, e a diferenca e' deliberada: o credito e' idempotente
 * pela chave unica (evento_inscricao_id, bonus_id) e a regra so' cresce.
 * Duas apuracoes simultaneas da mesma pessoa nao se atrapalham - uma grava,
 * a outra nao faz nada, sem erro e sem espera.
 */
class BonusApuracaoService
{
    /**
     * Os tipos que o sistema sabe apurar, com o rotulo da tela e o que o
     * cadastro pede de cada um. Fica aqui, e nao no controller, porque quem
     * define o que cada tipo significa e' quem o apura.
     */
    const TIPOS = [
        'atividades_distintas' => [
            'rotulo' => 'Atividades diferentes com presença',
            'pede_numero' => true,
            'pede_tipo_atividade' => false,
            'unidade' => 'atividades diferentes',
        ],
        'dias_distintos' => [
            'rotulo' => 'Dias diferentes com presença',
            'pede_numero' => true,
            'pede_tipo_atividade' => false,
            'unidade' => 'dias diferentes',
        ],
        'atividades_do_tipo' => [
            'rotulo' => 'Atividades de um tipo escolhido',
            'pede_numero' => true,
            'pede_tipo_atividade' => true,
            'unidade' => 'atividades do tipo escolhido',
        ],
        'responder_pesquisa' => [
            'rotulo' => 'Responder à pesquisa de satisfação',
            'pede_numero' => false,
            'pede_tipo_atividade' => false,
            'unidade' => '',
        ],
    ];

    private $config;
    private $bonus;
    private $creditos;
    private $presencas;
    private $respondentes;

    public function __construct()
    {
        $this->config = new BonusConfigRepository();
        $this->bonus = new BonusRepository();
        $this->creditos = new BonusCreditoRepository();
        $this->presencas = new EventoCheckinResumoRepository();
        $this->respondentes = new PesquisaRespondenteRepository();
    }

    public static function rotuloDoTipo($tipo)
    {
        return isset(self::TIPOS[$tipo]) ? self::TIPOS[$tipo]['rotulo'] : $tipo;
    }

    public static function unidadeDoTipo($tipo)
    {
        return isset(self::TIPOS[$tipo]) ? self::TIPOS[$tipo]['unidade'] : '';
    }

    /**
     * Apura os bonus de UMA pessoa e credita o que fechou agora. Devolve a
     * lista do que foi creditado nesta chamada, na forma
     * [['nome' => ..., 'pontos' => n], ...] - vazia no caso comum.
     *
     * Recebe a inscricao E o usuario: o credito e' chaveado pela inscricao,
     * mas o bonus de responder a pesquisa consulta a tabela de respondentes,
     * que desde o bloco E e' chaveada por usuario (pendencia 34).
     *
     * Custo: duas consultas (resumo de presencas e creditos ja' existentes),
     * mais uma terceira so' quando o evento tem bonus por tipo de atividade,
     * mais uma gravacao por bonus que fecha.
     */
    public function apurarInscricao(array $evento, $inscricaoId, $usuarioId)
    {
        $eventoId = (int) $evento['id'];

        if (!$this->config->estaAtivo($eventoId)) {
            return [];
        }

        $ativos = $this->bonus->listarAtivos($eventoId);

        if ($ativos === []) {
            return [];
        }

        $creditosAtuais = $this->creditos->resumoParticipante($inscricaoId);
        $resumo = $this->presencas->resumoDaInscricao($eventoId, $inscricaoId);
        $porTipo = $this->precisaDeTipos($ativos) ? $this->presencas->porTipoDaInscricao($eventoId, $inscricaoId) : [];
        $respondeu = null;

        $creditados = [];

        foreach ($ativos as $bonus) {
            $bonusId = (int) $bonus['id'];
            $credito = isset($creditosAtuais['por_bonus'][$bonusId]) ? $creditosAtuais['por_bonus'][$bonusId] : null;

            if ($bonus['tipo'] === 'responder_pesquisa') {
                if ($respondeu === null) {
                    $respondeu = $this->respondentes->jaRespondeu($eventoId, $usuarioId);
                }

                $cumpre = $respondeu;
            } else {
                $cumpre = $this->atingiu($bonus, $resumo, $porTipo);
            }

            // Ja' existe linha deste bonus: o unico movimento possivel e'
            // desfazer uma anulacao PELO SISTEMA quando a condicao voltou a
            // ser cumprida (a presenca removida por engano foi registrada de
            // novo). Anulacao feita por pessoa nunca volta por aqui, e
            // credito valido nao e' tocado.
            if ($credito !== null) {
                if ($cumpre && $credito['anulado_em'] !== null && $credito['anulado_por'] === null) {
                    $this->creditos->reverterAnulacaoAutomatica($credito['id']);
                }

                continue;
            }

            if (!$cumpre) {
                continue;
            }

            $creditado = $this->gravar($eventoId, $bonus, $inscricaoId, $resumo, $porTipo);

            if ($creditado !== null) {
                $creditados[] = $creditado;
            }
        }

        return $creditados;
    }

    /**
     * Fase 57 (bloco E, pendencia 33): chamado logo depois de o Administrador
     * marcar uma presenca como removida. Confere os creditos validos da
     * pessoa e anula os que perderam a base.
     *
     * A anulacao aqui e' PELO SISTEMA (anulado_por nulo), e por isso e'
     * reversivel sozinha: registrar a presenca de novo faz apurarInscricao()
     * devolver o credito, sem ninguem precisar lembrar de desfazer nada.
     *
     * Percorre o catalogo inteiro do evento, e nao so' os bonus ativos, para
     * alcancar tambem o credito de um bonus que foi desativado depois de
     * concedido.
     */
    public function reapurarAposRemocao(array $evento, $inscricaoId)
    {
        $eventoId = (int) $evento['id'];
        $creditosAtuais = $this->creditos->resumoParticipante($inscricaoId);

        if ($creditosAtuais['por_bonus'] === []) {
            return [];
        }

        $catalogo = [];

        foreach ($this->bonus->listarPorEvento($eventoId) as $bonus) {
            $catalogo[(int) $bonus['id']] = $bonus;
        }

        $resumo = $this->presencas->resumoDaInscricao($eventoId, $inscricaoId);
        $porTipo = $this->precisaDeTipos($catalogo) ? $this->presencas->porTipoDaInscricao($eventoId, $inscricaoId) : [];

        $anulados = [];

        foreach ($creditosAtuais['por_bonus'] as $bonusId => $credito) {
            if ($credito['anulado_em'] !== null || !isset($catalogo[$bonusId])) {
                continue;
            }

            $bonus = $catalogo[$bonusId];

            // O credito da pesquisa nao depende de presenca: remover uma
            // presenca nunca o alcanca.
            if ($bonus['tipo'] === 'responder_pesquisa' || $this->atingiu($bonus, $resumo, $porTipo)) {
                continue;
            }

            $motivo = 'A organização removeu uma presença sua, e este bônus deixou de ter a quantidade exigida.';

            if ($this->creditos->anularPeloSistema($credito['id'], $motivo)) {
                $anulados[] = [
                    'id' => $bonusId,
                    'nome' => $bonus['nome'],
                    'pontos' => (int) $credito['pontos'],
                    'motivo' => $motivo,
                ];
            }
        }

        return $anulados;
    }

    /**
     * Reconfere o evento inteiro e credita quem passou a cumprir. Roda ao
     * salvar, desativar ou reordenar um bonus e no botao "Reconferir agora".
     * Devolve quantos creditos foram criados.
     *
     * Duas consultas agregadas (tres com bonus por tipo de atividade) e uma
     * gravacao por credito novo. Quem nao tem nenhuma presenca nem aparece
     * no agrupamento, entao o laco percorre so' quem pode ganhar algo.
     */
    public function apurarEvento(array $evento)
    {
        $eventoId = (int) $evento['id'];

        if (!$this->config->estaAtivo($eventoId)) {
            return 0;
        }

        $ativos = $this->bonus->listarAtivos($eventoId);

        if ($ativos === []) {
            return 0;
        }

        $resumos = $this->presencas->resumoDoEvento($eventoId);
        $porTipoGeral = $this->precisaDeTipos($ativos) ? $this->presencas->porTipoDoEvento($eventoId) : [];
        $creditadosPorInscricao = $this->creditos->creditadosPorInscricaoNoEvento($eventoId);
        $bonusDePresenca = [];

        foreach ($ativos as $bonus) {
            if ($bonus['tipo'] !== 'responder_pesquisa') {
                $bonusDePresenca[] = $bonus;
            }
        }

        if ($bonusDePresenca === []) {
            return 0;
        }

        $total = 0;

        foreach ($resumos as $inscricaoId => $resumo) {
            $jaCreditados = isset($creditadosPorInscricao[$inscricaoId]) ? $creditadosPorInscricao[$inscricaoId] : [];
            $porTipo = isset($porTipoGeral[$inscricaoId]) ? $porTipoGeral[$inscricaoId] : [];

            foreach ($bonusDePresenca as $bonus) {
                $bonusId = (int) $bonus['id'];
                $credito = isset($jaCreditados[$bonusId]) ? $jaCreditados[$bonusId] : null;
                $cumpre = $this->atingiu($bonus, $resumo, $porTipo);

                // Mesma regra da apuracao individual: linha existente so' se
                // move para desfazer anulacao PELO SISTEMA, e a reconferencia
                // do Administrador tambem conserta esses casos.
                if ($credito !== null) {
                    if ($cumpre && $credito['anulado_em'] !== null && $credito['anulado_por'] === null) {
                        $this->creditos->reverterAnulacaoAutomatica($credito['id']);
                    }

                    continue;
                }

                if (!$cumpre) {
                    continue;
                }

                if ($this->gravar($eventoId, $bonus, $inscricaoId, $resumo, $porTipo) !== null) {
                    $total++;
                }
            }
        }

        if ($total > 0) {
            Auditoria::registrar('apurar_bonus', 'evento_bonus_creditos', null, null, [
                'evento_id' => $eventoId,
                'creditos_novos' => $total,
            ]);
        }

        return $total;
    }

    /**
     * Credita os bonus do tipo responder_pesquisa de quem acabou de
     * responder. Chamado por PesquisaService, depois do commit das
     * respostas. Devolve a lista do que foi creditado.
     */
    public function creditarPorPesquisa(array $evento, $inscricaoId)
    {
        $eventoId = (int) $evento['id'];

        // Quem responde sem ter inscricao no evento (facilitador, avaliador
        // avulso) responde e nao pontua: o credito vive em
        // evento_bonus_creditos.evento_inscricao_id, que exige inscricao.
        if ($inscricaoId === null || !$this->config->estaAtivo($eventoId)) {
            return [];
        }

        $jaCreditados = $this->creditos->bonusJaCreditados($inscricaoId);
        $creditados = [];

        foreach ($this->bonus->listarAtivos($eventoId) as $bonus) {
            if ($bonus['tipo'] !== 'responder_pesquisa' || in_array((int) $bonus['id'], $jaCreditados, true)) {
                continue;
            }

            $creditado = $this->gravar($eventoId, $bonus, $inscricaoId, ['atividades' => 0, 'dias' => 0], []);

            if ($creditado !== null) {
                $creditados[] = $creditado;
            }
        }

        return $creditados;
    }

    /**
     * Situacao de cada bonus ativo para o painel do participante: quanto a
     * pessoa ja' tem, quanto falta, e o credito quando ja' fechou. Leitura
     * pura, protegida contra falha de banco pelos repositorios.
     */
    public function progressoDe(array $evento, $inscricaoId)
    {
        $eventoId = (int) $evento['id'];
        $ativos = $this->bonus->listarAtivos($eventoId);

        if ($ativos === []) {
            return [];
        }

        $resumo = $this->presencas->resumoDaInscricao($eventoId, $inscricaoId);
        $porTipo = $this->precisaDeTipos($ativos) ? $this->presencas->porTipoDaInscricao($eventoId, $inscricaoId) : [];
        $creditos = $this->creditos->resumoParticipante($inscricaoId);

        $lista = [];

        foreach ($ativos as $bonus) {
            $id = (int) $bonus['id'];
            $credito = isset($creditos['por_bonus'][$id]) ? $creditos['por_bonus'][$id] : null;

            $lista[] = [
                'id' => $id,
                'nome' => $bonus['nome'],
                'descricao' => $bonus['descricao'],
                'tipo' => $bonus['tipo'],
                'unidade' => self::unidadeDoTipo($bonus['tipo']),
                'tipo_atividade_nome' => $bonus['tipo_atividade_nome'],
                'exigencia' => (int) $bonus['exigencia'],
                'pontos' => (int) $bonus['pontos'],
                'atual' => $this->progressoAtual($bonus, $resumo, $porTipo),
                'credito' => $credito,
            ];
        }

        return $lista;
    }

    /**
     * Grava o credito com os pontos e a exigencia do momento, congelados.
     * Devolve null quando a linha ja' existia (outra apuracao chegou antes).
     */
    private function gravar($eventoId, array $bonus, $inscricaoId, array $resumo, array $porTipo)
    {
        $exigenciaAtingida = $bonus['tipo'] === 'responder_pesquisa'
            ? 1
            : $this->progressoAtual($bonus, $resumo, $porTipo);

        $inseriu = $this->creditos->creditar(
            $eventoId,
            (int) $bonus['id'],
            $inscricaoId,
            (int) $bonus['pontos'],
            $exigenciaAtingida
        );

        if (!$inseriu) {
            return null;
        }

        return [
            'id' => (int) $bonus['id'],
            'nome' => $bonus['nome'],
            'pontos' => (int) $bonus['pontos'],
        ];
    }

    /**
     * Exigencia zero nunca fecha um bonus de presenca: seria creditar todo
     * mundo, inclusive quem nao apareceu.
     */
    private function atingiu(array $bonus, array $resumo, array $porTipo)
    {
        $exigencia = (int) $bonus['exigencia'];

        if ($exigencia < 1) {
            return false;
        }

        return $this->progressoAtual($bonus, $resumo, $porTipo) >= $exigencia;
    }

    private function progressoAtual(array $bonus, array $resumo, array $porTipo)
    {
        switch ($bonus['tipo']) {
            case 'atividades_distintas':
                return (int) $resumo['atividades'];

            case 'dias_distintos':
                return (int) $resumo['dias'];

            case 'atividades_do_tipo':
                $tipoId = $bonus['tipo_atividade_id'] !== null ? (int) $bonus['tipo_atividade_id'] : 0;

                return isset($porTipo[$tipoId]) ? (int) $porTipo[$tipoId] : 0;

            default:
                return 0;
        }
    }

    private function precisaDeTipos(array $ativos)
    {
        foreach ($ativos as $bonus) {
            if ($bonus['tipo'] === 'atividades_do_tipo') {
                return true;
            }
        }

        return false;
    }
}
