<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

class ConfiguracaoVisualRepository
{
    public function buscar()
    {
        $pdo = Database::conexao();

        return $pdo->query('SELECT * FROM configuracoes_visuais WHERE id = 1')->fetch();
    }

    public function atualizar($corPrimariaInicio, $corPrimariaFim, $corSecundaria)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'UPDATE configuracoes_visuais
             SET cor_primaria_inicio = :inicio, cor_primaria_fim = :fim, cor_secundaria = :secundaria
             WHERE id = 1'
        );
        $depois = [
            'cor_primaria_inicio' => $corPrimariaInicio,
            'cor_primaria_fim' => $corPrimariaFim,
            'cor_secundaria' => $corSecundaria,
        ];
        $stmt->execute(['inicio' => $corPrimariaInicio, 'fim' => $corPrimariaFim, 'secundaria' => $corSecundaria]);

        Auditoria::registrar('atualizar', 'configuracoes_visuais', 1, $antes, $depois);
    }

    /**
     * Fase 18 (4.9) - override de identidade visual por concurso (logo +
     * cores). concurso_id NULL continua sendo o "id=1" global/default,
     * usado como fallback por toda pagina que nao tem um concurso no
     * contexto (login, admin). A home publica usa o override do concurso
     * ativo quando existir, ou o global se a edicao nao tiver um.
     */
    public function buscarPorConcurso($concursoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM configuracoes_visuais WHERE concurso_id = :concurso_id LIMIT 1');
        $stmt->execute(['concurso_id' => $concursoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Resolve a config visual efetiva de um concurso: o override dele, se
     * existir, senao a global.
     */
    public function buscarEfetivaPorConcurso($concursoId)
    {
        return $this->buscarPorConcurso($concursoId) ?: $this->buscar();
    }

    public function salvarParaConcurso($concursoId, $corPrimariaInicio, $corPrimariaFim, $corSecundaria, $logoPath)
    {
        $antes = $this->buscarPorConcurso($concursoId);
        $pdo = Database::conexao();

        $dados = [
            'concurso_id' => $concursoId,
            'cor_primaria_inicio' => $corPrimariaInicio,
            'cor_primaria_fim' => $corPrimariaFim,
            'cor_secundaria' => $corSecundaria,
            'logo_path' => $logoPath,
        ];

        $stmt = $pdo->prepare(
            'INSERT INTO configuracoes_visuais (concurso_id, cor_primaria_inicio, cor_primaria_fim, cor_secundaria, logo_path)
             VALUES (:concurso_id, :cor_primaria_inicio, :cor_primaria_fim, :cor_secundaria, :logo_path)
             ON DUPLICATE KEY UPDATE
                cor_primaria_inicio = VALUES(cor_primaria_inicio),
                cor_primaria_fim = VALUES(cor_primaria_fim),
                cor_secundaria = VALUES(cor_secundaria),
                logo_path = VALUES(logo_path)'
        );
        $stmt->execute($dados);

        Auditoria::registrar('salvar_concurso', 'configuracoes_visuais', (int) $concursoId, $antes, $dados);
    }

    public function atualizarLogo($caminhoRelativo)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE configuracoes_visuais SET logo_path = :logo_path WHERE id = 1');
        $stmt->execute(['logo_path' => $caminhoRelativo]);

        Auditoria::registrar('atualizar_logo', 'configuracoes_visuais', 1, $antes, ['logo_path' => $caminhoRelativo]);
    }

    public function atualizarFavicon($caminhoRelativo)
    {
        $antes = $this->buscar();
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('UPDATE configuracoes_visuais SET favicon_path = :favicon_path WHERE id = 1');
        $stmt->execute(['favicon_path' => $caminhoRelativo]);

        Auditoria::registrar('atualizar_favicon', 'configuracoes_visuais', 1, $antes, ['favicon_path' => $caminhoRelativo]);
    }
}
