<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Naturezas do trabalho',
    'resumo' => 'Catálogo de naturezas (ex.: relato de experiência, pesquisa aplicada) que o autor escolhe ao submeter um trabalho neste evento.',
    'operacoes' => [
        [
            'nome' => 'Cadastrar',
            'como' => 'Nome e descrição opcional. A ordem de cadastro é a ordem de exibição no formulário de submissão.',
        ],
        [
            'nome' => 'Sem nenhuma natureza cadastrada',
            'como' => 'O campo "Natureza" simplesmente não aparece no formulário de submissão - não é preciso cadastrar uma opção genérica só para o formulário funcionar.',
        ],
        [
            'nome' => 'Remover',
            'como' => 'Só funciona se nenhum trabalho já submetido usar esta natureza.',
        ],
    ],
    'conceitos' => [],
];
