<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Progresso da avaliação',
    'resumo' => 'Quebra por avaliador de quantas submissões já foram avaliadas e quais ainda faltam, nesta etapa. Tela só de leitura, uma linha por avaliador.',
    'operacoes' => [
        [
            'nome' => 'Coluna "Pendentes"',
            'como' => 'Sem pendências, mostra a pill verde "Nenhuma". Com pendências, mostra em laranja a quantidade — a lista de submissões (número + equipe) aparece na última coluna.',
            'pills' => [
                ['cor' => 'verde', 'rotulo' => 'Nenhuma'],
            ],
        ],
    ],
    'conceitos' => [],
];
