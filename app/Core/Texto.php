<?php

namespace App\Core;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

class Texto
{
    private static $mapaAcentos = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
        'Ç' => 'C', 'ç' => 'c',
        'Ñ' => 'N', 'ñ' => 'n',
    ];

    public static function slugify($valor)
    {
        $valor = trim($valor);
        $valor = strtr($valor, self::$mapaAcentos);
        $valor = strtolower($valor);
        $valor = preg_replace('/[^a-z0-9]+/', '-', $valor);

        return trim($valor, '-');
    }

    private static $mesesAbreviados = [
        1 => 'jan.', 2 => 'fev.', 3 => 'mar.', 4 => 'abr.', 5 => 'mai.', 6 => 'jun.',
        7 => 'jul.', 8 => 'ago.', 9 => 'set.', 10 => 'out.', 11 => 'nov.', 12 => 'dez.',
    ];

    /**
     * Fase 19 (#17): "8 de jul." - sem depender da extensao intl (o projeto
     * e' PHP puro, sem Composer alem do autoload/PHPMailer).
     */
    public static function dataAbreviada($datetimeString)
    {
        $timestamp = strtotime((string) $datetimeString);

        if ($timestamp === false) {
            return '';
        }

        return (int) date('j', $timestamp) . ' de ' . self::$mesesAbreviados[(int) date('n', $timestamp)];
    }
}
