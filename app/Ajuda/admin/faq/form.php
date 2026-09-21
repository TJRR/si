<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Pergunta do FAQ: novo/editar',
    'resumo' => 'Texto de uma pergunta/resposta do banco global. A mesma tela atende o cadastro de uma pergunta nova e a edição de uma já existente.',
    'operacoes' => [
        [
            'nome' => 'Pergunta',
            'como' => 'Máximo de 255 caracteres.',
        ],
        [
            'nome' => 'Origem em dúvida real',
            'como' => 'Uma pergunta pode ter nascido de uma dúvida de participante, promovida na tela de atendimento. Editar o texto aqui não muda nem a dúvida de origem nem o registro de que ela veio de lá.',
        ],
    ],
    'conceitos' => ['banco_global_vs_edicao'],
];
