<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;
use App\Services\CodigoUnicoService;

/**
 * Fase 58: credenciamento no local (migrations 188 e 189). A configuracao
 * (liga e desliga, codigo, janela) e o fato (quem se credenciou) ficam aqui;
 * os pontos nao, porque quem credita e' um bonus do tipo
 * credenciamento_local (BonusApuracaoService), que le evento_credenciamentos.
 *
 * Diferente do credenciamento "automatico ou assistido" de Dados Gerais
 * (eventos.modo_credenciamento, Fase 39), que e' a homologacao da
 * inscricao: este e' a chegada da pessoa ao evento, lida no proprio celular.
 */
class CredenciamentoLocalRepository
{
    use OperacaoEmLote;

    const PADRAO = [
        'ativo' => 0,
        'codigo' => null,
        'leitura_inicio' => null,
        'leitura_fim' => null,
    ];

    public function buscarConfig($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_credenciamento_config WHERE evento_id = :evento LIMIT 1');
        $stmt->execute(['evento' => (int) $eventoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Leitura protegida para as telas do participante: falha de banco devolve
     * o padrao desligado em vez de derrubar a tela.
     */
    public function configVigente($eventoId)
    {
        try {
            $linha = $this->buscarConfig($eventoId);
        } catch (\PDOException $e) {
            error_log('[Credenciamento] Falha ao ler a configuracao do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return self::PADRAO;
        }

        if ($linha === null) {
            return self::PADRAO;
        }

        return [
            'ativo' => (int) $linha['ativo'],
            'codigo' => $linha['codigo'],
            'leitura_inicio' => $linha['leitura_inicio'],
            'leitura_fim' => $linha['leitura_fim'],
        ];
    }

    /**
     * Grava liga e desliga e a janela. O codigo nasce no primeiro
     * salvamento com o modulo ligado e nunca muda depois: ele ja' pode estar
     * impresso nas paredes do auditorio.
     */
    public function salvarConfig($eventoId, array $dados)
    {
        $antes = $this->buscarConfig($eventoId);
        $codigo = $antes !== null ? $antes['codigo'] : null;

        if ($codigo === null && !empty($dados['ativo'])) {
            $codigo = CodigoUnicoService::gerarCodigoFixoDoEvento();
        }

        $campos = [
            'evento_id' => (int) $eventoId,
            'ativo' => !empty($dados['ativo']) ? 1 : 0,
            'codigo' => $codigo,
            'leitura_inicio' => $dados['leitura_inicio'],
            'leitura_fim' => $dados['leitura_fim'],
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_credenciamento_config (evento_id, ativo, codigo, leitura_inicio, leitura_fim)
             VALUES (:evento_id, :ativo, :codigo, :leitura_inicio, :leitura_fim)
             ON DUPLICATE KEY UPDATE
                 ativo = VALUES(ativo), codigo = VALUES(codigo),
                 leitura_inicio = VALUES(leitura_inicio), leitura_fim = VALUES(leitura_fim)'
        );
        $stmt->execute($campos);

        Auditoria::registrar('salvar', 'evento_credenciamento_config', (int) $eventoId, $antes, $campos);
    }

    /**
     * Configuracao do evento dona do codigo lido, restrita ao evento do
     * leitor (nunca aceita evento vindo do cliente).
     */
    public function buscarConfigPorCodigo($eventoId, $codigo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM evento_credenciamento_config WHERE evento_id = :evento AND codigo = :codigo LIMIT 1'
        );
        $stmt->execute(['evento' => (int) $eventoId, 'codigo' => $codigo]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function buscarDaInscricao($inscricaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_credenciamentos WHERE evento_inscricao_id = :inscricao LIMIT 1');
        $stmt->execute(['inscricao' => (int) $inscricaoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Grava o credenciamento. Devolve true quando gravou agora e false
     * quando a pessoa ja' estava credenciada, inclusive quando outra leitura
     * simultanea chegou antes (erro 23000 da chave unica, tratado como em
     * EventoCheckinRepository::registrar()).
     */
    public function registrar($eventoId, $inscricaoId)
    {
        $pdo = Database::conexao();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_credenciamentos (evento_id, evento_inscricao_id, credenciado_em)
                 VALUES (:evento, :inscricao, NOW())'
            );
            $stmt->execute(['evento' => (int) $eventoId, 'inscricao' => (int) $inscricaoId]);
            $id = (int) $pdo->lastInsertId();
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return false;
            }

            throw $e;
        }

        Auditoria::registrar('credenciar_no_local', 'evento_credenciamentos', $id, null, [
            'evento_id' => (int) $eventoId,
            'evento_inscricao_id' => (int) $inscricaoId,
        ]);

        return true;
    }

    /**
     * Lista nominal de quem se credenciou, com os filtros da tela: busca por
     * nome ou correio eletronico e periodo do credenciamento.
     */
    public function listarPorEvento($eventoId, array $filtros = [])
    {
        $sql =
            'SELECT cr.*, u.nome AS participante_nome, u.email AS participante_email, i.usuario_id
               FROM evento_credenciamentos cr
               INNER JOIN evento_inscricoes i ON i.id = cr.evento_inscricao_id
               INNER JOIN usuarios u ON u.id = i.usuario_id
              WHERE cr.evento_id = :evento';
        $parametros = ['evento' => (int) $eventoId];

        if (!empty($filtros['busca'])) {
            $sql .= ' AND (u.nome LIKE :busca OR u.email LIKE :busca)';
            $parametros['busca'] = '%' . $filtros['busca'] . '%';
        }

        if (!empty($filtros['data_inicio'])) {
            $sql .= ' AND DATE(cr.credenciado_em) >= :data_inicio';
            $parametros['data_inicio'] = $filtros['data_inicio'];
        }

        if (!empty($filtros['data_fim'])) {
            $sql .= ' AND DATE(cr.credenciado_em) <= :data_fim';
            $parametros['data_fim'] = $filtros['data_fim'];
        }

        $sql .= ' ORDER BY cr.credenciado_em ASC';

        $pdo = Database::conexao();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll();
    }

    /**
     * Remove em lote o credenciamento das pessoas escolhidas, para desfazer
     * uma leitura feita por engano. Devolve as linhas removidas, para a
     * mensagem e para a reapuracao dos bonus de quem perdeu.
     */
    public function removerEmLote($eventoId, array $ids)
    {
        $limpos = $this->identificadoresDoLote($ids);

        if ($limpos === []) {
            return [];
        }

        $marcadores = implode(', ', array_fill(0, count($limpos), '?'));
        $pdo = Database::conexao();

        $stmt = $pdo->prepare(
            'SELECT cr.id, cr.evento_inscricao_id, i.usuario_id, u.nome AS participante_nome
               FROM evento_credenciamentos cr
               INNER JOIN evento_inscricoes i ON i.id = cr.evento_inscricao_id
               INNER JOIN usuarios u ON u.id = i.usuario_id
              WHERE cr.id IN (' . $marcadores . ') AND cr.evento_id = ?'
        );
        $stmt->execute(array_merge($limpos, [(int) $eventoId]));
        $alcancados = $stmt->fetchAll();

        if ($alcancados === []) {
            return [];
        }

        $identificadores = array_map(function ($linha) {
            return (int) $linha['id'];
        }, $alcancados);

        $this->executarLote(
            'DELETE FROM evento_credenciamentos',
            '',
            $eventoId,
            $identificadores,
            [],
            'remover',
            'evento_credenciamentos'
        );

        return $alcancados;
    }
}
