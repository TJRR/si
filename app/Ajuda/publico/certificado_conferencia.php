<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Conferência de certificado',
    'resumo' => 'Confere se um certificado foi mesmo emitido por este sistema, a partir do código impresso nele. Não é preciso ter conta.',
    'operacoes' => [
        [
            'nome' => 'Onde está o código',
            'como' => 'No pé do certificado, junto com o endereço desta página. São dez caracteres, com letras e números.',
        ],
        [
            'nome' => 'O que a página mostra',
            'como' => 'O nome de quem recebeu, a condição em que participou, o evento, o período, a carga horária, a data de emissão e se o documento continua valendo.',
        ],
        [
            'nome' => 'O que a página não mostra',
            'como' => 'O documento de identificação de quem recebeu e o arquivo do certificado. Ela existe para conferir o que foi emitido, não para distribuir dado pessoal nem para entregar uma segunda via, que sai apenas para o próprio interessado ou pela organização.',
        ],
        [
            'nome' => 'Certificado cancelado',
            'como' => 'A página informa o cancelamento e a data. Um documento cancelado não vale como comprovação, mesmo que a cópia em papel continue existindo.',
        ],
        [
            'nome' => 'Código não encontrado',
            'como' => 'Confira a digitação. A resposta é a mesma para código inexistente e para código mal escrito, de propósito: esta página não serve para descobrir quais códigos existem. Muitas tentativas sem sucesso fecham a conferência por alguns minutos.',
        ],
    ],
    'conceitos' => [],
];
