<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Repositories\BonusConfigRepository;
use App\Repositories\BonusCreditoRepository;
use App\Repositories\BonusFatosRepository;
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
 * Fase 58: quatro tipos novos, as "acoes do participante" da dinamica de
 * pontos v2 (criar conta, perfil, contato e minicurriculo, credenciamento
 * no local, resumo expandido submetido). Eles nao dependem de presenca, e
 * sim de fatos lidos por BonusFatosRepository. Nenhum deles e' anulado pelo
 * sistema, exceto o de trabalho submetido, quando a desclassificacao tira a
 * base (reapurarAutoresDoTrabalho()). O de perfil, uma vez concedido, fica
 * mesmo que a pessoa apague a foto: o documento o define como "unico".
 *
 * Com a gincana encerrada (GamificacaoService::encerrada()), nenhum metodo
 * daqui credita, anula ou reverte: e' o congelamento da classificacao.
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
     *
     * acao_participante: o tipo depende de um fato da pessoa (Fase 58), e
     * nao de presenca nem da pesquisa.
     */
    const TIPOS = [
        'atividades_distintas' => [
            'rotulo' => 'Atividades diferentes com presença',
            'pede_numero' => true,
            'pede_tipo_atividade' => false,
            'pede_campos_perfil' => false,
            'acao_participante' => false,
            'unidade' => 'atividades diferentes',
        ],
        'dias_distintos' => [
            'rotulo' => 'Dias diferentes com presença',
            'pede_numero' => true,
            'pede_tipo_atividade' => false,
            'pede_campos_perfil' => false,
            'acao_participante' => false,
            'unidade' => 'dias diferentes',
        ],
        'atividades_do_tipo' => [
            'rotulo' => 'Atividades de um tipo escolhido',
            'pede_numero' => true,
            'pede_tipo_atividade' => true,
            'pede_campos_perfil' => false,
            'acao_participante' => false,
            'unidade' => 'atividades do tipo escolhido',
        ],
        'responder_pesquisa' => [
            'rotulo' => 'Responder à pesquisa de satisfação',
            'pede_numero' => false,
            'pede_tipo_atividade' => false,
            'pede_campos_perfil' => false,
            'acao_participante' => false,
            'unidade' => '',
        ],
        'inscricao_evento' => [
            'rotulo' => 'Ter inscrição no evento (criar conta)',
            'pede_numero' => false,
            'pede_tipo_atividade' => false,
            'pede_campos_perfil' => false,
            'acao_participante' => true,
            'unidade' => '',
        ],
        'perfil_campos' => [
            'rotulo' => 'Preencher campos do perfil',
            'pede_numero' => false,
            'pede_tipo_atividade' => false,
            'pede_campos_perfil' => true,
            'acao_participante' => true,
            'unidade' => 'campos do perfil',
        ],
        'credenciamento_local' => [
            'rotulo' => 'Confirmar o credenciamento no local',
            'pede_numero' => false,
            'pede_tipo_atividade' => false,
            'pede_campos_perfil' => false,
            'acao_participante' => true,
            'unidade' => '',
        ],
        'trabalho_submetido' => [
            'rotulo' => 'Ser autor de trabalho submetido',
            'pede_numero' => false,
            'pede_tipo_atividade' => false,
            'pede_campos_perfil' => false,
            'acao_participante' => true,
            'unidade' => '',
        ],
    ];

    /**
     * Fase 58: rotulos dos campos que o tipo perfil_campos confere, na
     * ordem de BonusFatosRepository::CAMPOS_PERFIL.
     */
    const CAMPOS_PERFIL_ROTULOS = [
        'foto' => 'Foto',
        'cargo' => 'Cargo',
        'orgao_origem' => 'Órgão de origem',
        'categoria_profissional' => 'Categoria profissional',
        'minicurriculo' => 'Minicurrículo',
        'telefone' => 'Telefone',
        'redes' => 'Ao menos uma rede social',
    ];

    private $config;
    private $bonus;
    private $creditos;
    private $presencas;
    private $respondentes;
    private $fatos;

    public function __construct()
    {
        $this->config = new BonusConfigRepository();
        $this->bonus = new BonusRepository();
        $this->creditos = new BonusCreditoRepository();
        $this->presencas = new EventoCheckinResumoRepository();
        $this->respondentes = new PesquisaRespondenteRepository();
        $this->fatos = new BonusFatosRepository();
    }

    public static function rotuloDoTipo($tipo)
    {
        return isset(self::TIPOS[$tipo]) ? self::TIPOS[$tipo]['rotulo'] : $tipo;
    }

    public static function unidadeDoTipo($tipo)
    {
        return isset(self::TIPOS[$tipo]) ? self::TIPOS[$tipo]['unidade'] : '';
    }

    public static function ehAcaoDoParticipante($tipo)
    {
        return isset(self::TIPOS[$tipo]) && self::TIPOS[$tipo]['acao_participante'];
    }

    /**
     * Fase 58: os campos exigidos por um bonus do tipo perfil_campos, na
     * ordem fixa de BonusFatosRepository::CAMPOS_PERFIL.
     */
    public static function camposDoBonus(array $bonus)
    {
        if (empty($bonus['campos_perfil'])) {
            return [];
        }

        $marcados = explode(',', (string) $bonus['campos_perfil']);

        return array_values(array_intersect(BonusFatosRepository::CAMPOS_PERFIL, $marcados));
    }

    /**
     * Apura os bonus de UMA pessoa e credita o que fechou agora. Devolve a
     * lista do que foi creditado nesta chamada, na forma
     * [['nome' => ..., 'pontos' => n], ...] - vazia no caso comum.
     *
     * Recebe a inscricao E o usuario: o credito e' chaveado pela inscricao,
     * mas o bonus de responder a pesquisa consulta a tabela de respondentes,
     * que desde o bloco E e' chaveada por usuario (pendencia 34), e os tipos
     * de acao do participante leem perfil e autoria pelo usuario.
     *
     * Custo: duas consultas (resumo de presencas e creditos ja' existentes),
     * mais uma terceira so' quando o evento tem bonus por tipo de atividade,
     * mais uma por fato de acao do participante que o evento de fato usa,
     * mais uma gravacao por bonus que fecha.
     */
    public function apurarInscricao(array $evento, $inscricaoId, $usuarioId)
    {
        $eventoId = (int) $evento['id'];

        if (GamificacaoService::encerrada($eventoId) || !$this->config->estaAtivo($eventoId)) {
            return [];
        }

        $ativos = $this->bonus->listarAtivos($eventoId);

        if ($ativos === []) {
            return [];
        }

        $creditosAtuais = $this->creditos->resumoParticipante($inscricaoId);
        $resumo = $this->presencas->resumoDaInscricao($eventoId, $inscricaoId);
        $porTipo = $this->precisaDeTipos($ativos) ? $this->presencas->porTipoDaInscricao($eventoId, $inscricaoId) : [];
        $fatos = $this->fatosDaPessoa($ativos, $eventoId, $inscricaoId, $usuarioId);
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
            } elseif (self::ehAcaoDoParticipante($bonus['tipo'])) {
                $cumpre = $this->cumpreAcao($bonus, $fatos);
            } else {
                $cumpre = $this->atingiu($bonus, $resumo, $porTipo);
            }

            // Ja' existe linha deste bonus: o unico movimento possivel e'
            // desfazer uma anulacao PELO SISTEMA quando a condicao voltou a
            // ser cumprida (a presenca removida por engano foi registrada de
            // novo, ou outro trabalho foi submetido). Anulacao feita por
            // pessoa nunca volta por aqui, e credito valido nao e' tocado.
            if ($credito !== null) {
                if ($cumpre && $credito['anulado_em'] !== null && $credito['anulado_por'] === null) {
                    $this->creditos->reverterAnulacaoAutomatica($credito['id']);
                }

                continue;
            }

            if (!$cumpre) {
                continue;
            }

            $creditado = $this->gravar($eventoId, $bonus, $inscricaoId, $resumo, $porTipo, $fatos);

            if ($creditado !== null) {
                $creditados[] = $creditado;
            }
        }

        return $creditados;
    }

    /**
     * Fase 58: apuracao na abertura do painel, que e' o que alcanca o perfil
     * completado pelo "Meu Perfil" do Concurso (tela que esta fase nao toca).
     *
     * So' roda quando existe bonus ATIVO de acao do participante que a
     * pessoa ainda nao tem - os creditos dela ja' sao lidos para a propria
     * tela, entao a conferencia nao custa consulta a mais. Assim a tela mais
     * aberta do aplicativo so' grava quando ha' o que gravar. Quem chama
     * confere antes o modo "visualizar como" (nunca grava em nome de outra
     * pessoa).
     */
    public function apurarAcoesPendentes(array $evento, $inscricaoId, $usuarioId, array $progresso)
    {
        foreach ($progresso as $item) {
            if (self::ehAcaoDoParticipante($item['tipo']) && $item['credito'] === null) {
                return $this->apurarInscricao($evento, $inscricaoId, $usuarioId);
            }
        }

        return [];
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

        if (GamificacaoService::encerrada($eventoId)) {
            return [];
        }

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

            // O credito da pesquisa e os das acoes do participante nao
            // dependem de presenca: remover uma presenca nunca os alcanca.
            if ($bonus['tipo'] === 'responder_pesquisa' || self::ehAcaoDoParticipante($bonus['tipo'])
                || $this->atingiu($bonus, $resumo, $porTipo)) {
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
     * Fase 58: chamado depois da desclassificacao de um trabalho. Para cada
     * autor com inscricao no evento, anula PELO SISTEMA o credito de
     * trabalho_submetido de quem ficou sem nenhum trabalho valido, e apura
     * de novo quem continua cumprindo (o credito anulado volta sozinho se a
     * pessoa tiver outro trabalho valido).
     *
     * Devolve a lista de anulacoes, com o usuario de cada uma, para o aviso
     * no sino.
     */
    /**
     * Fase 58 (rodada de 01/10/2026): chamado depois que o Administrador
     * remove o credenciamento no local de alguem pela tela de Gamificacao.
     *
     * O credito do tipo credenciamento_local nao passa por
     * reapurarAposRemocao(), que trata so' dos bonus de presenca, nem por
     * apurarInscricao(), que nunca anula credito existente. Sem este metodo,
     * a pessoa perdia o registro do credenciamento e continuava com os
     * pontos do bonus.
     *
     * Anulacao PELO SISTEMA (anulado_por nulo), como a de presenca: se a
     * pessoa se credenciar de novo dentro da janela, a apuracao seguinte
     * desfaz a anulacao sozinha. Devolve o que foi anulado, para o aviso.
     */
    public function reapurarAposRemocaoDeCredenciamento(array $evento, $inscricaoId)
    {
        $eventoId = (int) $evento['id'];

        if (GamificacaoService::encerrada($eventoId)) {
            return [];
        }

        $bonusDeCredenciamento = [];

        foreach ($this->bonus->listarPorEvento($eventoId) as $bonus) {
            if ($bonus['tipo'] === 'credenciamento_local') {
                $bonusDeCredenciamento[(int) $bonus['id']] = $bonus;
            }
        }

        if ($bonusDeCredenciamento === []) {
            return [];
        }

        $creditosAtuais = $this->creditos->resumoParticipante($inscricaoId);
        $motivo = 'A organização removeu o seu credenciamento no local, e este bônus deixou de ser devido.';
        $anulados = [];

        foreach ($bonusDeCredenciamento as $bonusId => $bonus) {
            if (!isset($creditosAtuais['por_bonus'][$bonusId])) {
                continue;
            }

            $credito = $creditosAtuais['por_bonus'][$bonusId];

            if ($credito['anulado_em'] === null && $this->creditos->anularPeloSistema($credito['id'], $motivo)) {
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

    public function reapurarAutoresDoTrabalho(array $evento, $trabalhoId)
    {
        $eventoId = (int) $evento['id'];

        if (GamificacaoService::encerrada($eventoId)) {
            return [];
        }

        $bonusDeTrabalho = [];

        foreach ($this->bonus->listarPorEvento($eventoId) as $bonus) {
            if ($bonus['tipo'] === 'trabalho_submetido') {
                $bonusDeTrabalho[(int) $bonus['id']] = $bonus;
            }
        }

        if ($bonusDeTrabalho === []) {
            return [];
        }

        $anulados = [];
        $motivo = 'O trabalho que dava direito a este bônus foi desclassificado.';

        foreach ($this->fatos->inscricoesDosAutoresDoTrabalho($trabalhoId) as $autor) {
            $inscricaoId = (int) $autor['inscricao_id'];
            $usuarioId = (int) $autor['usuario_id'];

            if ($this->fatos->ehAutorDeTrabalhoValido($eventoId, $usuarioId)) {
                $this->apurarInscricao($evento, $inscricaoId, $usuarioId);
                continue;
            }

            $creditosAtuais = $this->creditos->resumoParticipante($inscricaoId);

            foreach ($bonusDeTrabalho as $bonusId => $bonus) {
                if (!isset($creditosAtuais['por_bonus'][$bonusId])) {
                    continue;
                }

                $credito = $creditosAtuais['por_bonus'][$bonusId];

                if ($credito['anulado_em'] === null && $this->creditos->anularPeloSistema($credito['id'], $motivo)) {
                    $anulados[] = [
                        'usuario_id' => $usuarioId,
                        'nome' => $bonus['nome'],
                        'pontos' => (int) $credito['pontos'],
                        'motivo' => $motivo,
                    ];
                }
            }
        }

        return $anulados;
    }

    /**
     * Reconfere o evento inteiro e credita quem passou a cumprir. Roda ao
     * salvar um bonus, ao salvar as configuracoes, no botao "Reconferir
     * agora" de Bonus e no de Gamificacao. Devolve quantos creditos foram
     * criados.
     *
     * Presenca: duas consultas agregadas (tres com bonus por tipo de
     * atividade); quem nao tem nenhuma presenca nem aparece no agrupamento.
     * Acoes do participante (Fase 58): um laco proprio sobre TODAS as
     * inscricoes do evento, com os fatos lidos em lote, uma consulta por
     * fato que o evento usa.
     */
    public function apurarEvento(array $evento)
    {
        $eventoId = (int) $evento['id'];

        if (GamificacaoService::encerrada($eventoId) || !$this->config->estaAtivo($eventoId)) {
            return 0;
        }

        $ativos = $this->bonus->listarAtivos($eventoId);

        if ($ativos === []) {
            return 0;
        }

        $bonusDePresenca = [];
        $bonusDeAcao = [];

        foreach ($ativos as $bonus) {
            if (self::ehAcaoDoParticipante($bonus['tipo'])) {
                $bonusDeAcao[] = $bonus;
            } elseif ($bonus['tipo'] !== 'responder_pesquisa') {
                $bonusDePresenca[] = $bonus;
            }
        }

        if ($bonusDePresenca === [] && $bonusDeAcao === []) {
            return 0;
        }

        $creditadosPorInscricao = $this->creditos->creditadosPorInscricaoNoEvento($eventoId);
        $total = 0;

        if ($bonusDePresenca !== []) {
            $resumos = $this->presencas->resumoDoEvento($eventoId);
            $porTipoGeral = $this->precisaDeTipos($bonusDePresenca) ? $this->presencas->porTipoDoEvento($eventoId) : [];

            foreach ($resumos as $inscricaoId => $resumo) {
                $jaCreditados = isset($creditadosPorInscricao[$inscricaoId]) ? $creditadosPorInscricao[$inscricaoId] : [];
                $porTipo = isset($porTipoGeral[$inscricaoId]) ? $porTipoGeral[$inscricaoId] : [];

                foreach ($bonusDePresenca as $bonus) {
                    $cumpre = $this->atingiu($bonus, $resumo, $porTipo);

                    if ($this->creditarOuReverter($eventoId, $bonus, $inscricaoId, $jaCreditados, $cumpre, $resumo, $porTipo, [])) {
                        $total++;
                    }
                }
            }
        }

        if ($bonusDeAcao !== []) {
            $pessoas = $this->fatos->inscricoesComPerfilDoEvento($eventoId);
            $credenciados = $this->usaTipo($bonusDeAcao, 'credenciamento_local') ? $this->fatos->credenciadosDoEvento($eventoId) : [];
            $autores = $this->usaTipo($bonusDeAcao, 'trabalho_submetido') ? $this->fatos->autoresValidosDoEvento($eventoId) : [];
            $vazio = ['atividades' => 0, 'dias' => 0];

            foreach ($pessoas as $inscricaoId => $pessoa) {
                $jaCreditados = isset($creditadosPorInscricao[$inscricaoId]) ? $creditadosPorInscricao[$inscricaoId] : [];
                $fatos = [
                    'campos' => $pessoa['campos'],
                    'credenciado' => isset($credenciados[$inscricaoId]),
                    'autor' => isset($autores[$pessoa['usuario_id']]),
                ];

                foreach ($bonusDeAcao as $bonus) {
                    $cumpre = $this->cumpreAcao($bonus, $fatos);

                    if ($this->creditarOuReverter($eventoId, $bonus, $inscricaoId, $jaCreditados, $cumpre, $vazio, [], $fatos)) {
                        $total++;
                    }
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
        if ($inscricaoId === null || GamificacaoService::encerrada($eventoId) || !$this->config->estaAtivo($eventoId)) {
            return [];
        }

        $jaCreditados = $this->creditos->bonusJaCreditados($inscricaoId);
        $creditados = [];

        foreach ($this->bonus->listarAtivos($eventoId) as $bonus) {
            if ($bonus['tipo'] !== 'responder_pesquisa' || in_array((int) $bonus['id'], $jaCreditados, true)) {
                continue;
            }

            $creditado = $this->gravar($eventoId, $bonus, $inscricaoId, ['atividades' => 0, 'dias' => 0], [], []);

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
     *
     * Fase 58: os tipos de acao do participante trazem 'cumpre' e, no de
     * perfil, os campos que faltam ('campos_faltantes'), para a tela dizer o
     * que fazer em vez de mostrar uma barra.
     */
    public function progressoDe(array $evento, $inscricaoId, $usuarioId = null)
    {
        $eventoId = (int) $evento['id'];
        $ativos = $this->bonus->listarAtivos($eventoId);

        if ($ativos === []) {
            return [];
        }

        $resumo = $this->presencas->resumoDaInscricao($eventoId, $inscricaoId);
        $porTipo = $this->precisaDeTipos($ativos) ? $this->presencas->porTipoDaInscricao($eventoId, $inscricaoId) : [];
        $creditos = $this->creditos->resumoParticipante($inscricaoId);
        $fatos = $usuarioId !== null ? $this->fatosDaPessoa($ativos, $eventoId, $inscricaoId, $usuarioId) : $this->fatosVazios();

        $lista = [];

        foreach ($ativos as $bonus) {
            $id = (int) $bonus['id'];
            $credito = isset($creditos['por_bonus'][$id]) ? $creditos['por_bonus'][$id] : null;
            $ehAcao = self::ehAcaoDoParticipante($bonus['tipo']);
            $camposFaltantes = [];

            if ($bonus['tipo'] === 'perfil_campos') {
                foreach (self::camposDoBonus($bonus) as $campo) {
                    if (!in_array($campo, $fatos['campos'], true)) {
                        $camposFaltantes[] = self::CAMPOS_PERFIL_ROTULOS[$campo];
                    }
                }
            }

            $lista[] = [
                'id' => $id,
                'nome' => $bonus['nome'],
                'descricao' => $bonus['descricao'],
                'tipo' => $bonus['tipo'],
                'unidade' => self::unidadeDoTipo($bonus['tipo']),
                'tipo_atividade_nome' => $bonus['tipo_atividade_nome'],
                'exigencia' => (int) $bonus['exigencia'],
                'pontos' => (int) $bonus['pontos'],
                'atual' => $ehAcao ? 0 : $this->progressoAtual($bonus, $resumo, $porTipo),
                'acao_participante' => $ehAcao,
                'cumpre' => $ehAcao ? $this->cumpreAcao($bonus, $fatos) : false,
                'campos_faltantes' => $camposFaltantes,
                'credito' => $credito,
            ];
        }

        return $lista;
    }

    /**
     * Credita (quando cumpre e ainda nao ha' linha) ou desfaz anulacao pelo
     * sistema (quando ha' linha anulada pelo sistema e a condicao voltou).
     * Devolve true so' quando criou credito novo. Mesma regra de
     * apurarInscricao(), para a reconferencia em lote.
     */
    private function creditarOuReverter($eventoId, array $bonus, $inscricaoId, array $jaCreditados, $cumpre, array $resumo, array $porTipo, array $fatos)
    {
        $bonusId = (int) $bonus['id'];
        $credito = isset($jaCreditados[$bonusId]) ? $jaCreditados[$bonusId] : null;

        if ($credito !== null) {
            if ($cumpre && $credito['anulado_em'] !== null && $credito['anulado_por'] === null) {
                $this->creditos->reverterAnulacaoAutomatica($credito['id']);
            }

            return false;
        }

        if (!$cumpre) {
            return false;
        }

        return $this->gravar($eventoId, $bonus, $inscricaoId, $resumo, $porTipo, $fatos) !== null;
    }

    /**
     * Grava o credito com os pontos e a exigencia do momento, congelados.
     * Devolve null quando a linha ja' existia (outra apuracao chegou antes).
     */
    private function gravar($eventoId, array $bonus, $inscricaoId, array $resumo, array $porTipo, array $fatos)
    {
        if ($bonus['tipo'] === 'perfil_campos') {
            $exigenciaAtingida = count(self::camposDoBonus($bonus));
        } elseif ($bonus['tipo'] === 'responder_pesquisa' || self::ehAcaoDoParticipante($bonus['tipo'])) {
            $exigenciaAtingida = 1;
        } else {
            $exigenciaAtingida = $this->progressoAtual($bonus, $resumo, $porTipo);
        }

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
     * Fase 58: se a acao do participante esta' cumprida. O tipo de perfil
     * sem campos marcados nunca cumpre, pelo mesmo motivo da regra de
     * exigencia zero dos tipos de presenca: seria creditar todo mundo.
     */
    private function cumpreAcao(array $bonus, array $fatos)
    {
        switch ($bonus['tipo']) {
            case 'inscricao_evento':
                return true;

            case 'perfil_campos':
                $exigidos = self::camposDoBonus($bonus);

                if ($exigidos === []) {
                    return false;
                }

                return array_diff($exigidos, $fatos['campos']) === [];

            case 'credenciamento_local':
                return $fatos['credenciado'];

            case 'trabalho_submetido':
                return $fatos['autor'];

            default:
                return false;
        }
    }

    /**
     * Fatos de uma pessoa, lidos so' quando o evento tem bonus ativo que
     * precisa deles.
     */
    private function fatosDaPessoa(array $ativos, $eventoId, $inscricaoId, $usuarioId)
    {
        $fatos = $this->fatosVazios();

        if ($this->usaTipo($ativos, 'perfil_campos')) {
            $fatos['campos'] = $this->fatos->camposPreenchidosDoUsuario($usuarioId);
        }

        if ($this->usaTipo($ativos, 'credenciamento_local')) {
            $fatos['credenciado'] = $this->fatos->estaCredenciado($inscricaoId);
        }

        if ($this->usaTipo($ativos, 'trabalho_submetido')) {
            $fatos['autor'] = $this->fatos->ehAutorDeTrabalhoValido($eventoId, $usuarioId);
        }

        return $fatos;
    }

    private function fatosVazios()
    {
        return ['campos' => [], 'credenciado' => false, 'autor' => false];
    }

    private function usaTipo(array $bonusLista, $tipo)
    {
        foreach ($bonusLista as $bonus) {
            if ($bonus['tipo'] === $tipo) {
                return true;
            }
        }

        return false;
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
        return $this->usaTipo($ativos, 'atividades_do_tipo');
    }
}
