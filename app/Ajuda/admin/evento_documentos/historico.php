<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Histórico de versões (Documentos do Evento)',
    'resumo' => 'Todas as versões de um mesmo documento do evento (mesmo tipo e título), da atual à mais antiga.',
    'operacoes' => [
        [
            'nome' => 'Baixar',
            'icone' => 'baixar',
            'como' => 'Baixa qualquer versão, não só a atual.',
        ],
    ],
    'conceitos' => ['nunca_apaga_so_versiona'],
];
