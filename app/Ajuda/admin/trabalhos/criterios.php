<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Critérios de avaliação',
    'resumo' => 'Critérios que compõem a nota de um trabalho, e o resumo desses critérios que o avaliador consulta dentro da própria tela de avaliação.',
    'operacoes' => [
        [
            'nome' => 'Novo critério',
            'como' => 'Nome, descrição e nota máxima. A nota máxima do trabalho é a soma da nota máxima de todos os critérios cadastrados.',
        ],
        [
            'nome' => 'Mover ▲ / ▼',
            'como' => 'Arraste pela alça ⠿ ou use as setas: reordena o critério, é a ordem em que aparece na tela de avaliação.',
        ],
        [
            'nome' => 'Remover',
            'como' => 'Só funciona se o critério ainda não tiver nota lançada nem estiver usado numa regra de desempate.',
        ],
        [
            'nome' => 'Resumo para o avaliador',
            'como' => 'Editor de texto rico com o trecho do edital sobre os critérios (nome, faixa de nota e como interpretar cada um) - o avaliador consulta isso num painel próprio, dentro da tela de avaliação, sem precisar abrir o edital inteiro. Enquanto ficar em branco, o avaliador não vê nenhum resumo nesse painel.',
            'observacao' => 'Formulário próprio, com botão de salvar independente das demais Configurações - salvar um não afeta o outro.',
        ],
    ],
    'conceitos' => [],
];
