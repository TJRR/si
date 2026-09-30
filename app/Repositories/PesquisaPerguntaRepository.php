<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 57: perguntas da pesquisa de satisfacao, no molde de
 * EventoCampoInscricaoRepository (ordem nascendo como MAX + 1, reordenacao
 * por arrasto em transacao com evento_id no WHERE, que e' a correcao de
 * seguranca da Fase 50 e nao se perde na copia).
 *
 * Diferenca central em relacao ao formulario de inscricao: pergunta que ja'
 * tem resposta NAO e' apagada nem tem as opcoes editadas. A resposta guarda
 * a POSICAO da opcao (evento_pesquisa_respostas.valor_numero), entao mexer
 * na lista mudaria em silencio o significado do que ja' foi respondido.
 * "Remover" na tela vira desativar.
 */
class PesquisaPerguntaRepository
{
    public function listarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT p.*,
                    (SELECT COUNT(*) FROM evento_pesquisa_respostas r WHERE r.pergunta_id = p.id) AS total_respostas
               FROM evento_pesquisa_perguntas p
              WHERE p.evento_id = :evento_id
              ORDER BY p.ordem ASC, p.id ASC'
        );
        $stmt->execute(['evento_id' => $eventoId]);

        return $stmt->fetchAll();
    }

    /**
     * Perguntas ativas, na ordem cadastrada: e' o formulario que o
     * participante responde. Recurso opcional da tela do inscrito, entao
     * falha de banco devolve lista vazia e o botao some do painel.
     */
    public function listarAtivas($eventoId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT * FROM evento_pesquisa_perguntas
                  WHERE evento_id = :evento_id AND ativa = 1
                  ORDER BY ordem ASC, id ASC'
            );
            $stmt->execute(['evento_id' => $eventoId]);

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log('[Pesquisa] Falha ao listar as perguntas do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return [];
        }
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_pesquisa_perguntas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    public function temResposta($perguntaId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT pergunta_id FROM evento_pesquisa_respostas WHERE pergunta_id = :pergunta LIMIT 1');
        $stmt->execute(['pergunta' => (int) $perguntaId]);

        return $stmt->fetch() !== false;
    }

    public function criar($eventoId, array $dados)
    {
        $pdo = Database::conexao();
        $stmtOrdem = $pdo->prepare('SELECT COALESCE(MAX(ordem), 0) + 1 FROM evento_pesquisa_perguntas WHERE evento_id = :evento_id');
        $stmtOrdem->execute(['evento_id' => $eventoId]);
        $proximaOrdem = (int) $stmtOrdem->fetchColumn();

        $campos = [
            'evento_id' => $eventoId,
            'ordem' => $proximaOrdem,
            'enunciado' => $dados['enunciado'],
            'tipo' => $dados['tipo'],
            'obrigatoria' => $dados['obrigatoria'],
            'texto_ajuda' => $dados['texto_ajuda'],
            'config_json' => $dados['config'] !== null ? json_encode($dados['config']) : null,
            'ativa' => $dados['ativa'],
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO evento_pesquisa_perguntas (evento_id, ordem, enunciado, tipo, obrigatoria, texto_ajuda, config_json, ativa)
             VALUES (:evento_id, :ordem, :enunciado, :tipo, :obrigatoria, :texto_ajuda, :config_json, :ativa)'
        );
        $stmt->execute($campos);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'evento_pesquisa_perguntas', $id, null, $campos);

        return $id;
    }

    /**
     * Sem o tipo e sem as opcoes quando ja' ha' resposta: quem decide qual
     * dos dois caminhos seguir e' o controller, com temResposta(). Aqui o
     * conjunto de campos vem pronto.
     */
    public function atualizar($id, array $campos)
    {
        $antes = $this->buscarPorId($id);

        $partes = [];
        $parametros = ['id' => (int) $id];

        foreach ($campos as $coluna => $valor) {
            $partes[] = $coluna . ' = :' . $coluna;
            $parametros[$coluna] = $valor;
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE evento_pesquisa_perguntas SET ' . implode(', ', $partes) . ' WHERE id = :id');
        $stmt->execute($parametros);

        Auditoria::registrar('atualizar', 'evento_pesquisa_perguntas', (int) $id, $antes, $campos);
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM evento_pesquisa_perguntas WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'evento_pesquisa_perguntas', (int) $id, $antes, null);
    }

    public function reordenar($eventoId, array $ids)
    {
        $pdo = Database::conexao();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare('UPDATE evento_pesquisa_perguntas SET ordem = :ordem WHERE id = :id AND evento_id = :evento_id');

            foreach ($ids as $indice => $id) {
                $stmt->execute(['ordem' => $indice, 'id' => (int) $id, 'evento_id' => $eventoId]);
            }

            $pdo->commit();
            Auditoria::registrar('reordenar', 'evento_pesquisa_perguntas', null, null, ['evento_id' => $eventoId, 'ids' => $ids]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
