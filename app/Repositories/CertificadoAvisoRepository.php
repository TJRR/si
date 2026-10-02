<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Documentos de certificado ja avisados (migration 209), um por chave de
 * documento, no formato de evento_certificados.chave_unicidade.
 */
class CertificadoAvisoRepository
{
    /**
     * Chaves ja avisadas no evento, como chaves do array.
     */
    public function chavesAvisadas($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT chave_documento FROM evento_certificado_avisos WHERE evento_id = :evento_id');
        $stmt->execute(['evento_id' => (int) $eventoId]);

        $chaves = [];

        foreach ($stmt->fetchAll(\PDO::FETCH_COLUMN) as $chave) {
            $chaves[$chave] = true;
        }

        return $chaves;
    }

    /**
     * $documentos: lista de ['usuario_id' => n, 'chave' => '...']. INSERT
     * IGNORE: um documento ja registrado por outro clique simultaneo nao
     * volta a ser registrado.
     */
    public function registrar($eventoId, array $documentos, $usuarioId)
    {
        if ($documentos === []) {
            return;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT IGNORE INTO evento_certificado_avisos (evento_id, usuario_id, chave_documento)
             VALUES (:evento_id, :usuario_id, :chave_documento)'
        );

        foreach ($documentos as $documento) {
            $stmt->execute([
                'evento_id' => (int) $eventoId,
                'usuario_id' => (int) $documento['usuario_id'],
                'chave_documento' => (string) $documento['chave'],
            ]);
        }

        Auditoria::registrar('avisar_certificados', 'evento_certificado_avisos', null, null, [
            'evento_id' => (int) $eventoId,
            'documentos' => count($documentos),
        ], null, $usuarioId);
    }
}
