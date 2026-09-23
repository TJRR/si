<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Resultado final da trilha',
    'resumo' => 'Página pública com a classificação final da trilha, publicada pela organização.',
    'operacoes' => [
        ['nome' => 'O que aparece aqui', 'como' => 'Depende do que a organização liberou: só as colocações com destaque cadastrado, ou a classificação completa com a Nota Final.'],
        ['nome' => 'Destaque de cada case', 'como' => 'Resumo e imagem publicados pela organização para as colocações escolhidas.'],
    ],
    'conceitos' => ['publicar_trava'],
];
