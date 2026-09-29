<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class PerfilRepository
{
    /**
     * Fase 55 (pendencia 24): perfis que NASCEM por fluxo proprio e nunca
     * devem ser atribuidos a mao pela tela Usuarios.
     *
     * 'representante_estande' vem do convite feito na tela de Estandes e
     * depende de uma linha em evento_estande_representantes; atribuido a
     * mao, a pessoa fica com o perfil sem estande nenhum. 'inscrito' nasce
     * do cadastro em evento (AuthService) e da submissao de trabalho;
     * atribuido a mao, a conta fica "inscrita" sem inscricao em evento
     * nenhum.
     *
     * A lista vale para o servidor, nao so' para a tela: convidar, aprovar
     * e editar recusam estas chaves mesmo que o envio venha forjado.
     *
     * O perfil 'participante' tem o mesmo defeito (nasce com vinculo em
     * usuario_participante pela homologacao de equipe), mas tem uso legitimo
     * conhecido pela tela Usuarios e ficou de fora por decisao do dono - e' a
     * pendencia 26.
     */
    const PERFIS_NAO_ATRIBUIVEIS = ['representante_estande', 'inscrito'];

    public function listar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM perfis ORDER BY nome_exibicao ASC')->fetchAll();
    }

    /**
     * Fase 55: lista para os campos de ESCOLHA de perfil da tela Usuarios
     * (convidar, aprovar e editar). O listar() continua completo e e' o que
     * alimenta o filtro da listagem, para o Administrador continuar podendo
     * filtrar por um perfil que ele nao pode atribuir.
     */
    public function listarParaAtribuicao()
    {
        $perfis = [];

        foreach ($this->listar() as $perfil) {
            if (!in_array($perfil['chave'], self::PERFIS_NAO_ATRIBUIVEIS, true)) {
                $perfis[] = $perfil;
            }
        }

        return $perfis;
    }

    /**
     * Fase 55: conferencia de servidor das telas de escrita.
     */
    public static function ehAtribuivelPelaTelaUsuarios($chave)
    {
        return !in_array($chave, self::PERFIS_NAO_ATRIBUIVEIS, true);
    }

    public function buscarPorChave($chave)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM perfis WHERE chave = :chave LIMIT 1');
        $stmt->execute(['chave' => $chave]);

        $perfil = $stmt->fetch();

        return $perfil !== false ? $perfil : null;
    }

    public function atribuir($usuarioId, $perfilId, $concursoId = null)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO usuario_perfil_concurso (usuario_id, perfil_id, concurso_id) VALUES (:usuario_id, :perfil_id, :concurso_id)'
        );
        $stmt->execute([
            'usuario_id' => $usuarioId,
            'perfil_id' => $perfilId,
            'concurso_id' => $concursoId,
        ]);

        Auditoria::registrar('atribuir', 'usuario_perfil_concurso', $usuarioId, null, [
            'perfil_id' => $perfilId,
            'concurso_id' => $concursoId,
        ]);
    }

    /**
     * Substitui o(s) vinculo(s) de perfil do usuario por um unico novo -
     * regra do projeto: um usuario tem no maximo 1 perfil no sistema. Usada
     * pela tela "Editar usuario" (Admin), que oferece so um select de perfil,
     * nao uma lista de multiplos vinculos.
     */
    public function substituirPerfil($usuarioId, $perfilId, $concursoId = null)
    {
        $pdo = Database::conexao();

        $stmtAntes = $pdo->prepare('SELECT * FROM usuario_perfil_concurso WHERE usuario_id = :usuario_id');
        $stmtAntes->execute(['usuario_id' => $usuarioId]);
        $antes = $stmtAntes->fetchAll();

        $stmt = $pdo->prepare('DELETE FROM usuario_perfil_concurso WHERE usuario_id = :usuario_id');
        $stmt->execute(['usuario_id' => $usuarioId]);

        if (!empty($antes)) {
            Auditoria::registrar('substituir_perfil', 'usuario_perfil_concurso', $usuarioId, $antes, null);
        }

        $this->atribuir($usuarioId, $perfilId, $concursoId);
    }

    public function possuiPerfil($usuarioId, $perfilId, $concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM usuario_perfil_concurso
             WHERE usuario_id = :usuario_id AND perfil_id = :perfil_id
               AND (concurso_id <=> :concurso_id)'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'perfil_id' => $perfilId, 'concurso_id' => $concursoId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Contagem global (todos os concursos) de usuarios distintos com um
     * dado perfil - usado no card "Avaliadores" do painel administrativo.
     */
    public function contarDistintosPorPerfil($perfilChave)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT COUNT(DISTINCT upc.usuario_id)
             FROM usuario_perfil_concurso upc
             INNER JOIN perfis p ON p.id = upc.perfil_id
             WHERE p.chave = :chave'
        );
        $stmt->execute(['chave' => $perfilChave]);

        return (int) $stmt->fetchColumn();
    }

    public function listarUsuariosPorPerfilConcurso($perfilChave, $concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT DISTINCT u.*
             FROM usuarios u
             INNER JOIN usuario_perfil_concurso upc ON upc.usuario_id = u.id
             INNER JOIN perfis p ON p.id = upc.perfil_id
             WHERE p.chave = :chave AND (upc.concurso_id IS NULL OR upc.concurso_id = :concurso_id)
             ORDER BY u.nome ASC'
        );
        $stmt->execute(['chave' => $perfilChave, 'concurso_id' => $concursoId]);

        return $stmt->fetchAll();
    }

    /**
     * Fase 35 (Parte C): so' quem tem o perfil com vinculo GLOBAL
     * (concurso_id NULL). Diferente de listarUsuariosPorPerfilConcurso(),
     * que tambem traz quem esta escopado ao concurso informado.
     *
     * Usado pela aba Segurança para avisar os demais administradores
     * globais - que sao exatamente os que podem entrar la'. Avisar
     * administrador escopado seria ruido: ele nao tem acesso a essa tela e
     * nao pode fazer nada com a informacao.
     */
    public function listarUsuariosGlobaisPorPerfil($perfilChave)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT DISTINCT u.*
             FROM usuarios u
             INNER JOIN usuario_perfil_concurso upc ON upc.usuario_id = u.id
             INNER JOIN perfis p ON p.id = upc.perfil_id
             WHERE p.chave = :chave AND upc.concurso_id IS NULL
             ORDER BY u.nome ASC'
        );
        $stmt->execute(['chave' => $perfilChave]);

        return $stmt->fetchAll();
    }

    /**
     * Fase 29 (Tira-Duvidas): inverso de listarUsuariosPorPerfilConcurso() -
     * a que concurso(s) este usuario tem acesso, num dado perfil. Retorna
     * null quando o usuario tem vinculo GLOBAL (concurso_id NULL) - nesse
     * caso quem chamar deve tratar como "sem filtro, ve tudo"; caso
     * contrario devolve a lista de concurso_id escopados.
     */
    public function concursosDoUsuario($usuarioId, $perfilChave)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT upc.concurso_id
             FROM usuario_perfil_concurso upc
             INNER JOIN perfis p ON p.id = upc.perfil_id
             WHERE upc.usuario_id = :usuario_id AND p.chave = :chave'
        );
        $stmt->execute(['usuario_id' => $usuarioId, 'chave' => $perfilChave]);
        $concursoIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        if (in_array(null, $concursoIds, true)) {
            return null;
        }

        return array_map('intval', $concursoIds);
    }
}
