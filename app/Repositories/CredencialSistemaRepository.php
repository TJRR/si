<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Auth;
use App\Core\Cifra;
use App\Core\Database;

/**
 * Credenciais de integracao guardadas no banco (migration 115), com
 * reserva no config/local.php enquanto a transferencia nao for confirmada.
 * Ver Implantar.md, secao 13.6.
 */
class CredencialSistemaRepository
{
    /**
     * Grupo => campos cifrados. Ver Implantar.md, secao 13.6.
     */
    private static $sigilosos = [
        'google_service_account' => ['private_key'],
        'google_oauth' => ['client_secret'],
        'smtp' => ['pass'],
    ];

    /**
     * Valores prontos para uso. Grupo nao transferido devolve array vazio, e
     * campo que nao decifra e' omitido: nos dois casos, quem chama cai na
     * reserva do config/local.php.
     */
    public function obterGrupo($grupo)
    {
        $valores = [];

        foreach ($this->linhasDoGrupo($grupo) as $linha) {
            if ((int) $linha['sigiloso'] !== 1) {
                $valores[$linha['chave']] = (string) $linha['valor'];
                continue;
            }

            $claro = Cifra::decifrar($linha['valor']);

            if ($claro !== null) {
                $valores[$linha['chave']] = $claro;
            }
        }

        return $valores;
    }

    /**
     * O que a tela de Seguranca mostra: valores nao sigilosos em texto
     * normal, sigilosos reduzidos a impressao digital, mais quem alterou e
     * quando. NUNCA devolve o valor de um campo sigiloso - nem para a
     * camada de visao, para nao existir caminho pelo qual ele chegue ao
     * HTML por engano.
     */
    public function obterGrupoParaTela($grupo)
    {
        $itens = [];

        foreach ($this->linhasDoGrupo($grupo) as $linha) {
            $sigiloso = (int) $linha['sigiloso'] === 1;
            $claro = $sigiloso ? Cifra::decifrar($linha['valor']) : (string) $linha['valor'];

            $itens[$linha['chave']] = [
                'sigiloso' => $sigiloso,
                'valor' => $sigiloso ? null : $claro,
                'impressao_digital' => $sigiloso ? Cifra::impressaoDigital($claro) : null,
                'ilegivel' => $sigiloso && !empty($linha['valor']) && $claro === null,
                'preenchido' => !empty($linha['valor']),
                'atualizado_por_nome' => $linha['atualizado_por_nome'],
                'atualizado_em' => $linha['atualizado_em'],
            ];
        }

        return $itens;
    }

    /**
     * Grava o grupo inteiro. Campo sigiloso com valor vazio e' IGNORADO, e
     * nao gravado como vazio: a tela sempre entrega o campo de segredo em
     * branco (nunca devolve o valor atual), entao vazio significa "mantenha
     * o que ja esta la'", nunca "apague". Para apagar de verdade existe
     * remover().
     *
     * $usuarioId explicito para o roteiro de linha de comando, que grava
     * sem sessao aberta.
     */
    public function salvarGrupo($grupo, array $valores, $usuarioId = null)
    {
        $pdo = Database::conexao();
        $sigilosos = isset(self::$sigilosos[$grupo]) ? self::$sigilosos[$grupo] : [];
        $autor = $usuarioId !== null ? $usuarioId : Auth::usuarioId();
        $alterados = [];

        $stmt = $pdo->prepare(
            'INSERT INTO credenciais_sistema (grupo, chave, valor, sigiloso, atualizado_por)
             VALUES (:grupo, :chave, :valor, :sigiloso, :atualizado_por)
             ON DUPLICATE KEY UPDATE valor = VALUES(valor), sigiloso = VALUES(sigiloso),
                                     atualizado_por = VALUES(atualizado_por)'
        );

        foreach ($valores as $chave => $valor) {
            $sigiloso = in_array($chave, $sigilosos, true);
            $valor = trim((string) $valor);

            if ($sigiloso && $valor === '') {
                continue;
            }

            $stmt->execute([
                'grupo' => $grupo,
                'chave' => $chave,
                'valor' => $sigiloso ? Cifra::cifrar($valor) : $valor,
                'sigiloso' => $sigiloso ? 1 : 0,
                'atualizado_por' => $autor,
            ]);

            $alterados[] = $chave;
        }

        // So' a lista de campos vai para a trilha, nunca os valores.
        Auditoria::registrar(
            'atualizar',
            'credenciais_sistema',
            null,
            null,
            ['grupo' => $grupo, 'chaves_alteradas' => $alterados],
            'Credenciais alteradas pela tela de Segurança',
            $autor
        );

        return $alterados;
    }

    /**
     * Existe alguma credencial transferida para o banco? Usado pela tela
     * para explicar por que os campos ainda estao vindo do arquivo.
     */
    public function possuiAlgumaCredencial()
    {
        $pdo = Database::conexao();

        return (int) $pdo->query('SELECT COUNT(*) FROM credenciais_sistema')->fetchColumn() > 0;
    }

    private function linhasDoGrupo($grupo)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'SELECT c.chave, c.valor, c.sigiloso, c.atualizado_em, u.nome AS atualizado_por_nome
             FROM credenciais_sistema c
             LEFT JOIN usuarios u ON u.id = c.atualizado_por
             WHERE c.grupo = :grupo
             ORDER BY c.chave ASC'
        );
        $stmt->execute(['grupo' => $grupo]);

        return $stmt->fetchAll();
    }
}
