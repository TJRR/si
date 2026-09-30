<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 57: a chave geral do modulo Bonus no evento, no molde de
 * ConexaoConfigRepository (Fase 55). Aqui so' mora o liga e desliga: o que
 * vale em cada evento esta' no catalogo (BonusRepository), porque bonus e'
 * entidade cadastravel, nao regra fixa em codigo.
 *
 * A linha nasce no primeiro salvamento da tela Bonus, Configuracoes; evento
 * sem linha equivale a modulo desligado, resolvido aqui e nunca semeado em
 * migration.
 */
class BonusConfigRepository
{
    const PADRAO = [
        'ativo' => 0,
    ];

    public function buscarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_bonus_config WHERE evento_id = :evento_id LIMIT 1');
        $stmt->execute(['evento_id' => $eventoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Recurso opcional da tela do participante: falha de banco (tabela ainda
     * nao criada, por exemplo) devolve o padrao desligado em vez de derrubar
     * o painel de todo inscrito.
     */
    public function vigente($eventoId)
    {
        try {
            $linha = $this->buscarPorEvento($eventoId);
        } catch (\PDOException $e) {
            error_log('[Bonus] Falha ao ler a configuracao do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return self::PADRAO;
        }

        if ($linha === null) {
            return self::PADRAO;
        }

        return [
            'ativo' => (int) $linha['ativo'],
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
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_bonus_config (evento_id, ativo)
             VALUES (:evento_id, :ativo)
             ON DUPLICATE KEY UPDATE ativo = VALUES(ativo)'
        );
        $stmt->execute($campos);

        Auditoria::registrar('salvar', 'evento_bonus_config', (int) $eventoId, $antes, $campos);
    }
}
