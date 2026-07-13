<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\LogAuditoriaRepository;

class Auditoria
{
    /**
     * $usuarioId: passar explicitamente quando a sessao ja tiver sido destruida
     * (logout/timeout) ou ainda nao setada; caso contrario, usa Auth::usuarioId().
     * Nunca deixa uma falha de auditoria (ex.: banco fora do ar) derrubar a acao
     * principal que esta sendo auditada.
     */
    public static function registrar($acao, $entidade, $entidadeId = null, $dadosAntes = null, $dadosDepois = null, $mensagem = null, $usuarioId = null)
    {
        try {
            (new LogAuditoriaRepository())->registrar([
                'usuario_id' => $usuarioId !== null ? $usuarioId : Auth::usuarioId(),
                'acao' => $acao,
                'entidade' => $entidade,
                'entidade_id' => $entidadeId,
                'ip_origem' => isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null,
                'dados_antes' => $dadosAntes !== null ? json_encode($dadosAntes) : null,
                'dados_depois' => $dadosDepois !== null ? json_encode($dadosDepois) : null,
                'mensagem' => $mensagem,
            ]);
        } catch (\Throwable $e) {
            error_log('Falha ao registrar auditoria: ' . $e->getMessage());
        }
    }
}
