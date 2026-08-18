<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Critérios',
    'resumo' => 'O que os avaliadores pontuam numa etapa: código (usado nas fórmulas), peso e escala. A tela mostra a soma dos pesos cadastrados (nos editais de 2026, essa soma é 10).',
    'operacoes' => [
        [
            'nome' => '+ Novo critério',
            'como' => 'Abre o formulário de um critério novo.',
        ],
        [
            'nome' => 'Editar',
            'icone' => 'editar',
            'como' => 'Altera código, nome, descrição, peso, escala e quais campos aparecem na aba deste critério na tela do avaliador.',
        ],
        [
            'nome' => 'Mover cima/baixo',
            'icone' => 'mover_cima',
            'como' => 'Ajusta a ordem de exibição.',
            'observacao' => 'A ordem aqui só afeta exibição — o cálculo da fórmula usa o código do critério, não a posição.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga o critério.',
            'observacao' => 'Sem confirmação — diferente da maioria das remoções do sistema, clique com atenção.',
        ],
    ],
    'conceitos' => [],
];
