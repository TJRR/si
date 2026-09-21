<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Submissão indisponível',
    'resumo' => 'Explica por que o formulário de submissão não pode ser aberto agora (fora do prazo, ou a submissão deste evento ainda não foi publicada pela organização).',
    'operacoes' => [],
    'conceitos' => [],
];
