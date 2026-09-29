<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Aparência',
    'resumo' => 'Escolha do tema de cores. A escolha é sua e vale em todo o sistema, dentro e fora do aplicativo do evento.',
    'operacoes' => [
        [
            'nome' => 'Lista de temas',
            'como' => 'Mostra os temas que a organização publicou, com uma amostra das cores de cada um. Marque o que preferir e toque em "Salvar tema".',
        ],
        [
            'nome' => 'Sem escolher nenhum',
            'como' => 'Quem não escolhe tema vê o tema padrão definido pela organização.',
        ],
    ],
    'conceitos' => [],
];
