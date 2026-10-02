<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Minhas dúvidas',
    'resumo' => 'Aqui ficam todas as dúvidas registradas pela sua equipe, com a situação de cada uma. Qualquer integrante, e não só o líder, pode registrar uma dúvida nova e acompanhar as respostas.',
    'operacoes' => [
        [
            'nome' => 'Registrar dúvida',
            'como' => 'Abre o formulário para fazer uma pergunta nova à organização.',
        ],
        [
            'nome' => 'Ver',
            'icone' => 'ver',
            'como' => 'Abre a conversa completa da dúvida, com a pergunta, as respostas e os anexos. A cor mostra em que pé ela está:',
            'pills' => [
                ['cor' => 'azul', 'rotulo' => 'Recebida'],
                ['cor' => 'laranja', 'rotulo' => 'Em análise'],
                ['cor' => 'verde', 'rotulo' => 'Respondida'],
            ],
        ],
    ],
    'conceitos' => [],
];
