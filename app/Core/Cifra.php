<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

/**
 * Cifragem simetrica das credenciais de integracao guardadas no banco.
 * Ver Implantar.md, secao 13.6.
 */
class Cifra
{
    const ALGORITMO = 'aes-256-gcm';
    const TAMANHO_VETOR = 12;
    const TAMANHO_ETIQUETA = 16;

    /** @var string|null|false null = ainda nao lida; false = ausente */
    private static $chaveMestra = null;

    /**
     * Devolve o texto cifrado em base64(vetor|etiqueta|cifra), ou null se
     * nao houver chave-mestra configurada. NUNCA lanca excecao por ausencia
     * de chave: sem ela o sistema continua no ar, so' a integracao que
     * depende da credencial e' que fica indisponivel.
     */
    public static function cifrar($valorClaro)
    {
        if ($valorClaro === null || $valorClaro === '') {
            return null;
        }

        $chave = self::chaveMestra();

        if ($chave === false) {
            return null;
        }

        // Vetor de inicializacao sorteado a cada chamada, nunca reaproveitado:
        // reusar vetor em GCM quebra a garantia do modo por completo.
        $vetor = random_bytes(self::TAMANHO_VETOR);
        $etiqueta = '';
        $cifrado = openssl_encrypt($valorClaro, self::ALGORITMO, $chave, OPENSSL_RAW_DATA, $vetor, $etiqueta);

        if ($cifrado === false) {
            return null;
        }

        return base64_encode($vetor . $etiqueta . $cifrado);
    }

    /**
     * Devolve o texto claro, ou null quando nao ha chave-mestra, quando o
     * conteudo esta corrompido ou quando a chave-mestra e' outra.
     */
    public static function decifrar($valorCifrado)
    {
        if (empty($valorCifrado)) {
            return null;
        }

        $chave = self::chaveMestra();

        if ($chave === false) {
            return null;
        }

        $dados = base64_decode($valorCifrado, true);

        if ($dados === false || strlen($dados) <= (self::TAMANHO_VETOR + self::TAMANHO_ETIQUETA)) {
            return null;
        }

        $vetor = substr($dados, 0, self::TAMANHO_VETOR);
        $etiqueta = substr($dados, self::TAMANHO_VETOR, self::TAMANHO_ETIQUETA);
        $cifrado = substr($dados, self::TAMANHO_VETOR + self::TAMANHO_ETIQUETA);

        $claro = openssl_decrypt($cifrado, self::ALGORITMO, $chave, OPENSSL_RAW_DATA, $vetor, $etiqueta);

        return $claro === false ? null : $claro;
    }

    /**
     * Identifica um segredo sem revelar nada dele: os 16 primeiros digitos
     * hexadecimais do resumo SHA-256, para conferir se duas instalacoes usam a
     * mesma credencial.
     */
    public static function impressaoDigital($valorClaro)
    {
        if ($valorClaro === null || $valorClaro === '') {
            return null;
        }

        return 'sha256:' . substr(hash('sha256', $valorClaro), 0, 16);
    }

    public static function chaveMestraConfigurada()
    {
        return self::chaveMestra() !== false;
    }

    /**
     * hash() de 32 bytes crus a partir do texto configurado - aceita
     * qualquer comprimento de chave escrita a mao e entrega os 256 bits
     * exatos que o AES-256 exige.
     */
    private static function chaveMestra()
    {
        if (self::$chaveMestra !== null) {
            return self::$chaveMestra;
        }

        $config = require __DIR__ . '/../../config/local.php';
        $texto = isset($config['cifra']['chave_mestra']) ? trim((string) $config['cifra']['chave_mestra']) : '';

        self::$chaveMestra = $texto !== '' ? hash('sha256', $texto, true) : false;

        return self::$chaveMestra;
    }
}
