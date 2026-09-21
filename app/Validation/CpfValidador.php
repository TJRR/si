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

    /**
     * Fase 42 (correcao pos-teste de fumaca): mesma mascara ja aplicada em
     * tempo real pelo JS (assets/js/cpf-validador.js, formatarCpf()), aqui
     * para formatar na EXIBICAO um CPF ja gravado (so' digitos no banco,
     * mesmo padrao usado desde o cadastro do Concurso). Nao mascara valor
     * que nao tenha exatamente 11 digitos - devolve como veio, ao inves de
     * exibir uma mascara parcial/enganosa sobre dado incompleto ou legado.
     */
    public static function formatar($cpf)
    {
        $digitos = self::apenasDigitos($cpf);

        if (strlen($digitos) !== 11) {
            return (string) $cpf;
        }

        return substr($digitos, 0, 3) . '.' . substr($digitos, 3, 3) . '.' . substr($digitos, 6, 3) . '-' . substr($digitos, 9, 2);
    }
}
