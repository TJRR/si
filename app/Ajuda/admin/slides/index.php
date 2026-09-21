<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Carrossel de imagens',
    'resumo' => 'Carrossel de imagens no topo da home pública.',
    'operacoes' => [
        [
            'nome' => '+ Novo',
            'como' => 'Abre o formulário de uma nova imagem do carrossel.',
        ],
        [
            'nome' => 'Editar / Remover',
            'icone' => 'editar',
            'como' => 'Remover também apaga os arquivos da imagem do carrossel (computador e celular).',
        ],
        [
            'nome' => 'Reordenar',
            'como' => 'Ver conceito "Reordenar por arraste" abaixo: aqui define a ordem de exibição no carrossel.',
        ],
    ],
    'conceitos' => ['reordenar_arraste'],
];
