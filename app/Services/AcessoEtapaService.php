<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\EtapaRepository;
use App\Repositories\ResultadoEtapaRepository;
use App\Repositories\SubmissaoRepository;

/**
 * Fase 17 (bug de fumaça, pos-teste): centraliza a checagem de "a equipe pode
 * acessar esta etapa agora?" - antes so existia dentro de
 * SubmissaoController::exigirClassificacaoNaEtapaAnterior() (so' descoberta
 * ao clicar em "Preencher"). Extraida pra tambem ser usada no PAINEL do
 * participante, que precisa saber ANTES de mostrar o link se ele vai
 * funcionar ou nao.
 */
class AcessoEtapaService
{
    private $etapas;
    private $resultados;
    private $submissoes;

    public function __construct()
    {
        $this->etapas = new EtapaRepository();
        $this->resultados = new ResultadoEtapaRepository();
        $this->submissoes = new SubmissaoRepository();
    }

    /**
     * Retorna null se a equipe pode acessar a etapa agora, ou uma mensagem
     * explicando por que nao (etapa anterior nao publicada / equipe nao
     * classificada). So se aplica quando a etapa anterior e' avaliada por
     * avaliadores - etapas de ordem 1 (cadastro) ou sem etapa anterior
     * avaliada nunca bloqueiam por aqui.
     */
    public function motivoBloqueio(array $etapa, $equipeId)
    {
        if ((int) $etapa['ordem'] <= 1) {
            return null;
        }

        $etapaAnterior = $this->etapas->buscarAnteriorNaTrilha($etapa['trilha_id'], (int) $etapa['ordem']);

        if ($etapaAnterior === null || $etapaAnterior['mecanismo_avaliacao'] !== 'avaliadores') {
            return null;
        }

        if (!$this->resultados->jaPublicado($etapaAnterior['id'])) {
            return 'O resultado da etapa anterior ("' . $etapaAnterior['nome'] . '") ainda não foi publicado.';
        }

        $submissaoAnterior = $this->submissoes->buscarPorEquipeEEtapa($equipeId, $etapaAnterior['id']);
        $resultado = $submissaoAnterior !== null
            ? $this->resultados->buscarPorSubmissaoEEtapa($submissaoAnterior['id'], $etapaAnterior['id'])
            : null;

        if ($resultado === null || !$resultado['classificado']) {
            return 'Sua equipe não foi classificada na etapa anterior ("' . $etapaAnterior['nome'] . '").';
        }

        return null;
    }
}
