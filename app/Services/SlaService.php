<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 30: calculo puro de atraso, extraido da duplicacao que ja existia
 * entre DuvidaAdminController::emAtraso() e HomeController::duvidaEmAtraso()
 * (Fase 29) - os dois passam a chamar este metodo, cada um com sua propria
 * data de referencia (nunca criado_em, que so' marca quando o registro foi
 * aberto, nao quando o relogio de atendimento deveria comecar a contar).
 */
class SlaService
{
    public static function emAtraso($dataReferencia, $horasLimite = 48)
    {
        if ($dataReferencia === null) {
            return false;
        }

        return strtotime($dataReferencia) < strtotime('-' . (int) $horasLimite . ' hours');
    }
}
