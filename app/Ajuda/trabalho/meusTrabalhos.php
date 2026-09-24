<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meus trabalhos',
    'resumo' => 'Lista de todo trabalho em que você consta como autor ou coautor, em qualquer evento, com o número do protocolo e a situação atual de cada um.',
    'operacoes' => [
        [
            'nome' => 'Ver detalhes',
            'como' => 'Abre a situação completa daquele trabalho.',
        ],
        [
            'nome' => 'Trabalhos em que você é coautor',
            'como' => 'Aparecem com a marca "(você é coautor)". O coautor acompanha a situação do trabalho, somente para leitura: quem envia e corrige a submissão é o autor principal.',
        ],
    ],
    'conceitos' => [],
];
