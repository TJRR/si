<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class ParticipanteRepository
{
    public function contarTodos()
    {
        $pdo = Database::conexao();

        return (int) $pdo->query('SELECT COUNT(*) FROM participantes')->fetchColumn();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM participantes WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $participante = $stmt->fetch();

        return $participante !== false ? $participante : null;
    }

    public function buscarPorCpf($cpf)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM participantes WHERE cpf = :cpf LIMIT 1');
        $stmt->execute(['cpf' => $cpf]);

        $participante = $stmt->fetch();

        return $participante !== false ? $participante : null;
    }

    public function buscarPorEmail($email)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM participantes WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        $participante = $stmt->fetch();

        return $participante !== false ? $participante : null;
    }

    /**
     * Nome nao e' unico entre participantes - devolve todos os que baterem,
     * pra quem chamar decidir (ex.: script de setup de teste que pede pra
     * escolher pelo id se houver mais de um resultado).
     */
    public function buscarTodosPorNome($nome)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM participantes WHERE nome = :nome');
        $stmt->execute(['nome' => $nome]);

        return $stmt->fetchAll();
    }

    public function criar($nome, $cpf, $email, $telefone, $vinculoProfissao)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO participantes (nome, cpf, email, telefone, vinculo_profissao)
             VALUES (:nome, :cpf, :email, :telefone, :vinculo_profissao)'
        );
        $dados = [
            'nome' => $nome,
            'cpf' => $cpf !== '' ? $cpf : null,
            'email' => $email !== '' ? $email : null,
            'telefone' => $telefone !== '' ? $telefone : null,
            'vinculo_profissao' => $vinculoProfissao !== '' ? $vinculoProfissao : null,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'participantes', $id, null, $dados);

        return $id;
    }

    public function atualizarCpf($id, $cpf)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE participantes SET cpf = :cpf WHERE id = :id');
        $stmt->execute(['cpf' => $cpf, 'id' => $id]);

        Auditoria::registrar('atualizar_cpf', 'participantes', $id, $antes, ['cpf' => $cpf]);
    }

    /**
     * So' existe para o script de setup de ambiente de teste
     * (database/definir_email_teste.php) - nao ha' nenhuma tela (nem admin,
     * nem autoedicao) que altere o e-mail de um participante, de proposito
     * (e' o identificador do vinculo com a conta de login).
     */
    public function atualizarEmail($id, $email)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE participantes SET email = :email WHERE id = :id');
        $stmt->execute(['email' => $email, 'id' => $id]);

        Auditoria::registrar('atualizar_email_teste', 'participantes', $id, $antes, ['email' => $email]);
    }

    public function atualizarDados($id, $nome, $telefone, $cpf)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE participantes SET nome = :nome, telefone = :telefone, cpf = :cpf WHERE id = :id'
        );
        $depois = [
            'nome' => $nome,
            'telefone' => $telefone !== '' ? $telefone : null,
            'cpf' => $cpf !== '' ? $cpf : null,
        ];
        $stmt->execute($depois + ['id' => $id]);

        Auditoria::registrar('atualizar_dados', 'participantes', $id, $antes, $depois);
    }
}
