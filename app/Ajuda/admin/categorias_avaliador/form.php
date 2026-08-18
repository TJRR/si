<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Categoria de avaliador — novo/editar',
    'resumo' => 'Nome de uma categoria de avaliador.',
    'operacoes' => [],
    'conceitos' => [],
];
