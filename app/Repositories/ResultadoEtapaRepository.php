<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class ResultadoEtapaRepository
{
    /**
     * Fase 51: o desempate entra na ordenacao pelo id, que e' a ordem em que
     * as linhas foram inseridas na publicacao (ou seja, a classificacao ja
     * desempatada). Sem isso, duas notas iguais podiam aparecer em ordem
     * diferente da publicada, bem na tela que agora explica qual regra
     * decidiu aquele empate.
     */
    public function listarPorEtapa($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT re.*, s.equipe_id, s.criado_em, eq.nome_equipe
             FROM resultados_etapa re
             INNER JOIN submissoes s ON s.id = re.submissao_id
             LEFT JOIN equipes eq ON eq.id = s.equipe_id
             WHERE re.etapa_id = :etapa_id
             ORDER BY re.ne DESC, re.id ASC'
        );
        $stmt->execute(['etapa_id' => $etapaId]);

        return $stmt->fetchAll();
    }

    public function buscarPorSubmissaoEEtapa($submissaoId, $etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM resultados_etapa WHERE submissao_id = :submissao_id AND etapa_id = :etapa_id LIMIT 1'
        );
        $stmt->execute(['submissao_id' => $submissaoId, 'etapa_id' => $etapaId]);

        $resultado = $stmt->fetch();

        return $resultado !== false ? $resultado : null;
    }

    public function jaPublicado($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM resultados_etapa WHERE etapa_id = :etapa_id');
        $stmt->execute(['etapa_id' => $etapaId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function publicar($etapaId, array $linhas, $usuarioId)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $remover = $pdo->prepare('DELETE FROM resultados_etapa WHERE etapa_id = :etapa_id');
            $remover->execute(['etapa_id' => $etapaId]);

            $inserir = $pdo->prepare(
                'INSERT INTO resultados_etapa (submissao_id, etapa_id, ne, classificado, desempate_criterio, publicado_por)
                 VALUES (:submissao_id, :etapa_id, :ne, :classificado, :desempate_criterio, :publicado_por)'
            );

            foreach ($linhas as $linha) {
                if ($linha['ne'] === null) {
                    continue;
                }

                $inserir->execute([
                    'submissao_id' => $linha['submissao_id'],
                    'etapa_id' => $etapaId,
                    'ne' => $linha['ne'],
                    'classificado' => $linha['classificado'] ? 1 : 0,
                    // Fase 51: regra que decidiu o empate com a submissao de
                    // cima, congelada junto com o resultado publicado.
                    'desempate_criterio' => isset($linha['desempate_criterio']) ? $linha['desempate_criterio'] : null,
                    'publicado_por' => $usuarioId,
                ]);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        Auditoria::registrar('publicar', 'resultados_etapa', $etapaId, null, [
            'linhas' => $linhas,
            'publicado_por' => $usuarioId,
        ]);
    }

    public function reabrir($etapaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM resultados_etapa WHERE etapa_id = :etapa_id');
        $stmt->execute(['etapa_id' => $etapaId]);

        Auditoria::registrar('reabrir', 'resultados_etapa', $etapaId, null, null);
    }
}
