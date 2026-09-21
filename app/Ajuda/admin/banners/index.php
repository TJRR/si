<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Faixas',
    'resumo' => 'Faixas de texto exibidas logo abaixo do carrossel de imagens, na página inicial.',
    'operacoes' => [
        [
            'nome' => '+ Nova / Editar / Remover',
            'icone' => 'editar',
            'como' => 'Criar, editar e remover a faixa.',
        ],
        [
            'nome' => 'Reordenar',
            'como' => 'Ver conceito "Reordenar por arraste" abaixo.',
        ],
    ],
    'conceitos' => ['reordenar_arraste'],
];
