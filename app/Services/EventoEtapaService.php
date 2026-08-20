<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EtapaRepository;
use App\Repositories\TrilhaRepository;

/**
 * Fase 34: regra unica de "esta equipe pode ver/participar deste horario de
 * Mentoria/Oficina?". Vira servico porque a mesma pergunta e' feita em 4
 * lugares (index e acao de MentoriaController e OficinaController) mais o
 * painel do participante.
 *
 * Nao reimplementa criterio nenhum: delega a AcessoEtapaService::
 * motivoBloqueio(), o MESMO que libera a submissao na etapa. O que este
 * servico acrescenta e':
 *   1. horario sem etapa_id = aberto a todos (regra da propria fase);
 *   2. a conferencia de trilha, que motivoBloqueio() NAO faz - ele assume
 *      que a etapa recebida ja e' da trilha da equipe. Mesma protecao que
 *      RequerimentoController::modeloDisponivelOuAbortar() ja aplicava
 *      antes de chamar o servico (padrao da Fase 30);
 *   3. memoizacao por (etapa_id, equipe_id) - motivoBloqueio() faz 3-4
 *      consultas por chamada, e uma tela lista dezenas de horarios que
 *      costumam apontar pra mesma etapa. Sem o cache, uma listagem vira
 *      centenas de consultas.
 */
class EventoEtapaService
{
    private $etapas;
    private $trilhas;
    private $acessoEtapa;
    private $memo = [];

    public function __construct()
    {
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->acessoEtapa = new AcessoEtapaService();
    }

    /**
     * null = a equipe pode; string = motivo do bloqueio (nunca mostrado ao
     * participante numa listagem, mas util no 403 e em log/diagnostico).
     */
    public function motivoBloqueio(array $horario, array $equipe)
    {
        $etapaId = isset($horario['etapa_id']) && $horario['etapa_id'] !== null ? (int) $horario['etapa_id'] : null;

        if ($etapaId === null) {
            return null;
        }

        $chave = $etapaId . ':' . (int) $equipe['id'];

        if (!array_key_exists($chave, $this->memo)) {
            $this->memo[$chave] = $this->calcular($etapaId, $equipe);
        }

        return $this->memo[$chave];
    }

    public function podeParticipar(array $horario, array $equipe)
    {
        return $this->motivoBloqueio($horario, $equipe) === null;
    }

    /**
     * Filtra uma listagem inteira preservando as chaves de ordem original.
     */
    public function apenasVisiveis(array $horarios, array $equipe)
    {
        $visiveis = [];

        foreach ($horarios as $horario) {
            if ($this->podeParticipar($horario, $equipe)) {
                $visiveis[] = $horario;
            }
        }

        return $visiveis;
    }

    /**
     * Painel do participante: dado o conjunto de etapas vinculadas aos
     * horarios de um concurso (com null representando "aberto a todos"),
     * responde se a equipe enxergaria ao menos um horario. Evita acender o
     * botao do painel pra uma tela que viria vazia.
     */
    public function algumHorarioVisivel(array $etapaIds, array $equipe)
    {
        foreach ($etapaIds as $etapaId) {
            if ($this->podeParticipar(['etapa_id' => $etapaId], $equipe)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Etapas da trilha para o select do admin, cada uma com a chave extra
     * "restringe". Todas sao listadas de proposito - esconder as que nao
     * restringem faria o admin achar que a etapa sumiu do sistema. A tela
     * marca as nao-restritivas e avisa quando uma delas e' escolhida.
     *
     * Nao restringem, porque motivoBloqueio() libera todo mundo nesses dois
     * casos:
     *   - ordem 1 (nao ha etapa anterior de onde classificar);
     *   - etapa cuja anterior nao e' avaliada por avaliadores.
     */
    public function etapasParaVinculo($trilhaId)
    {
        $lista = [];

        foreach ($this->etapas->listarPorTrilha($trilhaId) as $etapa) {
            $etapa['restringe'] = $this->restringe($etapa);
            $lista[] = $etapa;
        }

        return $lista;
    }

    public function restringe(array $etapa)
    {
        if ((int) $etapa['ordem'] <= 1) {
            return false;
        }

        $anterior = $this->etapas->buscarAnteriorNaTrilha($etapa['trilha_id'], (int) $etapa['ordem']);

        return $anterior !== null && $anterior['mecanismo_avaliacao'] === 'avaliadores';
    }

    /**
     * Fase 34: valida a etapa escolhida no formulario do admin. null =
     * "aberto a todos" e e' sempre valido. Devolve a mensagem de erro ou
     * null. Recusa etapa inexistente e etapa de outro concurso (etapa_id
     * forjado no POST). Etapa que nao restringe ninguem e' aceita - ver o
     * comentario no corpo.
     */
    public function erroDeVinculo($etapaId, $concursoId)
    {
        if ($etapaId === null) {
            return null;
        }

        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            return 'A etapa escolhida não existe.';
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);

        if ($trilha === null || (int) $trilha['concurso_id'] !== (int) $concursoId) {
            return 'A etapa escolhida não pertence a este concurso.';
        }

        // Etapa que nao restringe (ordem 1, ou anterior sem avaliadores) e'
        // escolha VALIDA: a tela lista todas e avisa qual nao restringe, em
        // vez de esconder - esconder faria o admin achar que a etapa sumiu.
        // O efeito pratico de vincular a uma dessas e' o mesmo de "aberto a
        // todos", e o aviso na tela deixa isso explicito antes de salvar.
        return null;
    }

    private function calcular($etapaId, array $equipe)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            return 'A etapa vinculada a este compromisso não existe mais.';
        }

        // motivoBloqueio() olha a etapa ANTERIOR da trilha da etapa recebida -
        // com uma equipe de outra trilha ele compararia coisas que nao se
        // relacionam. A checagem tem que vir antes, e explicita.
        if ((int) $etapa['trilha_id'] !== (int) $equipe['trilha_id']) {
            return 'Este compromisso é de outra trilha.';
        }

        return $this->acessoEtapa->motivoBloqueio($etapa, (int) $equipe['id']);
    }
}
