<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class ResultadoTrilhaRepository
{
    public function listarPorTrilha($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT rt.*, e.nome_equipe
             FROM resultados_trilha rt
             INNER JOIN equipes e ON e.id = rt.equipe_id
             WHERE rt.trilha_id = :trilha_id
             ORDER BY rt.colocacao ASC'
        );
        $stmt->execute(['trilha_id' => $trilhaId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT rt.*, e.nome_equipe FROM resultados_trilha rt INNER JOIN equipes e ON e.id = rt.equipe_id WHERE rt.id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Fase 18 (4.7) - resumo/imagem de destaque do case vencedor, editado
     * pelo admin separadamente do calculo/publicacao do ranking.
     */
    public function atualizarDestaque($id, $resumoDestaque, $imagemDestaquePath, $imagemDestaqueAlt)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $dados = [
            'resumo_destaque' => $resumoDestaque,
            'imagem_destaque_path' => $imagemDestaquePath,
            'imagem_destaque_alt' => $imagemDestaqueAlt,
        ];
        $stmt = $pdo->prepare(
            'UPDATE resultados_trilha SET resumo_destaque = :resumo_destaque,
             imagem_destaque_path = :imagem_destaque_path, imagem_destaque_alt = :imagem_destaque_alt WHERE id = :id'
        );
        $stmt->execute($dados + ['id' => $id]);

        Auditoria::registrar('atualizar_destaque', 'resultados_trilha', $id, $antes, $dados);
    }

    public function jaPublicado($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM resultados_trilha WHERE trilha_id = :trilha_id');
        $stmt->execute(['trilha_id' => $trilhaId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function publicar($trilhaId, array $linhas, $usuarioId)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            // Fase 18: preserva o resumo/imagem de destaque (editado a parte
            // pelo admin) ao recalcular/republicar - sem isso, toda vez que
            // a apuracao fosse refeita o destaque do case vencedor se
            // perderia, ja que o publicar() sempre apaga e reinsere as
            // linhas. Chave estavel = equipe_id (colocacao/nf podem mudar
            // entre publicacoes, a equipe nao).
            $stmtDestaque = $pdo->prepare(
                'SELECT equipe_id, resumo_destaque, imagem_destaque_path, imagem_destaque_alt
                 FROM resultados_trilha WHERE trilha_id = :trilha_id'
            );
            $stmtDestaque->execute(['trilha_id' => $trilhaId]);
            $destaquePorEquipe = [];

            foreach ($stmtDestaque->fetchAll() as $linhaAntiga) {
                $destaquePorEquipe[(int) $linhaAntiga['equipe_id']] = $linhaAntiga;
            }

            $remover = $pdo->prepare('DELETE FROM resultados_trilha WHERE trilha_id = :trilha_id');
            $remover->execute(['trilha_id' => $trilhaId]);

            $inserir = $pdo->prepare(
                'INSERT INTO resultados_trilha (
                    equipe_id, trilha_id, nf, colocacao, publicado_por,
                    resumo_destaque, imagem_destaque_path, imagem_destaque_alt
                ) VALUES (
                    :equipe_id, :trilha_id, :nf, :colocacao, :publicado_por,
                    :resumo_destaque, :imagem_destaque_path, :imagem_destaque_alt
                )'
            );

            foreach ($linhas as $linha) {
                $antigo = isset($destaquePorEquipe[(int) $linha['equipe_id']]) ? $destaquePorEquipe[(int) $linha['equipe_id']] : null;

                $inserir->execute([
                    'equipe_id' => $linha['equipe_id'],
                    'trilha_id' => $trilhaId,
                    'nf' => $linha['nf'],
                    'colocacao' => $linha['colocacao'],
                    'publicado_por' => $usuarioId,
                    'resumo_destaque' => $antigo !== null ? $antigo['resumo_destaque'] : null,
                    'imagem_destaque_path' => $antigo !== null ? $antigo['imagem_destaque_path'] : null,
                    'imagem_destaque_alt' => $antigo !== null ? $antigo['imagem_destaque_alt'] : null,
                ]);
            }

            $pdo->commit();
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }

        Auditoria::registrar('publicar', 'resultados_trilha', $trilhaId, null, [
            'linhas' => $linhas,
            'publicado_por' => $usuarioId,
        ]);
    }

    public function reabrir($trilhaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM resultados_trilha WHERE trilha_id = :trilha_id');
        $stmt->execute(['trilha_id' => $trilhaId]);

        Auditoria::registrar('reabrir', 'resultados_trilha', $trilhaId, null, null);
    }
}
