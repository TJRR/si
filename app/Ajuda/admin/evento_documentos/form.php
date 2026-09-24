<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Documento do evento: novo',
    'resumo' => 'Envio de um documento novo do evento (ou de uma nova versão de um já existente).',
    'operacoes' => [
        [
            'nome' => 'Tipo / Título / Arquivo',
            'como' => 'O arquivo pode ser PDF ou documento editável (Word: doc e docx; LibreOffice: odt), conferido pelo conteúdo e não só pelo nome. Enviar com o mesmo tipo e título de um documento já existente cria uma nova versão dele, e os botões da página que apontam para ele passam a abrir a versão nova.',
        ],
    ],
    'conceitos' => ['nunca_apaga_so_versiona'],
];
