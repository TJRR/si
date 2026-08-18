<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Categorias de Avaliador',
    'resumo' => 'Categorias livres (ex.: Professor, Área finalística/meio, TI) usadas no modo "sorteio por categoria" — garante pelo menos 1 avaliador de cada categoria por submissão.',
    'operacoes' => [
        [
            'nome' => '+ Nova categoria / Editar / Remover',
            'icone' => 'editar',
            'como' => 'CRUD simples: nome da categoria.',
            'observacao' => 'Remover uma categoria não apaga avaliadores — eles só perdem a categoria (confirmação avisa isso antes).',
        ],
    ],
    'conceitos' => [],
];
