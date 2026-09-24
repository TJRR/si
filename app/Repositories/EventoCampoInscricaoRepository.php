<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 39 (correcao pos-teste): campos configuraveis do formulario de
 * inscricao de um evento - "Documento" e' estrutural e nunca passa por
 * aqui (sempre presente, fora da configuracao do Admin). Tabela paralela
 * a campos_dinamicos (motor do concurso), ver comentario da migration 121
 * para o porque de nao reaproveitar aquela tabela.
 */
class EventoCampoInscricaoRepository
{
    /**
     * Reabertura da Fase 51 (achado da equipe de Teste Cego): rotulos dos dois
     * campos que dizem que documento a pessoa tem. O codigo encontra o campo
     * configuravel "tipo" pelo texto do rotulo, entao o texto mora aqui,
     * numa constante so', usada pelo controller de inscricao, pelas telas e
     * pela inscricao automatica de autores - nunca repetido a mao. O seed do
     * evento 1 (migration 121) usa o mesmo texto.
     */
    public const ROTULO_TIPO_DOCUMENTO = 'Tipo de Documento de Identificação';
    public const ROTULO_NUMERO_DOCUMENTO = 'Número do Documento de Identificação';

    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_campos_inscricao WHERE evento_id = :evento_id ORDER BY ordem ASC, id ASC');
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_campos_inscricao WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $campo = $stmt->fetch();

        return $campo !== false ? $campo : null;
    }

    public function criar($eventoId, array $dados)
    {
        $pdo = Database::conexao();
        $stmtOrdem = $pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM evento_campos_inscricao WHERE evento_id = :evento_id');
        $stmtOrdem->execute(['evento_id' => $eventoId]);
        $proximaOrdem = (int) $stmtOrdem->fetchColumn();

        $campos = [
            'evento_id' => $eventoId,
            'ordem' => $proximaOrdem,
            'rotulo' => $dados['rotulo'],
            'tipo' => $dados['tipo'],
            'obrigatorio' => $dados['obrigatorio'],
            'texto_ajuda' => $dados['texto_ajuda'],
            'config_json' => $dados['config'] !== null ? json_encode($dados['config']) : null,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO evento_campos_inscricao (evento_id, ordem, rotulo, tipo, obrigatorio, texto_ajuda, config_json)
             VALUES (:evento_id, :ordem, :rotulo, :tipo, :obrigatorio, :texto_ajuda, :config_json)'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'evento_campos_inscricao', $id, null, $campos);

        return $id;
    }

    public function atualizar($id, array $dados)
    {
        $antes = $this->buscarPorId($id);
        $campos = [
            'rotulo' => $dados['rotulo'],
            'tipo' => $dados['tipo'],
            'obrigatorio' => $dados['obrigatorio'],
            'texto_ajuda' => $dados['texto_ajuda'],
            'config_json' => $dados['config'] !== null ? json_encode($dados['config']) : null,
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE evento_campos_inscricao
                SET rotulo = :rotulo, tipo = :tipo, obrigatorio = :obrigatorio,
                    texto_ajuda = :texto_ajuda, config_json = :config_json
              WHERE id = :id'
        );
        $stmt->execute($campos + ['id' => $id]);

        Auditoria::registrar('atualizar', 'evento_campos_inscricao', $id, $antes, $campos);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_campos_inscricao WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'evento_campos_inscricao', $id, $antes, null);
    }

    /**
     * Fase 50 (achado de seguranca): WHERE inclui evento_id, nao so' id -
     * sem isso, um id de campo de OUTRO evento seria aceito e teria sua
     * ordem alterada, sem checagem de posse.
     */
    public function reordenar($eventoId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE evento_campos_inscricao SET ordem = :ordem WHERE id = :id AND evento_id = :evento_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'evento_id' => $eventoId]);
            }

            $pdo->commit();
            // Fase 50: mover() nunca tinha auditoria (unico dos 5 repositories
            // "swap com vizinho" sem isso) - reordenar() ja passa a ter, igual
            // ao padrao dos demais, nao e' regressao, e' correcao da lacuna.
            Auditoria::registrar('reordenar', 'evento_campos_inscricao', null, null, ['evento_id' => $eventoId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
