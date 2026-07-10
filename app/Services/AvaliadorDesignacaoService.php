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
     * Calcula (sem persistir) a distribuicao round-robin: para cada submissao
     * que ainda nao tem a quantidade de avaliadores configurada na etapa,
     * sugere o(s) avaliador(es) com menor carga atual na etapa, sem repetir
     * avaliador na mesma submissao. Uma linha por vaga faltante (uma
     * submissao pode gerar mais de uma linha se qtd_avaliadores_por_submissao > 1).
     */
    public function calcularDistribuicao($etapaId)
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

        $candidatos = array_map(function ($avaliador) {
            return ['id' => (int) $avaliador['id'], 'nome' => $avaliador['nome']];
        }, $avaliadores);

        $quantidadeNecessaria = max(1, (int) $etapa['qtd_avaliadores_por_submissao']);
        $linhas = [];

        foreach ($this->submissoes->listarPorEtapa($etapaId) as $submissao) {
            $jaDesignados = array_map('intval', array_column($this->designacoes->listarPorSubmissao($submissao['id']), 'usuario_id'));
            $faltando = $quantidadeNecessaria - count($jaDesignados);

            for ($i = 0; $i < $faltando; $i++) {
                $sugeridoId = $this->escolherMenosCarregado($carga, $jaDesignados);

                if ($sugeridoId === null) {
                    break;
                }

                $linhas[] = [
                    'submissao_id' => (int) $submissao['id'],
                    'nome_equipe' => $submissao['nome_equipe'],
                    'candidatos' => $candidatos,
                    'sugerido_id' => $sugeridoId,
                ];

                $carga[$sugeridoId]++;
                $jaDesignados[] = $sugeridoId;
            }
        }

        return $linhas;
    }

    /**
     * Persiste as atribuicoes ja revisadas/editadas pelo Admin na tela de
     * previa. $atribuicoes e uma lista de ['submissao_id' => int, 'usuario_id' => int].
     */
    public function confirmarDistribuicao($etapaId, array $atribuicoes, $atribuidoPor = null)
    {
        $total = 0;

        foreach ($atribuicoes as $atribuicao) {
            $submissaoId = (int) $atribuicao['submissao_id'];
            $usuarioId = (int) $atribuicao['usuario_id'];

            if ($usuarioId <= 0) {
                continue;
            }

            if (!$this->designacoes->existeDesignacao($submissaoId, $usuarioId)) {
                $this->designacoes->criar($submissaoId, $usuarioId, $atribuidoPor);
                $total++;
            }
        }

        return $total;
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
