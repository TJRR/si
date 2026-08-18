<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Submissão enviada — sucesso',
    'resumo' => 'Confirmação de que a submissão foi recebida, com o número dela. Tela só de leitura.',
    'operacoes' => [],
    'conceitos' => [],
];
