<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\RequerimentoRepository;
use App\Services\ArquivoPrivadoService;

/**
 * Fase 30 (validacao automatica no ITI): unica rota publica (sem login)
 * deste conjunto de telas - existe so' pra validar.iti.gov.br buscar, do
 * lado do servidor deles, o PDF assinado de um requerimento durante uma
 * janela curta e de uso unico aberta por RequerimentoAdminController::
 * validarIti(). Qualquer coisa fora do esperado (token errado, expirado, ja
 * usado, requerimento sem PDF ou ja expurgado) devolve 404 generico - nunca
 * uma mensagem que confirme se o requerimento existe.
 */
class ValidacaoPublicaController
{
    private $requerimentos;

    public function __construct()
    {
        $this->requerimentos = new RequerimentoRepository();
    }

    public function pdf($id, $token)
    {
        $requerimento = $this->requerimentos->buscarPorId((int) $id);

        if ($requerimento === null || $requerimento['pdf_assinado_path'] === null || $requerimento['expurgado_em'] !== null) {
            http_response_code(404);
            exit;
        }

        if ($requerimento['iti_token_hash'] === null || !hash_equals($requerimento['iti_token_hash'], hash('sha256', (string) $token))) {
            http_response_code(404);
            exit;
        }

        if (!$this->requerimentos->reivindicarTokenValidacaoIti((int) $id, $requerimento['iti_token_hash'])) {
            http_response_code(404);
            exit;
        }

        ArquivoPrivadoService::servir($requerimento['pdf_assinado_path'], $requerimento['pdf_assinado_nome_original']);
    }
}
