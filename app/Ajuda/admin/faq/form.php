<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Pergunta do FAQ — novo/editar',
    'resumo' => 'Texto de uma pergunta/resposta do banco global.',
    'operacoes' => [],
    'conceitos' => ['banco_global_vs_edicao'],
];
