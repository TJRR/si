<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Representante de estande',
    'resumo' => 'Você não representa nenhum estande no momento. O acesso ao painel do estande é dado pela organização do evento, por convite.',
    'operacoes' => [],
    'conceitos' => [],
];
