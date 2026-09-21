<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class UsuarioRepository
{
    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);

        $usuario = $stmt->fetch();

        return $usuario !== false ? $usuario : null;
    }

    /**
     * Fase 17 (Melhoria 2): usuarios elegiveis para "visualizar como" -
     * ativos, ja aprovados, e sem perfil administrador (decisao do usuario:
     * um Admin nao pode "virar" outro Admin).
     */
    public function listarAtivosNaoAdministradores()
    {
        $pdo = Database::conexao();
        $stmt = $pdo->query(
            "SELECT u.* FROM usuarios u
             WHERE u.status = 'aprovado' AND u.ativo = 1
               AND u.id NOT IN (
                   SELECT upc.usuario_id
                   FROM usuario_perfil_concurso upc
                   INNER JOIN perfis p ON p.id = upc.perfil_id
                   WHERE p.chave = 'administrador'
               )
             ORDER BY u.nome ASC"
        );

        return $stmt->fetchAll();
    }

    /**
     * Fase 48 (correcao pos-teste de fumaca): busca em tempo real por nome
     * OU e-mail - usado por AtividadeAdminController::buscarUsuarios() para
     * o campo de vinculo de facilitador (a pessoa que cadastra digita e
     * escolhe entre sugestoes, em vez de digitar o e-mail cru de memoria).
     * So' contas aprovadas - as demais nao conseguem logar, entao nao faz
     * sentido oferece-las aqui.
     */
    public function buscarPorTermo($termo, $limite = 10)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT id, nome, email FROM usuarios
             WHERE (nome LIKE :termo OR email LIKE :termo) AND status = 'aprovado'
             ORDER BY nome ASC
             LIMIT :limite"
        );
        $stmt->bindValue('termo', '%' . $termo . '%', \PDO::PARAM_STR);
        $stmt->bindValue('limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function buscarPorEmail($email)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        $usuario = $stmt->fetch();

        return $usuario !== false ? $usuario : null;
    }

    public function criar($nome, $email, $senhaHash)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nome, email, senha_hash, status) VALUES (:nome, :email, :senha_hash, 'pendente')"
        );
        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
            'senha_hash' => $senhaHash,
        ]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'usuarios', $id, null, ['nome' => $nome, 'email' => $email, 'status' => 'pendente']);

        return $id;
    }

    public function buscarPorGoogleId($googleId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM usuarios WHERE google_id = :google_id LIMIT 1');
        $stmt->execute(['google_id' => $googleId]);

        $usuario = $stmt->fetch();

        return $usuario !== false ? $usuario : null;
    }

    public function vincularGoogleId($id, $googleId)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET google_id = :google_id WHERE id = :id');
        $stmt->execute(['google_id' => $googleId, 'id' => $id]);

        Auditoria::registrar('vincular_google', 'usuarios', $id, $antes, ['google_id' => $googleId]);
    }

    public function criarAprovadoSemSenha($nome, $email)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nome, email, status) VALUES (:nome, :email, 'aprovado')"
        );
        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
        ]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'usuarios', $id, null, ['nome' => $nome, 'email' => $email, 'status' => 'aprovado']);

        return $id;
    }

    /**
     * Fase 40: cadastro auto-aprovado com senha propria (perfil "inscrito"
     * de evento) - mesma logica de criarAprovadoSemSenha(), mas com hash de
     * senha direto no INSERT (evita duas queries criar()+atualizarStatus()).
     */
    public function criarAprovado($nome, $email, $senhaHash)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nome, email, senha_hash, status) VALUES (:nome, :email, :senha_hash, 'aprovado')"
        );
        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
            'senha_hash' => $senhaHash,
        ]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'usuarios', $id, null, ['nome' => $nome, 'email' => $email, 'status' => 'aprovado']);

        return $id;
    }

    public function definirSenha($id, $senhaHash)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET senha_hash = :senha_hash WHERE id = :id');
        $stmt->execute(['senha_hash' => $senhaHash, 'id' => $id]);

        Auditoria::registrar('definir_senha', 'usuarios', $id, ['senha_hash' => $antes !== null ? '(hash anterior omitido)' : null], ['senha_hash' => '(hash omitido)']);
    }

    public function criarComGoogle($nome, $email, $googleId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "INSERT INTO usuarios (nome, email, google_id, status) VALUES (:nome, :email, :google_id, 'pendente')"
        );
        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
            'google_id' => $googleId,
        ]);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'usuarios', $id, null, ['nome' => $nome, 'email' => $email, 'status' => 'pendente']);

        return $id;
    }

    public function listarPendentes()
    {
        $pdo = Database::conexao();
        $stmt = $pdo->query("SELECT * FROM usuarios WHERE status = 'pendente' ORDER BY criado_em ASC");

        return $stmt->fetchAll();
    }

    public function listarTodos($concursoId = null)
    {
        $pdo = Database::conexao();

        if ($concursoId === null) {
            return $pdo->query('SELECT * FROM usuarios ORDER BY nome ASC')->fetchAll();
        }

        $stmt = $pdo->prepare(
            'SELECT DISTINCT u.*
             FROM usuarios u
             INNER JOIN usuario_perfil_concurso upc ON upc.usuario_id = u.id
             WHERE upc.concurso_id = :concurso_id
             ORDER BY u.nome ASC'
        );
        $stmt->execute(['concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    /**
     * Fase 40 (correcao pos-teste de fumaca): marca/desmarca o sinal de "esta
     * conta ja existia pendente e foi auto-aprovada de passagem pelo fluxo
     * do evento - revisar se tambem precisa de um perfil do Concurso". So'
     * setada em AuthService::resolverUsuarioGoogle() (conta pre-existente,
     * nunca em cadastro novo) e zerada em UsuarioAdminController quando o
     * Admin atribui/edita o perfil da conta.
     */
    public function definirPrecisaRevisarConcurso($id, $valor)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET precisa_revisar_concurso = :valor WHERE id = :id');
        $stmt->execute(['valor' => $valor ? 1 : 0, 'id' => $id]);
    }

    public function atualizarStatus($id, $status)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);

        Auditoria::registrar('atualizar_status', 'usuarios', $id, $antes, ['status' => $status]);
    }

    public function atualizarAtivo($id, $ativo)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET ativo = :ativo WHERE id = :id');
        $stmt->execute(['ativo' => $ativo ? 1 : 0, 'id' => $id]);

        Auditoria::registrar('atualizar_ativo', 'usuarios', $id, $antes, ['ativo' => $ativo ? 1 : 0]);
    }

    public function perfisDoUsuario($usuarioId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT upc.id AS vinculo_id, p.chave AS perfil, p.nome_exibicao AS perfil_nome,
                    upc.concurso_id AS concurso_id, c.nome AS concurso_nome
             FROM usuario_perfil_concurso upc
             INNER JOIN perfis p ON p.id = upc.perfil_id
             LEFT JOIN concursos c ON c.id = upc.concurso_id
             WHERE upc.usuario_id = :usuario_id'
        );
        $stmt->execute(['usuario_id' => $usuarioId]);

        return $stmt->fetchAll();
    }

    public function atualizarNome($id, $nome)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET nome = :nome WHERE id = :id');
        $stmt->execute(['nome' => $nome, 'id' => $id]);

        Auditoria::registrar('atualizar_nome', 'usuarios', $id, $antes, ['nome' => $nome]);
    }

    /**
     * Fase 26: so' usada pelo script database/alterar_email_usuario.php
     * (sem tela nenhuma, de proposito - decisao do usuario). Muda so' o
     * e-mail de LOGIN (usuarios.email) - nunca mexe no e-mail de inscricao
     * do participante (participantes.email), tabela separada.
     */
    public function atualizarEmail($id, $email)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET email = :email WHERE id = :id');
        $stmt->execute(['email' => $email, 'id' => $id]);

        Auditoria::registrar('atualizar_email', 'usuarios', $id, $antes, ['email' => $email]);
    }

    public function atualizarFoto($id, $caminhoRelativo)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET foto_path = :foto_path WHERE id = :id');
        $stmt->execute(['foto_path' => $caminhoRelativo, 'id' => $id]);

        Auditoria::registrar('atualizar_foto', 'usuarios', $id, $antes, ['foto_path' => $caminhoRelativo]);
    }

    /**
     * Fase 48B: $temaVisualId null volta o usuario para o tema padrao do
     * sistema (nenhuma escolha propria). Coluna "tema_visual_id" (nao
     * "tema_id"), de proposito: "tema" sozinho ja e' usado no dominio do
     * Premio de Inovacao (Trilha->Tema->Desafio) - ver TemaVisualRepository.
     */
    public function definirTemaVisual($id, $temaVisualId)
    {
        $antes = $this->buscarPorId($id);
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE usuarios SET tema_visual_id = :tema_visual_id WHERE id = :id');
        $stmt->execute(['tema_visual_id' => $temaVisualId, 'id' => $id]);

        Auditoria::registrar('selecionar_tema_visual', 'usuarios', $id, $antes, ['tema_visual_id' => $temaVisualId]);
    }
}
