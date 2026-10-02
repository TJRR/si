<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Novo requerimento',
    'resumo' => 'Primeiro passo de um requerimento: você descreve o que a equipe precisa e o sistema gera o documento em PDF para ser assinado digitalmente.',
    'operacoes' => [
        [
            'nome' => 'Passo a passo',
            'como' => '1. Descreva a necessidade da equipe no campo de texto. 2. Clique em "Gerar PDF". O requerimento é criado e o PDF, ainda sem assinatura, é baixado no seu computador. 3. Assine o PDF no gov.br e volte ao requerimento para enviar o arquivo assinado.',
        ],
    ],
    'conceitos' => [],
];
