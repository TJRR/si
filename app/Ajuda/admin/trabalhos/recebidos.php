<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Trabalhos recebidos',
    'resumo' => 'Lista de todo trabalho submetido neste evento, com a situação de cada um e quantos avaliadores já foram designados.',
    'operacoes' => [
        [
            'nome' => 'Coluna "Avaliadores"',
            'como' => 'Mostra quantos avaliadores já foram designados, sobre a quantidade configurada. Marca "(incompleto)" quando estiver abaixo do configurado.',
        ],
        [
            'nome' => 'Ver',
            'como' => 'Abre o detalhe do trabalho: dados de autoria, conteúdo submetido, avaliadores designados e ação de desclassificar.',
        ],
    ],
    'conceitos' => ['sigilo_anonimato'],
];
