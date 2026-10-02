<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 58: configuracao da Gamificacao do evento (migration 185), no molde
 * de BonusConfigRepository. A linha nasce no primeiro salvamento da tela
 * Gamificacao, Configuracoes; evento sem linha equivale ao padrao abaixo,
 * resolvido aqui e nunca semeado em migration.
 */
class GamificacaoConfigRepository
{
    const PADRAO = [
        'ativo' => 0,
        'classificacao_visivel' => 0,
        'classificacao_quantidade' => 0,
        'classificacao_mostrar_nomes' => 0,
        'minutos_pontualidade' => null,
        'texto_regras_html' => null,
        'encerramento_em' => null,
        'encerramento_definido_por' => null,
    ];

    public function buscarPorEvento($eventoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM evento_gamificacao_config WHERE evento_id = :evento_id LIMIT 1');
        $stmt->execute(['evento_id' => (int) $eventoId]);

        $linha = $stmt->fetch();

        return $linha !== false ? $linha : null;
    }

    /**
     * Leitura protegida, para as telas do participante: falha de banco
     * (tabela ainda nao criada, por exemplo) devolve o padrao, com o modulo
     * desligado, em vez de derrubar o painel de todo inscrito.
     */
    public function vigente($eventoId)
    {
        try {
            $linha = $this->buscarPorEvento($eventoId);
        } catch (\PDOException $e) {
            error_log('[Gamificacao] Falha ao ler a configuracao do evento ' . (int) $eventoId . ': ' . $e->getMessage());

            return self::PADRAO;
        }

        if ($linha === null) {
            return self::PADRAO;
        }

        return [
            'ativo' => (int) $linha['ativo'],
            'classificacao_visivel' => (int) $linha['classificacao_visivel'],
            'classificacao_quantidade' => (int) $linha['classificacao_quantidade'],
            'classificacao_mostrar_nomes' => (int) $linha['classificacao_mostrar_nomes'],
            'minutos_pontualidade' => $linha['minutos_pontualidade'] !== null ? (int) $linha['minutos_pontualidade'] : null,
            'texto_regras_html' => $linha['texto_regras_html'],
            'encerramento_em' => $linha['encerramento_em'],
            'encerramento_definido_por' => $linha['encerramento_definido_por'] !== null ? (int) $linha['encerramento_definido_por'] : null,
        ];
    }

    /**
     * Grava as opcoes da tela. NAO toca no encerramento, que tem gravacao
     * propria (definirEncerramento) com as travas dele.
     */
    public function salvar($eventoId, array $dados)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $campos = [
            'evento_id' => (int) $eventoId,
            'ativo' => !empty($dados['ativo']) ? 1 : 0,
            'classificacao_visivel' => !empty($dados['classificacao_visivel']) ? 1 : 0,
            'classificacao_quantidade' => (int) $dados['classificacao_quantidade'],
            'classificacao_mostrar_nomes' => !empty($dados['classificacao_mostrar_nomes']) ? 1 : 0,
            'minutos_pontualidade' => $dados['minutos_pontualidade'] !== null ? (int) $dados['minutos_pontualidade'] : null,
            'texto_regras_html' => $dados['texto_regras_html'],
        ];

        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO evento_gamificacao_config
                 (evento_id, ativo, classificacao_visivel, classificacao_quantidade, classificacao_mostrar_nomes,
                  minutos_pontualidade, texto_regras_html)
             VALUES (:evento_id, :ativo, :classificacao_visivel, :classificacao_quantidade, :classificacao_mostrar_nomes,
                     :minutos_pontualidade, :texto_regras_html)
             ON DUPLICATE KEY UPDATE
                 ativo = VALUES(ativo),
                 classificacao_visivel = VALUES(classificacao_visivel),
                 classificacao_quantidade = VALUES(classificacao_quantidade),
                 classificacao_mostrar_nomes = VALUES(classificacao_mostrar_nomes),
                 minutos_pontualidade = VALUES(minutos_pontualidade),
                 texto_regras_html = VALUES(texto_regras_html)'
        );
        $stmt->execute($campos);

        Auditoria::registrar('salvar', 'evento_gamificacao_config', (int) $eventoId, $antes, $campos);
    }

    /**
     * Grava ou retira o instante do encerramento. A condicao do UPDATE e' a
     * propria trava: so' altera enquanto o encerramento gravado estiver
     * vazio ou no futuro, entao duas abas abertas ou um envio atrasado nunca
     * mexem num encerramento que ja' aconteceu. Devolve false quando a trava
     * impediu.
     */
    public function definirEncerramento($eventoId, $instante, $usuarioId)
    {
        $antes = $this->buscarPorEvento($eventoId);
        $pdo = Database::conexao();

        if ($antes === null) {
            $stmt = $pdo->prepare(
                'INSERT INTO evento_gamificacao_config (evento_id, encerramento_em, encerramento_definido_por)
                 VALUES (:evento_id, :instante, :usuario)'
            );
            $stmt->execute([
                'evento_id' => (int) $eventoId,
                'instante' => $instante,
                'usuario' => $instante !== null ? (int) $usuarioId : null,
            ]);
        } else {
            // Sem estas duas conferencias, o rowCount() zero do UPDATE
            // abaixo seria ambiguo: o MySQL tambem devolve zero quando o
            // valor gravado e' igual ao que ja' estava.
            if ($antes['encerramento_em'] !== null && strtotime($antes['encerramento_em']) <= time()) {
                return false;
            }

            if ($antes['encerramento_em'] === $instante) {
                return true;
            }

            $stmt = $pdo->prepare(
                'UPDATE evento_gamificacao_config
                    SET encerramento_em = :instante, encerramento_definido_por = :usuario
                  WHERE evento_id = :evento_id
                    AND (encerramento_em IS NULL OR encerramento_em > NOW())'
            );
            $stmt->execute([
                'evento_id' => (int) $eventoId,
                'instante' => $instante,
                'usuario' => $instante !== null ? (int) $usuarioId : null,
            ]);

            if ($stmt->rowCount() === 0) {
                return false;
            }
        }

        Auditoria::registrar(
            $instante !== null ? 'definir_encerramento_gincana' : 'retirar_encerramento_gincana',
            'evento_gamificacao_config',
            (int) $eventoId,
            $antes !== null ? ['encerramento_em' => $antes['encerramento_em']] : null,
            ['encerramento_em' => $instante, 'definido_por' => (int) $usuarioId]
        );

        return true;
    }
}
