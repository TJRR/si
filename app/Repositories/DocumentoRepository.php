<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;
use App\Core\Texto;

/**
 * Fase 18 (4.6) - documentos/editais por edicao, com historico de versoes
 * simples: um novo upload com o mesmo tipo+titulo (mesmo grupo_documento,
 * derivado automaticamente via slug) vira uma nova versao, nunca sobrescreve
 * o arquivo anterior. So' a versao mais recente de cada grupo fica ativo=1
 * (a que aparece em destaque na listagem publica); as antigas continuam
 * acessiveis via listarVersoesPorGrupo().
 */
class DocumentoRepository
{
    public const TIPOS = ['edital', 'edital_simples', 'anexo', 'retificacao', 'resultado_final', 'ata'];

    public function listarAtivosPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM documentos WHERE concurso_id = :concurso_id AND ativo = 1 ORDER BY tipo ASC, titulo ASC'
        );
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    public function listarVersoesPorGrupo($concursoId, $grupoDocumento)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM documentos WHERE concurso_id = :concurso_id AND grupo_documento = :grupo ORDER BY versao DESC'
        );
        $stmt->execute(['concurso_id' => $concursoId, 'grupo' => $grupoDocumento]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM documentos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $documento = $stmt->fetch();

        return $documento !== false ? $documento : null;
    }

    private function buscarVersaoAtiva($concursoId, $grupoDocumento)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM documentos WHERE concurso_id = :concurso_id AND grupo_documento = :grupo AND ativo = 1 LIMIT 1'
        );
        $stmt->execute(['concurso_id' => $concursoId, 'grupo' => $grupoDocumento]);

        $documento = $stmt->fetch();

        return $documento !== false ? $documento : null;
    }

    /**
     * Cria uma nova versao (ou a primeira) do grupo tipo+titulo. Retorna o
     * id da linha criada.
     */
    public function criar($concursoId, $trilhaId, $tipo, $titulo, $arquivoPath, $criadoPor)
    {
        $grupo = Texto::slugify($tipo . '-' . $titulo);
        $versaoAtiva = $this->buscarVersaoAtiva($concursoId, $grupo);
        $novaVersao = $versaoAtiva !== null ? ((int) $versaoAtiva['versao'] + 1) : 1;

        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            if ($versaoAtiva !== null) {
                $pdo->prepare('UPDATE documentos SET ativo = 0 WHERE id = :id')->execute(['id' => $versaoAtiva['id']]);
            }

            $dados = [
                'concurso_id' => $concursoId,
                'trilha_id' => $trilhaId,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'arquivo_path' => $arquivoPath,
                'grupo_documento' => $grupo,
                'versao' => $novaVersao,
                'ativo' => 1,
                'criado_por' => $criadoPor,
            ];

            $stmt = $pdo->prepare(
                'INSERT INTO documentos (concurso_id, trilha_id, tipo, titulo, arquivo_path, grupo_documento, versao, ativo, criado_por)
                 VALUES (:concurso_id, :trilha_id, :tipo, :titulo, :arquivo_path, :grupo_documento, :versao, :ativo, :criado_por)'
            );
            $stmt->execute($dados);
            $id = (int) $pdo->lastInsertId();

            $pdo->commit();
            Auditoria::registrar('criar', 'documentos', $id, null, $dados);

            return $id;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Remove TODAS as versoes de um grupo (uso raro - ex.: documento
     * cadastrado por engano). Nao existe remocao de uma unica versao para
     * nao deixar o historico com buracos.
     */
    public function removerGrupo($concursoId, $grupoDocumento)
    {
        $versoes = $this->listarVersoesPorGrupo($concursoId, $grupoDocumento);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM documentos WHERE concurso_id = :concurso_id AND grupo_documento = :grupo');
        $stmt->execute(['concurso_id' => $concursoId, 'grupo' => $grupoDocumento]);

        Auditoria::registrar('remover_grupo', 'documentos', null, $versoes, null);

        return $versoes;
    }
}
