<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Novo requerimento',
    'resumo' => 'Primeiro passo de um requerimento: descrever a necessidade e gerar o PDF a ser assinado.',
    'operacoes' => [
        [
            'nome' => 'Gerar PDF',
            'como' => 'Preencha a textarea "Necessidade" e clique — cria o registro do requerimento e já baixa o PDF, ainda sem assinatura.',
        ],
    ],
    'conceitos' => [],
];
