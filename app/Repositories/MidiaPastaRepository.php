<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 51: pastas da biblioteca de midia, com subpastas. Antes disso a
 * biblioteca era uma lista unica: com o volume de imagens da pagina de um
 * evento (quadros, faixas, blocos, cartoes, mapa) ela deixa de ser
 * navegavel. Pasta nula em `midias` significa raiz, entao nada do que ja
 * existe precisa ser movido para a estrutura nova funcionar.
 */
class MidiaPastaRepository
{
    public function listarFilhas($paiId = null)
    {
        $pdo = Database::conexao();

        if ($paiId === null) {
            $stmt = $pdo->query('SELECT * FROM midia_pastas WHERE pasta_pai_id IS NULL ORDER BY nome ASC');
        } else {
            $stmt = $pdo->prepare('SELECT * FROM midia_pastas WHERE pasta_pai_id = :pai ORDER BY nome ASC');
            $stmt->execute(['pai' => $paiId]);
        }

        return $stmt->fetchAll();
    }

    public function listarTodas()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM midia_pastas ORDER BY nome ASC')->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM midia_pastas WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $pasta = $stmt->fetch();

        return $pasta !== false ? $pasta : null;
    }

    /**
     * Caminho da raiz ate a pasta atual, para a navegacao no topo da tela.
     * O limite de profundidade evita laco infinito caso alguma pasta acabe
     * apontando para si mesma por dado corrompido.
     */
    public function caminho($id)
    {
        $caminho = [];
        $atual = $this->buscarPorId($id);
        $limite = 20;

        while ($atual !== null && $limite-- > 0) {
            array_unshift($caminho, $atual);
            $atual = $atual['pasta_pai_id'] !== null ? $this->buscarPorId((int) $atual['pasta_pai_id']) : null;
        }

        return $caminho;
    }

    public function criar($nome, $paiId, $usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('INSERT INTO midia_pastas (nome, pasta_pai_id, criado_por) VALUES (:nome, :pai, :criado_por)');
        $stmt->execute(['nome' => $nome, 'pai' => $paiId, 'criado_por' => $usuarioId]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'midia_pastas', $id, null, ['nome' => $nome, 'pasta_pai_id' => $paiId]);

        return $id;
    }

    public function renomear($id, $nome)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE midia_pastas SET nome = :nome WHERE id = :id');
        $stmt->execute(['nome' => $nome, 'id' => $id]);

        Auditoria::registrar('atualizar', 'midia_pastas', $id, $antes, ['nome' => $nome]);
    }

    /**
     * Pasta com conteudo dentro nunca some em silencio: quem apaga decide
     * antes o que fazer com o que esta la.
     */
    public function contarConteudo($id)
    {
        $pdo = Database::conexao();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM midia_pastas WHERE pasta_pai_id = :id');
        $stmt->execute(['id' => $id]);
        $subpastas = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT COUNT(*) FROM midias WHERE pasta_id = :id');
        $stmt->execute(['id' => $id]);
        $midias = (int) $stmt->fetchColumn();

        return ['subpastas' => $subpastas, 'midias' => $midias];
    }

    public function remover($id)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('DELETE FROM midia_pastas WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'midia_pastas', $id, $antes, null);
    }
}
