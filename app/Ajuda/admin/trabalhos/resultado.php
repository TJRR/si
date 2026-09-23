<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Resultado de Trabalhos',
    'resumo' => 'Calcula a nota final de cada trabalho a partir das notas lançadas pelos avaliadores, aplica a nota de corte e a regra de seleção configuradas, e marca quem fica aprovado/selecionado.',
    'operacoes' => [
        [
            'nome' => 'Coluna Desempate',
            'como' => 'Item 7.6 do edital: preenchida só nas linhas que empataram na nota final com o trabalho de cima, dizendo qual critério decidiu.',
        ],
        [
            'nome' => 'Calcular e aplicar',
            'como' => 'Combina as notas dos avaliadores conforme configurado (média aritmética ou mediana), aplica a nota de corte, resolve empate pela cascata de regras de desempate, e seleciona conforme a regra configurada (todos os aprovados, número fixo ou percentual).',
            'observacao' => 'Pode ser repetido quantas vezes for preciso (ex.: depois de uma nota corrigida) - cada execução recalcula tudo do zero, não acumula sobre a anterior.',
        ],
        [
            'nome' => 'Ranking',
            'como' => 'Lista todos os trabalhos avaliados, ordenados pela nota final, com a situação (aprovado/reprovado/selecionado) já aplicada pela última vez que "Calcular e aplicar" foi usado.',
        ],
    ],
    'conceitos' => [],
];
