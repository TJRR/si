<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Minhas dúvidas',
    'resumo' => 'Lista de dúvidas registradas pela sua equipe — qualquer integrante (não só o líder) pode registrar e ver.',
    'operacoes' => [
        [
            'nome' => 'Registrar dúvida',
            'como' => 'Abre o formulário de uma nova dúvida.',
        ],
        [
            'nome' => 'Ver',
            'icone' => 'ver',
            'como' => 'Abre a thread completa da dúvida.',
            'pills' => [
                ['cor' => 'azul', 'rotulo' => 'Recebida'],
                ['cor' => 'laranja', 'rotulo' => 'Em análise'],
                ['cor' => 'verde', 'rotulo' => 'Respondida'],
            ],
        ],
    ],
    'conceitos' => [],
];
