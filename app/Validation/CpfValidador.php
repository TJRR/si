<?php

namespace App\Validation;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

class CpfValidador
{
    public static function apenasDigitos($cpf)
    {
        return preg_replace('/\D/', '', (string) $cpf);
    }

    public static function valido($cpf)
    {
        $cpf = self::apenasDigitos($cpf);

        if (strlen($cpf) !== 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        for ($posicaoDigito = 9; $posicaoDigito <= 10; $posicaoDigito++) {
            $soma = 0;

            for ($i = 0; $i < $posicaoDigito; $i++) {
                $soma += (int) $cpf[$i] * (($posicaoDigito + 1) - $i);
            }

            $resto = $soma % 11;
            $digitoEsperado = $resto < 2 ? 0 : 11 - $resto;

            if ((int) $cpf[$posicaoDigito] !== $digitoEsperado) {
                return false;
            }
        }

        return true;
    }
}
