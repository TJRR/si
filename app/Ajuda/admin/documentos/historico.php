<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Histórico de versões (Documentos)',
    'resumo' => 'Todas as versões anteriores de um mesmo documento (mesmo tipo + título), da mais antiga à atual.',
    'operacoes' => [
        [
            'nome' => 'Baixar',
            'icone' => 'baixar',
            'como' => 'Baixa qualquer versão antiga, não só a atual.',
        ],
    ],
    'conceitos' => ['nunca_apaga_so_versiona'],
];
