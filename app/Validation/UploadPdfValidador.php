<?php

namespace App\Validation;

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

class UploadPdfValidador
{
    public static function validar(array $arquivo, $limiteBytes)
    {
        if (!isset($arquivo['error']) || $arquivo['error'] === UPLOAD_ERR_NO_FILE) {
            return ['valido' => false, 'mensagem' => 'Nenhum arquivo enviado.'];
        }

        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            return ['valido' => false, 'mensagem' => 'Falha no envio do arquivo.'];
        }

        if (!is_uploaded_file($arquivo['tmp_name'])) {
            return ['valido' => false, 'mensagem' => 'Arquivo invalido.'];
        }

        if ($arquivo['size'] > $limiteBytes) {
            $limiteMb = (int) round($limiteBytes / 1024 / 1024);

            return ['valido' => false, 'mensagem' => "Arquivo maior que o limite de {$limiteMb}MB."];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeReal = finfo_file($finfo, $arquivo['tmp_name']);
        finfo_close($finfo);

        if ($mimeReal !== 'application/pdf') {
            return ['valido' => false, 'mensagem' => 'O arquivo enviado nao e um PDF valido.'];
        }

        return ['valido' => true, 'mensagem' => null];
    }
}
