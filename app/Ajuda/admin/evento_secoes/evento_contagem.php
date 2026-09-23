<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Contagem regressiva',
    'resumo' => 'Mostra quanto falta para a data escolhida e, ao lado, as datas que você quiser destacar.',
    'operacoes' => [
        ['nome' => 'Data e hora alvo', 'como' => 'Em branco, a contagem usa a data de início do evento, às 00h00.'],
        ['nome' => 'Datas em destaque', 'como' => 'Cada item vira uma linha com marcador colorido ao lado do relógio. A ordem é a do arraste.'],
        ['nome' => 'Cores', 'como' => 'Cor de fundo e cor do texto valem para a seção inteira; a cor do círculo vale só para o relógio.'],
    ],
    'conceitos' => ['reordenar_arraste'],
];
