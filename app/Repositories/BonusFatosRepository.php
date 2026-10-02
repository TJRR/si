<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Database;

/**
 * Fase 58: os fatos que os tipos novos de bonus apuram (acoes do
 * participante): perfil preenchido, credenciamento no local e autoria de
 * trabalho submetido. So' LE; nada aqui grava.
 *
 * Repositorio novo, e nao metodos em TrabalhoAutorRepository ou
 * UsuarioPerfilRepository, pelo precedente de isolamento: os dois sao
 * usados tambem pelo "Meu Perfil" do Concurso (MeuPerfilController).
 *
 * Cada fato tem duas formas: a de uma pessoa (leitura de codigo, abertura
 * do painel) e a do evento inteiro, em lote, para a reconferencia.
 */
class BonusFatosRepository
{
    /**
     * Campos do perfil que o tipo perfil_campos sabe conferir. A ordem aqui
     * e' a ordem em que a lista e' gravada em evento_bonus.campos_perfil.
     */
    const CAMPOS_PERFIL = ['foto', 'cargo', 'orgao_origem', 'categoria_profissional', 'minicurriculo', 'telefone', 'redes'];

    private $perfis;

    public function __construct()
    {
        $this->perfis = new UsuarioPerfilRepository();
    }

    /**
     * Perfil de uma pessoa, ja' reduzido aos campos preenchidos. Recurso da
     * tela do participante: falha de banco devolve lista vazia.
     */
    public function camposPreenchidosDoUsuario($usuarioId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT u.foto_path, p.cargo, p.orgao_origem, p.categoria_profissional, p.minicurriculo, p.telefone, p.redes_sociais
                   FROM usuarios u
                   LEFT JOIN usuarios_perfil p ON p.usuario_id = u.id
                  WHERE u.id = :usuario LIMIT 1'
            );
            $stmt->execute(['usuario' => (int) $usuarioId]);
            $linha = $stmt->fetch();
        } catch (\PDOException $e) {
            error_log('[Bonus] Falha ao ler o perfil do usuario ' . (int) $usuarioId . ': ' . $e->getMessage());

            return [];
        }

        return $linha !== false ? $this->camposPreenchidos($linha) : [];
    }

    public function estaCredenciado($inscricaoId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare('SELECT 1 FROM evento_credenciamentos WHERE evento_inscricao_id = :inscricao LIMIT 1');
            $stmt->execute(['inscricao' => (int) $inscricaoId]);

            return $stmt->fetch() !== false;
        } catch (\PDOException $e) {
            error_log('[Bonus] Falha ao ler o credenciamento da inscricao ' . (int) $inscricaoId . ': ' . $e->getMessage());

            return false;
        }
    }

    /**
     * A pessoa e' autora (principal ou coautora com conta) de algum trabalho
     * do evento que nao esta' desclassificado. Vale para trabalho submetido
     * pelo sistema e importado do canal alternativo, porque os dois caminhos
     * gravam trabalho_autores.usuario_id.
     */
    public function ehAutorDeTrabalhoValido($eventoId, $usuarioId)
    {
        try {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                "SELECT 1 FROM trabalho_autores ta
                   INNER JOIN trabalhos t ON t.id = ta.trabalho_id
                  WHERE ta.usuario_id = :usuario AND t.evento_id = :evento AND t.status <> 'desclassificado'
                  LIMIT 1"
            );
            $stmt->execute(['usuario' => (int) $usuarioId, 'evento' => (int) $eventoId]);

            return $stmt->fetch() !== false;
        } catch (\PDOException $e) {
            error_log('[Bonus] Falha ao ler a autoria do usuario ' . (int) $usuarioId . ': ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Todas as inscricoes do evento com os campos de perfil preenchidos, na
     * forma [evento_inscricao_id => ['usuario_id' => n, 'campos' => [...]]].
     * Uma consulta so', para a reconferencia.
     */
    public function inscricoesComPerfilDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT i.id AS inscricao_id, i.usuario_id, u.foto_path, p.cargo, p.orgao_origem, p.categoria_profissional,
                    p.minicurriculo, p.telefone, p.redes_sociais
               FROM evento_inscricoes i
               INNER JOIN usuarios u ON u.id = i.usuario_id
               LEFT JOIN usuarios_perfil p ON p.usuario_id = u.id
              WHERE i.evento_id = :evento'
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        $mapa = [];

        foreach ($stmt->fetchAll() as $linha) {
            $mapa[(int) $linha['inscricao_id']] = [
                'usuario_id' => (int) $linha['usuario_id'],
                'campos' => $this->camposPreenchidos($linha),
            ];
        }

        return $mapa;
    }

    /**
     * Inscricoes credenciadas no local, na forma [evento_inscricao_id => true].
     */
    public function credenciadosDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT evento_inscricao_id FROM evento_credenciamentos WHERE evento_id = :evento');
        $stmt->execute(['evento' => (int) $eventoId]);

        $mapa = [];

        foreach ($stmt->fetchAll() as $linha) {
            $mapa[(int) $linha['evento_inscricao_id']] = true;
        }

        return $mapa;
    }

    /**
     * Usuarios autores de trabalho valido do evento, na forma
     * [usuario_id => true].
     */
    public function autoresValidosDoEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            "SELECT DISTINCT ta.usuario_id
               FROM trabalho_autores ta
               INNER JOIN trabalhos t ON t.id = ta.trabalho_id
              WHERE t.evento_id = :evento AND t.status <> 'desclassificado' AND ta.usuario_id IS NOT NULL"
        );
        $stmt->execute(['evento' => (int) $eventoId]);

        $mapa = [];

        foreach ($stmt->fetchAll() as $linha) {
            $mapa[(int) $linha['usuario_id']] = true;
        }

        return $mapa;
    }

    /**
     * Inscricoes, no evento do trabalho, de todos os autores com conta desse
     * trabalho - para reapurar os autores depois de uma desclassificacao ou
     * de uma submissao.
     */
    public function inscricoesDosAutoresDoTrabalho($trabalhoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT DISTINCT i.id AS inscricao_id, i.usuario_id
               FROM trabalho_autores ta
               INNER JOIN trabalhos t ON t.id = ta.trabalho_id
               INNER JOIN evento_inscricoes i ON i.usuario_id = ta.usuario_id AND i.evento_id = t.evento_id
              WHERE ta.trabalho_id = :trabalho'
        );
        $stmt->execute(['trabalho' => (int) $trabalhoId]);

        return $stmt->fetchAll();
    }

    /**
     * Reduz a linha de usuarios + usuarios_perfil aos campos preenchidos.
     * "redes" conta so' as redes reconhecidas do mapa, lidas por
     * UsuarioPerfilRepository::redesSociais(), a
     * mesma leitura usada por todas as telas.
     */
    private function camposPreenchidos(array $linha)
    {
        $campos = [];

        if (!empty($linha['foto_path'])) {
            $campos[] = 'foto';
        }

        foreach (['cargo', 'orgao_origem', 'categoria_profissional', 'minicurriculo', 'telefone'] as $campo) {
            if (isset($linha[$campo]) && trim((string) $linha[$campo]) !== '') {
                $campos[] = $campo;
            }
        }

        if (!empty($linha['redes_sociais']) && $this->perfis->redesSociais($linha) !== []) {
            $campos[] = 'redes';
        }

        return $campos;
    }
}
