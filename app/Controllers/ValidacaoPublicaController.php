<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Repositories\RequerimentoRepository;
use App\Services\ArquivoPrivadoService;

/**
 * Rota publica, sem entrada, usada so' pela conferencia de assinatura do
 * ITI, numa janela curta e de uso unico. Ver Implantar.md, secao 13.6.
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
