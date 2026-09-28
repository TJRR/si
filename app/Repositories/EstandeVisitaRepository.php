<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 54: visita registrada pela leitura do codigo do estande no
 * aplicativo. Mesmo desenho de EventoCheckinRepository: uma linha por
 * estande e por inscricao no evento, com a data e hora da leitura.
 * pontos_creditados guarda o valor do estande no momento da visita: se o
 * Administrador mudar a pontuacao depois, o que ja foi creditado nao muda.
 */
class EstandeVisitaRepository
{
    public function buscarPorEstandeEInscricao($estandeId, $inscricaoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_estande_visitas WHERE estande_id = :estande_id AND evento_inscricao_id = :inscricao_id LIMIT 1');
        $stmt->execute(['estande_id' => $estandeId, 'inscricao_id' => $inscricaoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Nao duplica: se a visita ja existe (inclusive por dois toques quase
     * juntos, barrados pela chave unica), devolve a que ja existia.
     */
    public function registrar($estandeId, $inscricaoId, $pontos)
    {
        $existente = $this->buscarPorEstandeEInscricao($estandeId, $inscricaoId);

        if ($existente !== null) {
            return $existente;
        }

        $pdo = Database::conexao();

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_estande_visitas (estande_id, evento_inscricao_id, pontos_creditados, visitado_em)
                 VALUES (:estande_id, :inscricao_id, :pontos, NOW())'
            );
            $stmt->execute(['estande_id' => $estandeId, 'inscricao_id' => $inscricaoId, 'pontos' => (int) $pontos]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return $this->buscarPorEstandeEInscricao($estandeId, $inscricaoId);
            }

            throw $e;
        }

        $id = (int) $pdo->lastInsertId();
        $registro = $this->buscarPorEstandeEInscricao($estandeId, $inscricaoId);

        Auditoria::registrar('registrar_visita_estande', 'evento_estande_visitas', $id, null, [
            'estande_id' => $estandeId,
            'evento_inscricao_id' => $inscricaoId,
            'pontos_creditados' => (int) $pontos,
            'visitado_em' => $registro['visitado_em'],
        ]);

        return $registro;
    }

    public function contarVisitas($estandeId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM evento_estande_visitas WHERE estande_id = :estande_id');
        $stmt->execute(['estande_id' => $estandeId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Estandes ja visitados pelo participante e o total de pontos creditados.
     * A lista sai da propria tabela de visitas (inclusive de estande
     * desativado depois), para o total sempre bater com os itens mostrados.
     * Recurso opcional da tela do participante: falha de banco devolve
     * "nenhuma visita" em vez de derrubar a tela.
     */
    public function resumoParticipante($eventoInscricaoId)
    {
        $vazio = ['visitas' => [], 'total_pontos' => 0];

        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT v.estande_id, v.pontos_creditados, v.visitado_em, e.nome, e.categoria, e.logotipo_path, e.logotipo_alt
                 FROM evento_estande_visitas v
                 JOIN evento_estandes e ON e.id = v.estande_id
                 WHERE v.evento_inscricao_id = :inscricao_id
                 ORDER BY v.visitado_em ASC, v.id ASC'
            );
            $stmt->execute(['inscricao_id' => $eventoInscricaoId]);
            $visitas = $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Estandes] Falha ao montar o resumo de visitas da inscricao ' . (int) $eventoInscricaoId . ': ' . $e->getMessage());

            return $vazio;
        }

        $total = 0;

        foreach ($visitas as $visita) {
            $total += (int) $visita['pontos_creditados'];
        }

        return ['visitas' => $visitas, 'total_pontos' => $total];
    }
}
