<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 53: versoes do PDF dos Anais de um evento (evento_anais_versoes).
 * Cada envio do Administrador vira uma versao numerada, crescente por
 * evento. O arquivo fica na pasta privada ate ser publicado; versao ja
 * publicada nunca e' apagada, so' arquivada pela versao seguinte.
 */
class EventoAnaisVersaoRepository
{
    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT v.*, u.nome AS enviado_por_nome
             FROM evento_anais_versoes v
             LEFT JOIN usuarios u ON u.id = v.enviado_por
             WHERE v.evento_id = :evento_id
             ORDER BY v.numero DESC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Sempre escopada pelo evento: um id de outro evento forjado na URL nao
     * devolve nada.
     */
    public function buscarDoEvento($eventoId, $id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_anais_versoes WHERE id = :id AND evento_id = :evento_id LIMIT 1');
        $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);

        $versao = $stmt->fetch();

        return $versao !== false ? $versao : null;
    }

    public function jaFoiPublicadaAlguma($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM evento_anais_versoes WHERE evento_id = :evento_id AND publicado_em IS NOT NULL');
        $stmt->execute(['evento_id' => $eventoId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Grava a versao seguinte. O numero e' calculado aqui e protegido pela
     * chave unica (evento_id, numero): dois envios simultaneos nunca ficam
     * com o mesmo numero, o segundo tenta de novo com o numero seguinte.
     */
    public function criar($eventoId, array $dados)
    {
        $pdo = Database::conexao();

        for ($tentativa = 1; $tentativa <= 3; $tentativa++) {
            $stmt = $pdo->prepare('SELECT COALESCE(MAX(numero), 0) + 1 FROM evento_anais_versoes WHERE evento_id = :evento_id');
            $stmt->execute(['evento_id' => $eventoId]);
            $numero = (int) $stmt->fetchColumn();

            $registro = [
                'evento_id' => $eventoId,
                'numero' => $numero,
                'arquivo_path' => $dados['arquivo_path'],
                'nome_original' => $dados['nome_original'],
                'tamanho_bytes' => $dados['tamanho_bytes'],
                'sha256' => $dados['sha256'],
                'observacao' => $dados['observacao'],
                'enviado_por' => $dados['enviado_por'],
            ];

            try {
                $insercao = $pdo->prepare(
                    'INSERT INTO evento_anais_versoes (evento_id, numero, arquivo_path, nome_original, tamanho_bytes, sha256, observacao, enviado_por)
                     VALUES (:evento_id, :numero, :arquivo_path, :nome_original, :tamanho_bytes, :sha256, :observacao, :enviado_por)'
                );
                $insercao->execute($registro);
                $id = (int) $pdo->lastInsertId();

                Auditoria::registrar('enviar_versao', 'evento_anais_versoes', $id, null, $registro);

                return ['id' => $id, 'numero' => $numero];
            } catch (\PDOException $e) {
                // 23000 = violacao de chave unica: outro envio pegou o mesmo numero.
                if ($e->getCode() !== '23000' || $tentativa === 3) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Não foi possível numerar a versão dos Anais.');
    }

    /**
     * Remove uma versao que nunca foi publicada e devolve a linha removida
     * (para quem chamou apagar o arquivo). Versao publicada, hoje ou antes,
     * nunca e' removida: fica como historico.
     */
    public function removerNaoPublicada($eventoId, $id)
    {
        $versao = $this->buscarDoEvento($eventoId, $id);

        if ($versao === null) {
            return null;
        }

        if ($versao['publicado_em'] !== null || $versao['documento_id'] !== null) {
            throw new \RuntimeException('Uma versão que já foi publicada não pode ser removida: ela fica no histórico.');
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_anais_versoes WHERE id = :id AND evento_id = :evento_id AND publicado_em IS NULL');
        $stmt->execute(['id' => $id, 'evento_id' => $eventoId]);

        if ($stmt->rowCount() === 0) {
            throw new \RuntimeException('Não foi possível remover a versão: ela mudou de situação.');
        }

        Auditoria::registrar('remover_versao', 'evento_anais_versoes', (int) $id, $versao, null);

        return $versao;
    }
}
