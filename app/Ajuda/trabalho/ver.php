<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Meu trabalho',
    'resumo' => 'Acompanhamento do trabalho que você submeteu - situação atual e dados de autoria.',
    'operacoes' => [
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
