<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Eixos temáticos',
    'resumo' => 'Catálogo de eixos temáticos que o autor escolhe ao submeter um trabalho neste evento.',
    'operacoes' => [
        [
            'nome' => 'Cadastrar',
            'como' => 'Nome e descrição opcional. A ordem de cadastro é a ordem de exibição no formulário de submissão.',
        ],
        [
            'nome' => 'Sem nenhum eixo cadastrado',
            'como' => 'O campo "Eixo temático" simplesmente não aparece no formulário de submissão - não é preciso cadastrar um eixo genérico só para o formulário funcionar.',
        ],
        [
            'nome' => 'Remover',
            'como' => 'Só funciona se nenhum trabalho já submetido usar este eixo.',
        ],
    ],
    'conceitos' => [],
];
