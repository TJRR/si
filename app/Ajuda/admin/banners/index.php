<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Banners',
    'resumo' => 'Faixas de texto exibidas logo abaixo do slideshow, na home.',
    'operacoes' => [
        [
            'nome' => '+ Novo / Editar / Remover',
            'icone' => 'editar',
            'como' => 'CRUD do banner.',
        ],
        [
            'nome' => 'Reordenar',
            'como' => 'Ver conceito "Reordenar por arraste" abaixo.',
        ],
    ],
    'conceitos' => ['reordenar_arraste'],
];
