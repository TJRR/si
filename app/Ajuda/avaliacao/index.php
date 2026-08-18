<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Minhas etapas pendentes',
    'resumo' => 'Lista, agrupada por Concurso e Trilha, das etapas em que você tem submissões para avaliar. Uma etapa só aparece aqui se tiver critérios cadastrados, estiver dentro do período, e você tiver ao menos uma submissão pendente nela (em modo de designação "aberto", basta existir qualquer pendência na etapa, mesmo sem designação individual).',
    'operacoes' => [
        [
            'nome' => 'Ver submissões',
            'como' => 'Abre a lista de submissões da etapa.',
        ],
    ],
    'conceitos' => [],
];
