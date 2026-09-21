<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Ordenação da Página Inicial',
    'resumo' => 'Define a ordem das seções da página inicial pública: seções fixas (Trilhas, Cronograma, Desafios, FAQ) e os Blocos de conteúdo, todos numa lista só. O carrossel de imagens e as Faixas ficam sempre no topo, e Contato sempre no rodapé; os três não entram nesta lista.',
    'operacoes' => [
        [
            'nome' => 'Reordenar',
            'como' => 'Ver conceito "Reordenar por arraste" abaixo.',
        ],
    ],
    'conceitos' => ['reordenar_arraste'],
];
