<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Fórmula da Trilha (Nota Final — NF)',
    'resumo' => 'Expressão que combina a nota de cada etapa (variáveis NE1, NE2... na ordem das etapas) na nota final de classificação da trilha.',
    'operacoes' => [
        [
            'nome' => 'Testar fórmula',
            'como' => 'Calcula com valores de exemplo, sem gravar.',
        ],
        [
            'nome' => 'Salvar',
            'como' => 'Grava a expressão.',
            'observacao' => 'O mesmo campo também aparece embutido dentro da tela Apuração — os dois pontos de entrada editam o mesmo dado, não são cópias independentes.',
        ],
    ],
    'conceitos' => [],
];
