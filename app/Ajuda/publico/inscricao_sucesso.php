<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Inscrição enviada — sucesso',
    'resumo' => 'Confirmação de que a equipe foi cadastrada.',
    'operacoes' => [],
    'conceitos' => ['cadastro_pendente_aprovacao'],
];
