<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Aviso do evento',
    'resumo' => 'Conteúdo completo de um aviso enviado pela organização do evento; o mesmo que gerou a notificação no sino.',
    'operacoes' => [],
    'conceitos' => [],
];
