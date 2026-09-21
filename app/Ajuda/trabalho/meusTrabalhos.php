<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meus trabalhos',
    'resumo' => 'Lista de todo trabalho que você submeteu, em qualquer evento, com a situação atual de cada um.',
    'operacoes' => [
        [
            'nome' => 'Ver detalhes',
            'como' => 'Abre a situação completa daquele trabalho.',
        ],
    ],
    'conceitos' => [],
];
