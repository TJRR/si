<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Slides',
    'resumo' => 'Carrossel de imagens no topo da home pública.',
    'operacoes' => [
        [
            'nome' => '+ Novo',
            'como' => 'Abre o formulário de um slide novo.',
        ],
        [
            'nome' => 'Editar / Remover',
            'icone' => 'editar',
            'como' => 'Remover também apaga as imagens do slide (desktop e mobile).',
        ],
        [
            'nome' => 'Reordenar',
            'como' => 'Ver conceito "Reordenar por arraste" abaixo — aqui define a ordem de exibição no carrossel.',
        ],
    ],
    'conceitos' => ['reordenar_arraste'],
];
