<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Cronograma',
    'resumo' => 'Linha do tempo de marcos, como o cronograma de submissão do edital.',
    'operacoes' => [
        ['nome' => 'Período', 'como' => 'Escreva como deve ser lido na página: "16/10/2026, até 23h59" ou "19 a 26/10/2026".'],
        ['nome' => 'Data de referência', 'como' => 'Opcional, serve para ordenar e destacar o marco; um intervalo não precisa dela.'],
        ['nome' => 'Cor', 'como' => 'Cor do marcador daquele item.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
