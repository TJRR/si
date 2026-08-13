<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Middleware\RoleMiddleware;
use App\Services\NavegacaoService;

/**
 * Endpoint JSON usado pelo JS da arvore lateral (assets/js/navegacao-arvore.js)
 * para carregar os filhos de um no sob demanda, sem consultar tudo de uma vez.
 */
class NavegacaoController extends Controller
{
    public function __construct()
    {
        // Fase 29 (ajuste pos-push): exigirEmQualquerConcurso() (nao
        // exigir()) - exigir() sem concurso so' reconhece vinculo GLOBAL,
        // barrando quem for administrador/suporte de UM concurso
        // especifico. Sem filtro fino por concurso aqui de proposito: e'
        // um endpoint so' de LEITURA pra montar a arvore de navegacao
        // (mesmo padrao ja aceito em ConcursoAdminController::index(), uma
        // listagem geral) - quem for escopado consegue ver os nos da
        // arvore de fora do escopo dele, mas as acoes de verdade (abrir
        // Etapas/Trilhas/Temas/etc. de um concurso alheio) continuam
        // barradas nos controllers correspondentes, ja corrigidos.
        RoleMiddleware::exigirEmQualquerConcurso(['administrador', 'suporte']);
    }

    public function filhos($tipo, $id)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(NavegacaoService::filhosDe($tipo, (int) $id));
    }
}
