<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Tema — Favicon e Cores',
    'resumo' => 'Configuração visual global do sistema (vale para todas as edições, não é por concurso): favicon, cor primária (início e fim do degradê) e cor secundária.',
    'operacoes' => [
        [
            'nome' => 'Favicon',
            'como' => 'Ícone exibido na aba do navegador.',
        ],
        [
            'nome' => 'Cores',
            'como' => 'Preview em tempo real ao lado de cada campo de cor.',
        ],
    ],
    'conceitos' => [],
];
