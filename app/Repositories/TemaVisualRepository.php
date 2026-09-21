<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 48B: gestao de multiplos temas DE COR, valendo para o sistema
 * inteiro. Nome "Visual" no repositorio e na tabela (temas_visuais), de
 * proposito, para nao colidir com a classe/tabela "temas" ja existente no
 * dominio do Premio de Inovacao (hierarquia Trilha->Tema->Desafio, Fase 17,
 * ver TemaRepository) - sao dois conceitos completamente diferentes que so'
 * compartilham o nome em portugues. Substitui a configuracao unica de cor
 * que existia em ConfiguracaoVisualRepository (as 5 colunas de cor saem de
 * configuracoes_visuais na migration 145). cor_primaria_fim nunca e' pedida
 * ao Admin - e' sempre calculada a partir de cor_primaria_inicio (ver
 * clarear()), para o formulario de tema ter so' 4 campos.
 */
class TemaVisualRepository
{
    public function listarTodos()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM temas_visuais ORDER BY editavel ASC, nome ASC')->fetchAll();
    }

    public function listarPublicados()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM temas_visuais WHERE publicado = 1 ORDER BY editavel ASC, nome ASC')->fetchAll();
    }

    public function buscarPorId($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM temas_visuais WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $tema = $stmt->fetch();

        return $tema !== false ? $tema : null;
    }

    public function buscarPadrao()
    {
        $pdo = Database::conexao();
        $tema = $pdo->query('SELECT * FROM temas_visuais WHERE padrao = 1 LIMIT 1')->fetch();

        return $tema !== false ? $tema : null;
    }

    /**
     * Tema efetivamente aplicado: o do usuario (se logado, com tema_visual_id
     * definido e o tema ainda publicado) ou o padrao do sistema.
     * $usuarioId = null (visitante nao autenticado) sempre cai no padrao.
     */
    public function resolverAtivo($usuarioId)
    {
        if ($usuarioId !== null) {
            $pdo = Database::conexao();
            $stmt = $pdo->prepare(
                'SELECT t.* FROM temas_visuais t
                 INNER JOIN usuarios u ON u.tema_visual_id = t.id
                 WHERE u.id = :usuario_id AND t.publicado = 1'
            );
            $stmt->execute(['usuario_id' => $usuarioId]);
            $tema = $stmt->fetch();

            if ($tema !== false) {
                return $tema;
            }
        }

        return $this->buscarPadrao();
    }

    public function contarUsuarios($id)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM usuarios WHERE tema_visual_id = :id');
        $stmt->execute(['id' => $id]);

        return (int) $stmt->fetchColumn();
    }

    public function criar($nome, $corPrimariaInicio, $corSecundaria, $corTerciaria, $corDestaqueApp, $logoConcursoPath = null, $logoEventoPath = null)
    {
        $this->validarCores([$corPrimariaInicio, $corSecundaria, $corTerciaria, $corDestaqueApp]);

        $corPrimariaFim = $this->clarear($corPrimariaInicio, 0.33);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO temas_visuais (nome, cor_primaria_inicio, cor_primaria_fim, cor_secundaria, cor_terciaria, cor_destaque_app, logo_concurso_path, logo_evento_path, padrao, editavel, publicado)
             VALUES (:nome, :inicio, :fim, :secundaria, :terciaria, :destaque_app, :logo_concurso, :logo_evento, 0, 1, 0)'
        );
        $dados = [
            'nome' => $nome,
            'inicio' => $corPrimariaInicio,
            'fim' => $corPrimariaFim,
            'secundaria' => $corSecundaria,
            'terciaria' => $corTerciaria,
            'destaque_app' => $corDestaqueApp,
            'logo_concurso' => $logoConcursoPath,
            'logo_evento' => $logoEventoPath,
        ];
        $stmt->execute($dados);
        $id = (int) $pdo->lastInsertId();

        Auditoria::registrar('criar', 'temas_visuais', $id, null, $dados);

        return $id;
    }

    public function atualizar($id, $nome, $corPrimariaInicio, $corSecundaria, $corTerciaria, $corDestaqueApp, $logoConcursoPath = null, $logoEventoPath = null)
    {
        $antes = $this->buscarPorId($id);
        if ($antes === null || (int) $antes['editavel'] === 0) {
            throw new \RuntimeException('Este tema não pode ser editado.');
        }

        $this->validarCores([$corPrimariaInicio, $corSecundaria, $corTerciaria, $corDestaqueApp]);

        $corPrimariaFim = $this->clarear($corPrimariaInicio, 0.33);

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE temas_visuais
             SET nome = :nome, cor_primaria_inicio = :inicio, cor_primaria_fim = :fim,
                 cor_secundaria = :secundaria, cor_terciaria = :terciaria, cor_destaque_app = :destaque_app,
                 logo_concurso_path = :logo_concurso, logo_evento_path = :logo_evento
             WHERE id = :id'
        );
        $depois = [
            'nome' => $nome,
            'inicio' => $corPrimariaInicio,
            'fim' => $corPrimariaFim,
            'secundaria' => $corSecundaria,
            'terciaria' => $corTerciaria,
            'destaque_app' => $corDestaqueApp,
            'logo_concurso' => $logoConcursoPath,
            'logo_evento' => $logoEventoPath,
        ];
        $stmt->execute(array_merge($depois, ['id' => $id]));

        Auditoria::registrar('atualizar', 'temas_visuais', $id, $antes, $depois);
    }

    public function duplicar($id, $novoNome)
    {
        $origem = $this->buscarPorId($id);
        if ($origem === null) {
            throw new \RuntimeException('Tema não encontrado.');
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO temas_visuais (nome, cor_primaria_inicio, cor_primaria_fim, cor_secundaria, cor_terciaria, cor_destaque_app, logo_concurso_path, logo_evento_path, padrao, editavel, publicado)
             VALUES (:nome, :inicio, :fim, :secundaria, :terciaria, :destaque_app, :logo_concurso, :logo_evento, 0, 1, 0)'
        );
        $dados = [
            'nome' => $novoNome,
            'inicio' => $origem['cor_primaria_inicio'],
            'fim' => $origem['cor_primaria_fim'],
            'secundaria' => $origem['cor_secundaria'],
            'terciaria' => $origem['cor_terciaria'],
            'destaque_app' => $origem['cor_destaque_app'],
            'logo_concurso' => $origem['logo_concurso_path'],
            'logo_evento' => $origem['logo_evento_path'],
        ];
        $stmt->execute($dados);
        $novoId = (int) $pdo->lastInsertId();

        Auditoria::registrar('duplicar', 'temas_visuais', $novoId, ['origem_id' => $id], $dados);

        return $novoId;
    }

    public function publicar($id)
    {
        $this->alternarPublicacao($id, 1, 'publicar');
    }

    public function despublicar($id)
    {
        $this->alternarPublicacao($id, 0, 'despublicar');
    }

    private function alternarPublicacao($id, $publicado, $acao)
    {
        $antes = $this->buscarPorId($id);
        if ($antes === null) {
            throw new \RuntimeException('Tema não encontrado.');
        }

        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE temas_visuais SET publicado = :publicado WHERE id = :id');
        $stmt->execute(['publicado' => $publicado, 'id' => $id]);

        Auditoria::registrar($acao, 'temas_visuais', $id, $antes, ['publicado' => $publicado]);
    }

    public function definirPadrao($id)
    {
        $tema = $this->buscarPorId($id);
        if ($tema === null || (int) $tema['publicado'] === 0) {
            throw new \RuntimeException('Só é possível definir como padrão um tema publicado.');
        }

        $pdo = Database::conexao();
        $pdo->beginTransaction();
        $pdo->exec('UPDATE temas_visuais SET padrao = 0 WHERE padrao = 1');
        $stmt = $pdo->prepare('UPDATE temas_visuais SET padrao = 1 WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $pdo->commit();

        Auditoria::registrar('definir_padrao', 'temas_visuais', $id, null, ['padrao' => 1]);
    }

    public function remover($id)
    {
        $tema = $this->buscarPorId($id);
        if ($tema === null) {
            throw new \RuntimeException('Tema não encontrado.');
        }
        if ((int) $tema['editavel'] === 0) {
            throw new \RuntimeException('Este é um dos temas de sistema (não customizado) e não pode ser removido.');
        }
        if ((int) $tema['padrao'] === 1) {
            throw new \RuntimeException('Defina outro tema como padrão antes de remover este.');
        }

        $pdo = Database::conexao();

        $stmtReverter = $pdo->prepare('UPDATE usuarios SET tema_visual_id = NULL WHERE tema_visual_id = :id');
        $stmtReverter->execute(['id' => $id]);

        $stmt = $pdo->prepare('DELETE FROM temas_visuais WHERE id = :id');
        $stmt->execute(['id' => $id]);

        Auditoria::registrar('remover', 'temas_visuais', $id, $tema, null);
    }

    /**
     * Clareamento de $hex em direcao ao branco, na proporcao informada
     * (0 a 1) - mesmo espirito de corContrastante() (app/helpers.php):
     * calculo automatico de uma cor a partir de outra ja existente no
     * sistema. Usada so' para derivar cor_primaria_fim de cor_primaria_inicio.
     */
    public function clarear($hex, $proporcao)
    {
        $hex = ltrim((string) $hex, '#');
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = (int) round($r + $proporcao * (255 - $r));
        $g = (int) round($g + $proporcao * (255 - $g));
        $b = (int) round($b + $proporcao * (255 - $b));

        return sprintf('#%02X%02X%02X', $r, $g, $b);
    }

    public function validarHex($cor)
    {
        return is_string($cor) && preg_match('/^#[0-9A-Fa-f]{6}$/', $cor) === 1;
    }

    private function validarCores(array $cores)
    {
        foreach ($cores as $cor) {
            if (!$this->validarHex($cor)) {
                throw new \InvalidArgumentException('Cor inválida: ' . (string) $cor);
            }
        }
    }
}
