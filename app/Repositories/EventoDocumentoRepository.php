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
 * Reabertura da Fase 51: Documentos do Evento, copia da funcao de
 * Documentos do Concurso (DocumentoRepository) com evento_id no lugar de
 * concurso_id e sem trilha.
 *
 * Herda de EventoConteudoRepositorioBase o que ja' serve igual:
 * listar()/listarAtivos() (na base, ativo = 1 e' exatamente a "versao
 * vigente" de cada documento), buscarPorId() e reordenar(), todos filtrados
 * por evento_id. O que e' proprio de documento vem de DocumentoRepository:
 * criar() com versionamento (sobrescreve o da base), metadados, historico,
 * publicacao e remocao do grupo inteiro.
 */
class EventoDocumentoRepository extends EventoConteudoRepositorioBase
{
    public const TIPOS = ['edital', 'edital_simples', 'anexo', 'retificacao', 'resultado_final', 'ata'];

    public const ROTULOS_TIPO = [
        'edital' => 'Edital',
        'edital_simples' => 'Edital em linguagem simples',
        'anexo' => 'Anexo',
        'retificacao' => 'Retificação',
        'resultado_final' => 'Resultado final',
        'ata' => 'Ata',
    ];

    protected function tabela()
    {
        return 'evento_documentos';
    }

    protected function colunas()
    {
        return ['tipo', 'titulo', 'arquivo_path', 'grupo_documento', 'versao', 'ativo', 'publicado', 'criado_por'];
    }

    /**
     * Versoes vigentes publicadas, para a pagina publica e para a lista de
     * escolha do botao da secao de submissao.
     */
    public function listarPublicados($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM evento_documentos
             WHERE evento_id = :evento_id AND ativo = 1 AND publicado = 1
             ORDER BY ordem ASC, tipo ASC, titulo ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function listarVersoesPorGrupo($eventoId, $grupoDocumento)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM evento_documentos WHERE evento_id = :evento_id AND grupo_documento = :grupo ORDER BY versao DESC'
        );
        $stmt->execute(['evento_id' => $eventoId, 'grupo' => $grupoDocumento]);

        return $stmt->fetchAll();
    }

    private function buscarVersaoAtiva($eventoId, $grupoDocumento)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT * FROM evento_documentos WHERE evento_id = :evento_id AND grupo_documento = :grupo AND ativo = 1 LIMIT 1'
        );
        $stmt->execute(['evento_id' => $eventoId, 'grupo' => $grupoDocumento]);

        $documento = $stmt->fetch();

        return $documento !== false ? $documento : null;
    }

    /**
     * Versao vigente e publicada do mesmo documento de $documentoId. Quem
     * aponta para um documento (o botao da secao de submissao) guarda o id
     * da versao escolhida; quando sai uma retificacao, o botao passa a
     * abrir a versao nova sozinho, sem o Admin precisar escolher de novo.
     */
    public function buscarVigentePublicado($eventoId, $documentoId)
    {
        $documento = $this->buscarPorId($documentoId);

        if ($documento === null || (int) $documento['evento_id'] !== (int) $eventoId) {
            return null;
        }

        $vigente = $this->buscarVersaoAtiva($eventoId, $documento['grupo_documento']);

        return $vigente !== null && (int) $vigente['publicado'] === 1 ? $vigente : null;
    }

    /**
     * Cria uma nova versao (ou a primeira) do grupo tipo+titulo, com a mesma
     * regra do Concurso: a versao nova herda ordem e publicacao da anterior;
     * documento inedito entra publicado, no fim da lista.
     */
    public function criar($eventoId, array $dados)
    {
        $grupo = Texto::slugify($dados['tipo'] . '-' . $dados['titulo']);
        $versaoAtiva = $this->buscarVersaoAtiva($eventoId, $grupo);
        $novaVersao = $versaoAtiva !== null ? ((int) $versaoAtiva['versao'] + 1) : 1;

        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            if ($versaoAtiva !== null) {
                $pdo->prepare('UPDATE evento_documentos SET ativo = 0 WHERE id = :id AND evento_id = :evento_id')
                    ->execute(['id' => $versaoAtiva['id'], 'evento_id' => $eventoId]);
                $ordem = (int) $versaoAtiva['ordem'];
                $publicado = (int) $versaoAtiva['publicado'];
            } else {
                $ordemMaxima = $pdo->prepare('SELECT COALESCE(MAX(ordem), -1) + 1 FROM evento_documentos WHERE evento_id = :evento_id');
                $ordemMaxima->execute(['evento_id' => $eventoId]);
                $ordem = (int) $ordemMaxima->fetchColumn();
                $publicado = 1;
            }

            $registro = [
                'evento_id' => $eventoId,
                'tipo' => $dados['tipo'],
                'titulo' => $dados['titulo'],
                'arquivo_path' => $dados['arquivo_path'],
                'grupo_documento' => $grupo,
                'versao' => $novaVersao,
                'ativo' => 1,
                'ordem' => $ordem,
                'publicado' => $publicado,
                'criado_por' => $dados['criado_por'],
            ];

            $stmt = $pdo->prepare(
                'INSERT INTO evento_documentos (evento_id, tipo, titulo, arquivo_path, grupo_documento, versao, ativo, ordem, publicado, criado_por)
                 VALUES (:evento_id, :tipo, :titulo, :arquivo_path, :grupo_documento, :versao, :ativo, :ordem, :publicado, :criado_por)'
            );
            $stmt->execute($registro);
            $id = (int) $pdo->lastInsertId();

            $pdo->commit();
            Auditoria::registrar('criar', 'evento_documentos', $id, null, $registro);

            return $id;
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Corrige tipo e titulo de uma versao, sem gerar versao nova, e
     * recalcula o grupo desta linha (mesma regra de
     * DocumentoRepository::atualizarMetadados()).
     */
    public function atualizarMetadados($eventoId, $id, $tipo, $titulo)
    {
        $antes = $this->buscarPorId($id);

        if ($antes === null || (int) $antes['evento_id'] !== (int) $eventoId) {
            return;
        }

        $depois = [
            'tipo' => $tipo,
            'titulo' => $titulo,
            'grupo' => Texto::slugify($tipo . '-' . $titulo),
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_documentos SET tipo = :tipo, titulo = :titulo, grupo_documento = :grupo
             WHERE id = :id AND evento_id = :evento_id'
        );
        $stmt->execute($depois + ['id' => $id, 'evento_id' => $eventoId]);

        Auditoria::registrar('atualizar_metadados', 'evento_documentos', $id, $antes, $depois);
    }

    public function alterarPublicacao($eventoId, $id, $publicado)
    {
        $pdo = Database::conexao();
        $pdo->prepare('UPDATE evento_documentos SET publicado = :publicado WHERE id = :id AND evento_id = :evento_id')
            ->execute(['publicado' => $publicado ? 1 : 0, 'id' => $id, 'evento_id' => $eventoId]);

        Auditoria::registrar($publicado ? 'republicar' : 'despublicar', 'evento_documentos', $id, null, ['publicado' => $publicado ? 1 : 0]);
    }

    /**
     * Remove todas as versoes de um grupo e devolve as linhas removidas,
     * para quem chamou apagar os arquivos.
     */
    public function removerGrupo($eventoId, $grupoDocumento)
    {
        $versoes = $this->listarVersoesPorGrupo($eventoId, $grupoDocumento);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_documentos WHERE evento_id = :evento_id AND grupo_documento = :grupo');
        $stmt->execute(['evento_id' => $eventoId, 'grupo' => $grupoDocumento]);

        Auditoria::registrar('remover_grupo', 'evento_documentos', null, $versoes, null);

        return $versoes;
    }
}
