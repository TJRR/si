<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Nova mídia',
    'resumo' => 'Upload de um item novo para a Biblioteca de Mídia.',
    'operacoes' => [
        [
            'nome' => 'Tipo / Arquivo / Alt / Título / Descrição',
            'como' => 'Alt (texto alternativo) é obrigatório para imagens.',
        ],
    ],
    'conceitos' => ['banco_global_vs_edicao'],
];
