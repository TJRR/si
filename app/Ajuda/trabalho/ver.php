<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meu trabalho',
    'resumo' => 'Acompanhamento do trabalho em que você é autor ou coautor: número do protocolo, situação atual e dados de autoria. A tela é somente para leitura.',
    'operacoes' => [
        [
            'nome' => 'Protocolo',
            'como' => 'Número que identifica o trabalho, o mesmo informado na tela e no e-mail depois do envio. Use-o ao falar com a organização do evento.',
        ],
        [
            'nome' => 'Situação',
            'como' => 'Submetido (aguardando avaliação), Desclassificado (com o motivo, se houver), Aprovado ou Reprovado - as duas últimas só aparecem depois do resultado sair.',
        ],
        [
            'nome' => 'Resultado da avaliação',
            'como' => 'Ainda não aparece nesta tela; a exibição da pontuação obtida fica para uma fase futura do sistema.',
        ],
    ],
    'conceitos' => [],
];
