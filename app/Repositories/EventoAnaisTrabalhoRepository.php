<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 54: PDF final de cada trabalho para o volume dos Anais e a posicao do
 * trabalho no volume (evento_anais_trabalhos, uma linha por trabalho, criada
 * no primeiro envio ou na primeira reordenacao). Quem consta nos Anais
 * continua decidido por evento_anais_exclusoes (Fase 53).
 */
class EventoAnaisTrabalhoRepository
{
    public function buscarPorTrabalho($trabalhoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_anais_trabalhos WHERE trabalho_id = :trabalho_id LIMIT 1');
        $stmt->execute(['trabalho_id' => $trabalhoId]);

        $registro = $stmt->fetch();

        return $registro !== false ? $registro : null;
    }

    /**
     * Linhas do evento, indexadas pelo id do trabalho.
     */
    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_anais_trabalhos WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => $eventoId]);

        $porTrabalho = [];

        foreach ($stmt->fetchAll() as $linha) {
            $porTrabalho[(int) $linha['trabalho_id']] = $linha;
        }

        return $porTrabalho;
    }

    /**
     * Grava o arquivo enviado pelo autor e devolve o caminho do arquivo
     * anterior (ou null), para quem chamou apagar o antigo so' depois da
     * gravacao confirmada. A linha e' garantida antes da transacao para que
     * o FOR UPDATE trave um registro existente: dois envios simultaneos do
     * mesmo trabalho se serializam, e o segundo recebe como "anterior" o
     * arquivo do primeiro.
     *
     * $dados: arquivo_path, nome_original, paginas, tamanho_bytes, sha256,
     * enviado_por.
     */
    public function gravarArquivo($eventoId, $trabalhoId, array $dados)
    {
        $pdo = Database::conexao();
        $pdo->prepare(
            'INSERT INTO evento_anais_trabalhos (evento_id, trabalho_id) VALUES (:evento_id, :trabalho_id)
             ON DUPLICATE KEY UPDATE trabalho_id = trabalho_id'
        )->execute(['evento_id' => $eventoId, 'trabalho_id' => $trabalhoId]);

        $valores = [
            'arquivo_path' => $dados['arquivo_path'],
            'nome_original' => $dados['nome_original'],
            'paginas' => $dados['paginas'],
            'tamanho_bytes' => $dados['tamanho_bytes'],
            'sha256' => $dados['sha256'],
            'enviado_por' => $dados['enviado_por'],
        ];

        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare(
                'SELECT id, arquivo_path FROM evento_anais_trabalhos WHERE trabalho_id = :trabalho_id AND evento_id = :evento_id LIMIT 1 FOR UPDATE'
            );
            $stmt->execute(['trabalho_id' => $trabalhoId, 'evento_id' => $eventoId]);
            $linha = $stmt->fetch();

            if ($linha === false) {
                throw new \RuntimeException('Trabalho não encontrado nos Anais deste evento.');
            }

            $pdo->prepare(
                'UPDATE evento_anais_trabalhos
                 SET arquivo_path = :arquivo_path, nome_original = :nome_original, paginas = :paginas,
                     tamanho_bytes = :tamanho_bytes, sha256 = :sha256, enviado_por = :enviado_por, enviado_em = NOW()
                 WHERE id = :id'
            )->execute($valores + ['id' => $linha['id']]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        $anterior = !empty($linha['arquivo_path']) ? $linha['arquivo_path'] : null;

        Auditoria::registrar('enviar_pdf_final', 'evento_anais_trabalhos', (int) $linha['id'], [
            'arquivo_path' => $anterior,
        ], $valores + ['evento_id' => (int) $eventoId, 'trabalho_id' => (int) $trabalhoId]);

        return $anterior;
    }

    /**
     * Grava a posicao de cada trabalho na ordem da lista recebida (1, 2,
     * 3...). So' entram trabalhos deste evento; qualquer outro id (lista
     * manipulada) e' ignorado.
     */
    public function reordenar($eventoId, array $trabalhoIds)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT id FROM trabalhos WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => $eventoId]);
        $doEvento = array_flip(array_map('intval', $stmt->fetchAll(\PDO::FETCH_COLUMN)));

        $gravados = [];
        $pdo->beginTransaction();

        try {
            $gravacao = $pdo->prepare(
                'INSERT INTO evento_anais_trabalhos (evento_id, trabalho_id, ordem) VALUES (:evento_id, :trabalho_id, :ordem)
                 ON DUPLICATE KEY UPDATE ordem = VALUES(ordem)'
            );

            foreach ($trabalhoIds as $trabalhoId) {
                $trabalhoId = (int) $trabalhoId;

                if (!isset($doEvento[$trabalhoId]) || in_array($trabalhoId, $gravados, true)) {
                    continue;
                }

                $gravados[] = $trabalhoId;
                $gravacao->execute([
                    'evento_id' => $eventoId,
                    'trabalho_id' => $trabalhoId,
                    'ordem' => count($gravados),
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();

            throw $e;
        }

        Auditoria::registrar('reordenar', 'evento_anais_trabalhos', null, null, ['evento_id' => (int) $eventoId, 'trabalhos' => $gravados]);

        return count($gravados);
    }
}
