<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\AvaliadorCategoriaRepository;
use App\Repositories\AvaliadorDesignacaoRepository;
use App\Repositories\EtapaCategoriaAvaliadorRepository;
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
    private $avaliadorCategorias;
    private $etapaCategorias;

    public function __construct()
    {
        $this->designacoes = new AvaliadorDesignacaoRepository();
        $this->etapas = new EtapaRepository();
        $this->trilhas = new TrilhaRepository();
        $this->perfis = new PerfilRepository();
        $this->submissoes = new SubmissaoRepository();
        $this->avaliadorCategorias = new AvaliadorCategoriaRepository();
        $this->etapaCategorias = new EtapaCategoriaAvaliadorRepository();
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
     * Calcula (sem persistir) o sorteio garantindo 1 avaliador de cada
     * categoria configurada em etapa_categoria_avaliadores por submissao
     * (Fase 10). Para cada vaga faltante, sorteia aleatoriamente entre os
     * avaliadores da categoria empatados na menor carga -- mesma garantia de
     * equilibrio do round-robin acima, mas sem repetir sempre o mesmo trio.
     */
    public function calcularDistribuicaoPorCategoria($etapaId)
    {
        $etapa = $this->etapas->buscarPorId($etapaId);

        if ($etapa === null) {
            throw new \RuntimeException('Etapa não encontrada.');
        }

        $vagas = $this->etapaCategorias->listarPorEtapa($etapaId);

        if (empty($vagas)) {
            throw new \RuntimeException('Configure as vagas por categoria desta etapa antes de sortear.');
        }

        $carga = [];
        $candidatosPorCategoria = [];

        foreach ($vagas as $vaga) {
            $categoriaId = (int) $vaga['categoria_avaliador_id'];
            $avaliadores = $this->avaliadorCategorias->listarUsuariosPorCategoria($categoriaId);

            if (empty($avaliadores)) {
                throw new \RuntimeException('A categoria "' . $vaga['categoria_nome'] . '" não tem nenhum avaliador vinculado ainda.');
            }

            $candidatosPorCategoria[$categoriaId] = array_map(function ($avaliador) {
                return ['id' => (int) $avaliador['id'], 'nome' => $avaliador['nome']];
            }, $avaliadores);

            foreach ($avaliadores as $avaliador) {
                $carga[(int) $avaliador['id']] = $this->designacoes->contarPorUsuarioNaEtapa($avaliador['id'], $etapaId);
            }
        }

        $linhas = [];

        foreach ($this->submissoes->listarPorEtapa($etapaId) as $submissao) {
            $jaDesignadosGeral = array_map('intval', array_column($this->designacoes->listarPorSubmissao($submissao['id']), 'usuario_id'));

            foreach ($vagas as $vaga) {
                $categoriaId = (int) $vaga['categoria_avaliador_id'];
                $quantidadeNecessaria = max(1, (int) $vaga['quantidade']);
                $candidatosCategoria = $candidatosPorCategoria[$categoriaId];

                $jaDesignadosNaCategoria = array_intersect($jaDesignadosGeral, array_column($candidatosCategoria, 'id'));
                $faltando = $quantidadeNecessaria - count($jaDesignadosNaCategoria);

                for ($i = 0; $i < $faltando; $i++) {
                    $sugeridoId = $this->sortearMenosCarregado($carga, $candidatosCategoria, $jaDesignadosGeral);

                    if ($sugeridoId === null) {
                        break;
                    }

                    $linhas[] = [
                        'submissao_id' => (int) $submissao['id'],
                        'nome_equipe' => $submissao['nome_equipe'],
                        'categoria_nome' => $vaga['categoria_nome'],
                        'candidatos' => $candidatosCategoria,
                        'sugerido_id' => $sugeridoId,
                    ];

                    $carga[$sugeridoId]++;
                    $jaDesignadosGeral[] = $sugeridoId;
                }
            }
        }

        return $linhas;
    }

    /**
     * Persiste as atribuicoes ja revisadas/editadas pelo Admin na tela de
     * previa. $atribuicoes e uma lista de ['submissao_id' => int, 'usuario_id' => int].
     *
     * $origem (Fase 17, Bug 3): quem chama decide 'sorteio' ou 'manual' -
     * so' a distribuicao por "Sorteio aleatorio garantindo 1 avaliador de
     * cada categoria" fica travada contra remocao (nao a "automatica
     * balanceada", que o usuario nao pediu pra travar).
     */
    public function confirmarDistribuicao($etapaId, array $atribuicoes, $atribuidoPor = null, $origem = 'manual')
    {
        $total = 0;

        foreach ($atribuicoes as $atribuicao) {
            $submissaoId = (int) $atribuicao['submissao_id'];
            $usuarioId = (int) $atribuicao['usuario_id'];

            if ($usuarioId <= 0) {
                continue;
            }

            if (!$this->designacoes->existeDesignacao($submissaoId, $usuarioId)) {
                $this->designacoes->criar($submissaoId, $usuarioId, $atribuidoPor, $origem);
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

    /**
     * Sorteia aleatoriamente entre os candidatos da categoria empatados na
     * menor carga -- mesma logica de escolherMenosCarregado(), trocando "o
     * primeiro empatado" por um sorteio entre os empatados.
     */
    private function sortearMenosCarregado(array $carga, array $candidatosCategoria, array $jaDesignados)
    {
        $menorCarga = null;
        $empatados = [];

        foreach ($candidatosCategoria as $candidato) {
            $id = $candidato['id'];

            if (in_array($id, $jaDesignados, true)) {
                continue;
            }

            $quantidade = $carga[$id];

            if ($menorCarga === null || $quantidade < $menorCarga) {
                $menorCarga = $quantidade;
                $empatados = [$id];
            } elseif ($quantidade === $menorCarga) {
                $empatados[] = $id;
            }
        }

        if (empty($empatados)) {
            return null;
        }

        return $empatados[array_rand($empatados)];
    }
}
