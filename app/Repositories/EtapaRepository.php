<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class EtapaRepository
{
    public function listarPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM etapas WHERE trilha_id = :trilha_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM etapas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $etapa = $stmt->fetch();

        return $etapa !== false ? $etapa : null;
    }

    public function buscarPorTrilhaENome($trilhaId, $nome)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM etapas WHERE trilha_id = :trilha_id AND nome = :nome LIMIT 1');
        $stmt->execute(['trilha_id' => $trilhaId, 'nome' => $nome]);

        $etapa = $stmt->fetch();

        return $etapa !== false ? $etapa : null;
    }

    public function buscarCadastroDaTrilha($trilhaId)
    {
        return $this->buscarPorTrilhaEOrdem($trilhaId, 1);
    }

    /**
     * Fase 17 (Bug 1): busca por ordem em vez de nome - o nome da etapa diverge
     * entre trilhas (confirmado: a etapa de submissao e' "ordem = 2" nas duas,
     * mesmo com nomes diferentes), tornando a busca por nome fragil demais
     * para scripts como a importacao do Google Forms.
     */
    public function buscarPorTrilhaEOrdem($trilhaId, $ordem)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM etapas WHERE trilha_id = :trilha_id AND ordem = :ordem LIMIT 1');
        $stmt->execute(['trilha_id' => $trilhaId, 'ordem' => $ordem]);

        $etapa = $stmt->fetch();

        return $etapa !== false ? $etapa : null;
    }

    /**
     * Etapa imediatamente anterior na trilha, pela ordem (nao assume ordem-1
     * contiguo, ja que etapas podem ter sido removidas no meio) - usada para
     * a trava de classificacao antes de liberar a submissao da proxima etapa.
     */
    public function buscarAnteriorNaTrilha($trilhaId, $ordemAtual)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM etapas WHERE trilha_id = :trilha_id AND ordem < :ordem ORDER BY ordem DESC LIMIT 1'
        );
        $stmt->execute(['trilha_id' => $trilhaId, 'ordem' => $ordemAtual]);

        $etapa = $stmt->fetch();

        return $etapa !== false ? $etapa : null;
    }

    public function alternarCapturaAtiva($etapaId)
    {
        $antes = $this->buscarPorId($etapaId);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE etapas SET captura_ativa = NOT captura_ativa WHERE id = :id');
        $stmt->execute(['id' => $etapaId]);

        Auditoria::registrar('alternar_captura_ativa', 'etapas', $etapaId, $antes, null);
    }

    /**
     * Remoção real (sem soft-delete). Nenhuma FK que aponta para etapas tem
     * ON DELETE CASCADE, entao o proprio banco recusa (PDOException, SQLSTATE
     * 23000) remover uma etapa que ja tenha criterios/formula/submissoes/notas
     * vinculadas — o controller trata esse erro com uma mensagem amigavel.
     */
    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM etapas WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'etapas', $id, $antes, null);
    }

    public function criar(
        $trilhaId,
        $nome,
        $descricao,
        $ordem,
        $dataInicio,
        $dataFim,
        $formularioDinamicoId,
        $regraTransicaoTipo = '',
        $regraTransicaoValor = '',
        array $configAvaliacao = []
    ) {
        $configAvaliacao = $this->normalizarConfigAvaliacao($configAvaliacao);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO etapas (trilha_id, nome, descricao, ordem, data_inicio, data_fim, formulario_dinamico_id,
                                  regra_transicao_tipo, regra_transicao_valor, modo_designacao,
                                  qtd_avaliadores_por_submissao, modo_consolidacao, modo_sigilo, modo_avanco,
                                  mecanismo_avaliacao, modo_feedback_avaliador)
             VALUES (:trilha_id, :nome, :descricao, :ordem, :data_inicio, :data_fim, :formulario_dinamico_id,
                     :regra_transicao_tipo, :regra_transicao_valor, :modo_designacao,
                     :qtd_avaliadores_por_submissao, :modo_consolidacao, :modo_sigilo, :modo_avanco,
                     :mecanismo_avaliacao, :modo_feedback_avaliador)'
        );
        $dados = array_merge([
            'trilha_id' => $trilhaId,
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'ordem' => $ordem,
            'data_inicio' => $dataInicio !== '' ? $dataInicio : null,
            'data_fim' => $dataFim !== '' ? $dataFim : null,
            'formulario_dinamico_id' => $formularioDinamicoId !== '' ? $formularioDinamicoId : null,
            'regra_transicao_tipo' => $regraTransicaoTipo !== '' ? $regraTransicaoTipo : null,
            'regra_transicao_valor' => $regraTransicaoValor !== '' ? $regraTransicaoValor : null,
        ], $configAvaliacao);
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'etapas', $id, null, $dados);

        return $id;
    }

    public function atualizar(
        $id,
        $nome,
        $descricao,
        $ordem,
        $dataInicio,
        $dataFim,
        $formularioDinamicoId,
        $regraTransicaoTipo = '',
        $regraTransicaoValor = '',
        array $configAvaliacao = []
    ) {
        $antes = $this->buscarPorId($id);
        $configAvaliacao = $this->normalizarConfigAvaliacao($configAvaliacao);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE etapas
             SET nome = :nome, descricao = :descricao, ordem = :ordem, data_inicio = :data_inicio,
                 data_fim = :data_fim, formulario_dinamico_id = :formulario_dinamico_id,
                 regra_transicao_tipo = :regra_transicao_tipo, regra_transicao_valor = :regra_transicao_valor,
                 modo_designacao = :modo_designacao, qtd_avaliadores_por_submissao = :qtd_avaliadores_por_submissao,
                 modo_consolidacao = :modo_consolidacao, modo_sigilo = :modo_sigilo, modo_avanco = :modo_avanco,
                 mecanismo_avaliacao = :mecanismo_avaliacao, modo_feedback_avaliador = :modo_feedback_avaliador
             WHERE id = :id'
        );
        $depois = array_merge([
            'nome' => $nome,
            'descricao' => $descricao !== '' ? $descricao : null,
            'ordem' => $ordem,
            'data_inicio' => $dataInicio !== '' ? $dataInicio : null,
            'data_fim' => $dataFim !== '' ? $dataFim : null,
            'formulario_dinamico_id' => $formularioDinamicoId !== '' ? $formularioDinamicoId : null,
            'regra_transicao_tipo' => $regraTransicaoTipo !== '' ? $regraTransicaoTipo : null,
            'regra_transicao_valor' => $regraTransicaoValor !== '' ? $regraTransicaoValor : null,
        ], $configAvaliacao);
        $stmt->execute($depois + ['id' => $id]);

        Auditoria::registrar('atualizar', 'etapas', $id, $antes, $depois);
    }

    private function normalizarConfigAvaliacao(array $configAvaliacao)
    {
        $modoDesignacao = isset($configAvaliacao['modo_designacao']) ? $configAvaliacao['modo_designacao'] : '';
        $qtdAvaliadores = isset($configAvaliacao['qtd_avaliadores_por_submissao'])
            ? (int) $configAvaliacao['qtd_avaliadores_por_submissao']
            : 1;
        $modoConsolidacao = isset($configAvaliacao['modo_consolidacao']) ? $configAvaliacao['modo_consolidacao'] : 'unico';
        $modoSigilo = isset($configAvaliacao['modo_sigilo']) ? $configAvaliacao['modo_sigilo'] : 'aberto';
        $modoAvanco = isset($configAvaliacao['modo_avanco']) ? $configAvaliacao['modo_avanco'] : 'manual';
        $mecanismoAvaliacao = isset($configAvaliacao['mecanismo_avaliacao']) ? $configAvaliacao['mecanismo_avaliacao'] : '';
        $modoFeedbackAvaliador = isset($configAvaliacao['modo_feedback_avaliador']) ? $configAvaliacao['modo_feedback_avaliador'] : '';

        return [
            'modo_designacao' => in_array($modoDesignacao, ['manual', 'aberto', 'automatico', 'sorteio_categoria'], true) ? $modoDesignacao : null,
            'qtd_avaliadores_por_submissao' => $qtdAvaliadores > 0 ? $qtdAvaliadores : 1,
            'modo_consolidacao' => in_array($modoConsolidacao, ['media_criterio', 'media_ne', 'unico'], true) ? $modoConsolidacao : 'unico',
            'modo_sigilo' => in_array($modoSigilo, ['cego', 'aberto'], true) ? $modoSigilo : 'aberto',
            'modo_avanco' => in_array($modoAvanco, ['automatico', 'manual'], true) ? $modoAvanco : 'manual',
            'mecanismo_avaliacao' => in_array($mecanismoAvaliacao, ['nenhuma', 'administrador', 'avaliadores'], true) ? $mecanismoAvaliacao : 'nenhuma',
            'modo_feedback_avaliador' => in_array($modoFeedbackAvaliador, ['nenhum', 'submissao', 'criterio'], true) ? $modoFeedbackAvaliador : 'nenhum',
        ];
    }
}
