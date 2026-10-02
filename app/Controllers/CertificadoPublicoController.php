<?php

namespace App\Controllers;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

use App\Core\Controller;
use App\Repositories\CertificadoConferenciaFalhaRepository;
use App\Repositories\CertificadoRepository;
use App\Services\CertificadoElegibilidadeService;

/**
 * Pagina publica de conferencia de certificado, aberta a quem nao tem
 * conta: quem recebe um certificado digita o codigo impresso nele e ve se
 * o documento foi emitido pelo sistema. Nao mostra o documento de
 * identificacao nem entrega o arquivo. Ver Implantar.md, secao 13.18.
 */
class CertificadoPublicoController extends Controller
{
    const MENSAGEM_NAO_ENCONTRADO = 'Não encontramos certificado com este código. Confira a digitação: o código tem dez caracteres e está impresso no pé do documento.';

    public function index()
    {
        $codigo = isset($_GET['codigo']) ? strtoupper(trim((string) $_GET['codigo'])) : '';
        $falhas = new CertificadoConferenciaFalhaRepository();
        $origem = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;

        $certificado = null;
        $erro = null;

        if ($codigo !== '') {
            if ($falhas->excedeuOLimite($origem)) {
                $erro = 'Muitas tentativas sem sucesso. Espere alguns minutos e tente outra vez.';
            } else {
                $certificado = $this->conferir($codigo);

                if ($certificado === null) {
                    $falhas->registrarFalha($origem);
                    $erro = self::MENSAGEM_NAO_ENCONTRADO;
                } else {
                    $falhas->limparFalhas($origem);
                }
            }
        }

        $this->renderizar('publico/certificado_conferencia', [
            'codigo' => $codigo,
            'certificado' => $certificado,
            'erro' => $erro,
            'cargaHoraria' => $certificado !== null && $certificado['carga_horaria_minutos'] !== null
                ? CertificadoElegibilidadeService::formatarCargaHoraria($certificado['carga_horaria_minutos'])
                : null,
        ], 'Conferência de certificado');
    }

    /**
     * Busca o certificado do codigo digitado. Formato invalido nao chega ao
     * banco: o alfabeto e' o de CodigoUnicoService, sem as letras que se
     * confundem com numero.
     */
    private function conferir($codigo)
    {
        if (!preg_match('/^[0-9ABCDEFGHJKMNPQRSTVWXYZ]{10}$/', $codigo)) {
            return null;
        }

        return (new CertificadoRepository())->buscarPorCodigo($codigo);
    }
}
