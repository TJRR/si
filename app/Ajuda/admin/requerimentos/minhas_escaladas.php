<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Requerimentos — minhas escaladas',
    'resumo' => 'Fila pessoal de requerimentos sob sua responsabilidade agora — mesmo padrão de Dúvidas.',
    'operacoes' => [
        [
            'nome' => 'Ver',
            'icone' => 'ver',
            'como' => 'Abre o detalhe/atendimento do requerimento.',
        ],
    ],
    'conceitos' => ['atendimento_fila_escalonamento'],
];
