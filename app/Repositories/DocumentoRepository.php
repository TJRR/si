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

    /**
     * Uso administrativo (tela de Documentos) - traz publicados e
     * despublicados, pra quem gerencia poder ver e reverter a qualquer um.
     */
    public function listarAtivosPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT d.*, u.nome AS criado_por_nome
             FROM documentos d
             LEFT JOIN usuarios u ON u.id = d.criado_por
             WHERE d.concurso_id = :concurso_id AND d.ativo = 1
             ORDER BY d.ordem ASC, d.tipo ASC, d.titulo ASC'
        );
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    /**
     * Fase 29 (Bug 3): uso publico (home) - so' os que o Admin decidiu
     * manter visiveis. Mesma ordem definida no drag-and-drop da tela
     * administrativa.
     */
    public function listarPublicadosPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT d.*, u.nome AS criado_por_nome
             FROM documentos d
             LEFT JOIN usuarios u ON u.id = d.criado_por
             WHERE d.concurso_id = :concurso_id AND d.ativo = 1 AND d.publicado = 1
             ORDER BY d.ordem ASC, d.tipo ASC, d.titulo ASC'
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

            // Fase 29 (Bug 3): uma nova versao do MESMO documento herda
            // ordem/publicado da versao anterior - senao subir uma
            // retificacao republicaria e jogaria pro fim da lista um
            // documento que o Admin tinha despublicado/reposicionado de
            // proposito. So' documento realmente novo (grupo inedito) entra
            // publicado, no fim da lista.
            if ($versaoAtiva !== null) {
                $ordem = (int) $versaoAtiva['ordem'];
                $publicado = (int) $versaoAtiva['publicado'];
            } else {
                $ordemMaxima = $pdo->prepare('SELECT MAX(ordem) FROM documentos WHERE concurso_id = :concurso_id');
                $ordemMaxima->execute(['concurso_id' => $concursoId]);
                $ordem = ((int) $ordemMaxima->fetchColumn()) + 1;
                $publicado = 1;
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
                'ordem' => $ordem,
                'publicado' => $publicado,
                'criado_por' => $criadoPor,
            ];

            $stmt = $pdo->prepare(
                'INSERT INTO documentos (concurso_id, trilha_id, tipo, titulo, arquivo_path, grupo_documento, versao, ativo, ordem, publicado, criado_por)
                 VALUES (:concurso_id, :trilha_id, :tipo, :titulo, :arquivo_path, :grupo_documento, :versao, :ativo, :ordem, :publicado, :criado_por)'
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
     * Fase 19: edicao pontual de metadados (tipo/trilha/titulo) de UMA
     * versao especifica, sem tocar em arquivo_path/versao nem nas outras
     * linhas do grupo - ao contrario de criar(), nao gera versao nova. So'
     * recalcula o grupo_documento desta linha, para continuar coerente se
     * um upload novo for feito depois com os dados corrigidos.
     */
    public function atualizarMetadados($id, $trilhaId, $tipo, $titulo)
    {
        $antes = $this->buscarPorId($id);

        if ($antes === null) {
            return;
        }

        $novoGrupo = Texto::slugify($tipo . '-' . $titulo);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE documentos SET trilha_id = :trilha_id, tipo = :tipo, titulo = :titulo, grupo_documento = :grupo
             WHERE id = :id'
        );
        $depois = [
            'trilha_id' => $trilhaId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'grupo' => $novoGrupo,
        ];
        $stmt->execute($depois + ['id' => $id]);

        Auditoria::registrar('atualizar_metadados', 'documentos', $id, $antes, $depois);
    }

    /**
     * Fase 29 (Bug 3): tira o documento da home sem apagar o arquivo nem o
     * historico - alternativa a removerGrupo() pra quando o motivo e' so'
     * "nao mostrar mais por enquanto", nao "foi cadastrado por engano".
     */
    public function despublicar($id)
    {
        $pdo = Database::conexao();
        $pdo->prepare('UPDATE documentos SET publicado = 0 WHERE id = :id')->execute(['id' => $id]);

        Auditoria::registrar('despublicar', 'documentos', $id, null, ['publicado' => 0]);
    }

    public function republicar($id)
    {
        $pdo = Database::conexao();
        $pdo->prepare('UPDATE documentos SET publicado = 1 WHERE id = :id')->execute(['id' => $id]);

        Auditoria::registrar('republicar', 'documentos', $id, null, ['publicado' => 1]);
    }

    /**
     * Fase 29 (Bug 3): ordem manual via drag-and-drop na tela de Documentos,
     * refletida na home - mesmo padrao ja usado em SlideRepository/
     * PremioRepository etc. (assets/js/reordenar-arrastar.js).
     */
    public function reordenar(array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE documentos SET ordem = :ordem WHERE id = :id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'documentos', null, null, ['ids' => $ids]);
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
