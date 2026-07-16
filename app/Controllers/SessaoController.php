<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;

/**
 * Fase 17 (Bug 8): endpoint de heartbeat para telas de formulario longo
 * (ex.: submissao de ideia). Nao faz nada por si so - o Router::despachar
 * ja chama Auth::validarAtividade() e atualiza ultima_atividade para
 * QUALQUER requisicao autenticada antes de chegar aqui; o unico proposito
 * deste endpoint e' dar ao JS de heartbeat algo leve pra chamar
 * periodicamente enquanto o usuario preenche o formulario sem navegar.
 */
class SessaoController extends Controller
{
    public function manterViva()
    {
        http_response_code(204);
    }
}
