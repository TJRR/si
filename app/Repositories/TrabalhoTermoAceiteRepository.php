<?php

namespace App\Repositories;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Auditoria;
use App\Core\Database;

/**
 * Fase 51: aceites registrados de cada submissao de Trabalho. Guarda o texto
 * exato aceito, nao so o id do termo: o termo pode ser corrigido depois, e o
 * que a pessoa aceitou naquele dia precisa continuar recuperavel palavra por
 * palavra (mesmo principio ja usado para resultado publicado, que fica
 * congelado). termo_id fica nulo quando o aceite veio de fora do sistema.
 */
class TrabalhoTermoAceiteRepository
{
    public function listarPorTrabalho($trabalhoId)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare('SELECT * FROM trabalho_termos_aceitos WHERE trabalho_id = :trabalho_id ORDER BY id ASC');
        $stmt->execute(['trabalho_id' => $trabalhoId]);

        return $stmt->fetchAll();
    }

    /**
     * $aceitos: lista de arrays com termo_id, rotulo, texto_html e, quando
     * vier de importacao, aceito_em e origem proprios (data do envio
     * original, nunca a data da importacao).
     */
    public function registrar($trabalhoId, array $aceitos)
    {
        $pdo = Database::conexao();
        $stmt = $pdo->prepare(
            'INSERT INTO trabalho_termos_aceitos (trabalho_id, termo_id, rotulo_snapshot, texto_snapshot, origem, aceito_em)
             VALUES (:trabalho_id, :termo_id, :rotulo_snapshot, :texto_snapshot, :origem, :aceito_em)'
        );

        foreach ($aceitos as $aceito) {
            $stmt->execute([
                'trabalho_id' => $trabalhoId,
                'termo_id' => isset($aceito['termo_id']) ? $aceito['termo_id'] : null,
                'rotulo_snapshot' => $aceito['rotulo'],
                'texto_snapshot' => $aceito['texto_html'],
                'origem' => isset($aceito['origem']) ? $aceito['origem'] : 'sistema',
                'aceito_em' => isset($aceito['aceito_em']) ? $aceito['aceito_em'] : date('Y-m-d H:i:s'),
            ]);
        }

        Auditoria::registrar('criar', 'trabalho_termos_aceitos', $trabalhoId, null, ['total' => count($aceitos)]);
    }
}
