<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 55: configuracao de Conexoes por evento, no molde de
 * EstandeConfigRepository (Fase 54). A linha nasce no primeiro salvamento da
 * tela Conexoes, Configuracoes; evento sem linha equivale a modulo
 * desligado, resolvido aqui e nunca semeado em migration.
 */
class ConexaoConfigRepository
{
    /**
     * Configuracao de um evento que ainda nao teve a tela salva: modulo
     * desligado, sem pontos e sem teto.
     */
    const PADRAO = [
        'ativo' => 0,
        'pontos_por_conexao' => 0,
        'teto_conexoes_pontuadas' => 0,
    ];

    public function buscarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_conexoes_config WHERE evento_id = :evento_id LIMIT 1');
        $stmt->execute(['evento_id' => $eventoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Configuracao normalizada, sempre com as tres chaves e sempre em
     * numero inteiro. Recurso opcional da tela do participante: falha de
     * banco (tabela ainda nao criada, por exemplo) devolve o padrao
     * desligado em vez de derrubar o painel de todo inscrito.
     */
    public function vigente($eventoId)
    {
        try {
            $linha = $this->buscarPorEvento($eventoId);
        } catch (\PDOException $e) {
            error_log('[Conexoes] Falha ao ler a configuracao do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return self::PADRAO;
        }

        if ($linha === null) {
            return self::PADRAO;
        }

        return [
            'ativo' => (int) $linha['ativo'],
            'pontos_por_conexao' => (int) $linha['pontos_por_conexao'],
            'teto_conexoes_pontuadas' => (int) $linha['teto_conexoes_pontuadas'],
        ];
    }

    public function estaAtivo($eventoId)
    {
        $config = $this->vigente($eventoId);

        return $config['ativo'] === 1;
    }

    public function salvar($eventoId, array $dados)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $campos = [
            'evento_id' => $eventoId,
            'ativo' => !empty($dados['ativo']) ? 1 : 0,
            'pontos_por_conexao' => (int) $dados['pontos_por_conexao'],
            'teto_conexoes_pontuadas' => (int) $dados['teto_conexoes_pontuadas'],
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_conexoes_config (evento_id, ativo, pontos_por_conexao, teto_conexoes_pontuadas)
             VALUES (:evento_id, :ativo, :pontos_por_conexao, :teto_conexoes_pontuadas)
             ON DUPLICATE KEY UPDATE ativo = VALUES(ativo), pontos_por_conexao = VALUES(pontos_por_conexao),
                 teto_conexoes_pontuadas = VALUES(teto_conexoes_pontuadas)'
        );
        $stmt->execute($campos);

        Auditoria::registrar('salvar', 'evento_conexoes_config', (int) $eventoId, $antes, $campos);
    }
}
