<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\AvaliadorDesignacaoRepository;
use App\Repositories\EtapaRepository;
use App\Repositories\PerfilRepository;
use App\Repositories\SubmissaoRepository;
use App\Repositories\TrilhaRepository;

class AvaliadorDesignacaoService
{
    private $designacoes;
    private $etapas;
    private $trilhas;
    private $perfis;
    private $submissoes;

    public function __construct()
    {
        $this->designacoes = new AvaliadorDesignacaoRepository();
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->perfis = new PerfilRepository();
        $this->submissoes = new SubmissaoRepository();
    }

    /**
     * Distribuicao round-robin: para cada submissao que ainda nao tem a
     * quantidade de avaliadores configurada na etapa, atribui o(s) avaliador(es)
     * com menor carga atual na etapa, sem repetir avaliador na mesma submissao.
     */
    public function distribuirAutomaticamente($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            throw new \RuntimeException('Etapa não encontrada.');
        }

        $trilha = $this->trilhas->buscarPorId($etapa['trilha_id']);
        $avaliadores = $this->perfis->listarUsuariosPorPerfilConcurso('avaliador', $trilha['concurso_id']);

        if (empty($avaliadores)) {
            throw new \RuntimeException('Não há avaliadores vinculados a este concurso ainda.');
        }

        $carga = [];
        foreach ($avaliadores as $avaliador) {
            $carga[(int) $avaliador['id']] = $this->designacoes->contarPorUsuarioNaEtapa($avaliador['id'], $etapaId);
        }

        $quantidadeNecessaria = max(1, (int) $etapa['qtd_avaliadores_por_submissao']);
        $totalAtribuicoes = 0;

        foreach ($this->submissoes->listarPorEtapa($etapaId) as $submissao) {
            $jaDesignados = array_map('intval', array_column($this->designacoes->listarPorSubmissao($submissao['id']), 'usuario_id'));
            $faltando = $quantidadeNecessaria - count($jaDesignados);

            for ($i = 0; $i < $faltando; $i++) {
                $candidato = $this->escolherMenosCarregado($carga, $jaDesignados);

                if ($candidato === null) {
                    break;
                }

                $this->designacoes->criar($submissao['id'], $candidato, null);
                $carga[$candidato]++;
                $jaDesignados[] = $candidato;
                $totalAtribuicoes++;
            }
        }

        return $totalAtribuicoes;
    }

    private function escolherMenosCarregado(array $carga, array $jaDesignados)
    {
        $melhorId = null;
        $melhorCarga = null;

        foreach ($carga as $usuarioId => $quantidade) {
            if (in_array($usuarioId, $jaDesignados, true)) {
                continue;
            }

            if ($melhorCarga === null || $quantidade < $melhorCarga) {
                $melhorId = $usuarioId;
                $melhorCarga = $quantidade;
            }
        }

        return $melhorId;
    }
}
