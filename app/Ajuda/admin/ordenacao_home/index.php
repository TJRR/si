<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Ordenação da Home',
    'resumo' => 'Define a ordem das seções da home pública: seções fixas (Trilhas, Cronograma, Desafios, FAQ) e os Blocos de conteúdo, todos numa lista só. Slideshow e Banners ficam sempre no topo, e Contato sempre no rodapé — os três não entram nesta lista.',
    'operacoes' => [
        [
            'nome' => 'Reordenar',
            'como' => 'Ver conceito "Reordenar por arraste" abaixo.',
        ],
    ],
    'conceitos' => ['reordenar_arraste'],
];
