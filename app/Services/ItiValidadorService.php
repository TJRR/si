<?php

namespace App\Services;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Fase 30 (validacao automatica no ITI): chama a rota interna que
 * validar.iti.gov.br usa no proprio site pra checar um PDF assinado a
 * partir de uma URL - nao e' uma API publicada/documentada pelo ITI (achada
 * inspecionando as chamadas do site oficial no navegador), entao pode mudar
 * de formato ou parar de funcionar sem aviso a qualquer momento.
 *
 * Por isso a leitura da resposta e' deliberadamente conservadora: qualquer
 * coisa fora do formato esperado (campo ausente, tipo errado, JSON
 * invalido, erro de rede) devolve null - "nao deu pra confirmar
 * automaticamente", nunca um resultado interpretado. O resultado, quando
 * existe, e' sempre um apoio visual complementar - nunca substitui a
 * conferencia manual no site oficial (ver RequerimentoAdminController::
 * validarIti() e a view admin/requerimentos/ver.php).
 */
class ItiValidadorService
{
    private const ENDPOINT = 'https://validar.iti.gov.br/url';
    private const TIMEOUT_SEGUNDOS = 25;

    public static function validar($urlPublicaDoPdf)
    {
        $resposta = self::chamar($urlPublicaDoPdf);

        if ($resposta === null) {
            return null;
        }

        return self::extrairResultado($resposta);
    }

    private static function chamar($urlPublicaDoPdf)
    {
        $ch = curl_init(self::ENDPOINT);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['url' => $urlPublicaDoPdf]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Origin: https://validar.iti.gov.br',
                'Referer: https://validar.iti.gov.br/',
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36',
            ],
            CURLOPT_TIMEOUT => self::TIMEOUT_SEGUNDOS,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $corpo = curl_exec($ch);
        $codigoHttp = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erro = curl_error($ch);
        curl_close($ch);

        if ($erro !== '' || $codigoHttp !== 200 || $corpo === false) {
            return null;
        }

        $dados = json_decode($corpo, true);

        return (json_last_error() === JSON_ERROR_NONE && is_array($dados)) ? $dados : null;
    }

    /**
     * Le so' os campos que a tela mostra - qualquer um ausente ou de tipo
     * errado faz o metodo inteiro devolver null (ver classe acima).
     * generalName pode vir como objeto unico ou lista, dependendo de quantos
     * "subjectAlternativeNames" o certificado tiver - trata os dois casos.
     */
    private static function extrairResultado(array $dados)
    {
        $primeiro = isset($dados[0]) && is_array($dados[0]) ? $dados[0] : null;

        if ($primeiro === null) {
            return null;
        }

        $statusGeral = $primeiro['verifierReport']['generalStatus'] ?? null;
        $assinatura = $primeiro['verifierReport']['signatures']['signature'] ?? null;

        if (!is_string($statusGeral) || $statusGeral === '' || !is_array($assinatura)) {
            return null;
        }

        $signatario = $assinatura['certification']['signer'] ?? null;

        if (!is_array($signatario)) {
            return null;
        }

        $nomeAssinante = isset($signatario['subjectName']) && is_string($signatario['subjectName'])
            ? preg_replace('/^CN=/', '', $signatario['subjectName'])
            : null;

        $cpfAssinante = self::extrairCpf($signatario);
        $dataAssinatura = isset($assinatura['signingTime']) && is_string($assinatura['signingTime']) ? $assinatura['signingTime'] : null;

        return [
            'status_geral' => $statusGeral,
            'nome' => $nomeAssinante,
            'cpf' => $cpfAssinante,
            'data_assinatura' => $dataAssinatura,
        ];
    }

    private static function extrairCpf(array $signatario)
    {
        $generalName = $signatario['extensions']['subjectAlternativeNames']['generalName'] ?? null;

        if (!is_array($generalName)) {
            return null;
        }

        // Um unico SAN vem como objeto associativo ("name"/"value" direto);
        // mais de um vem como lista de objetos - normaliza pra lista.
        $entradas = isset($generalName['name']) ? [$generalName] : $generalName;

        foreach ($entradas as $entrada) {
            if (is_array($entrada) && ($entrada['name'] ?? null) === 'CPF' && is_string($entrada['value'] ?? null)) {
                return $entrada['value'];
            }
        }

        return null;
    }
}
