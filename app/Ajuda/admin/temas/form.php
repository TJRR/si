<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Tema — novo/editar',
    'resumo' => 'Dados de um Tema (grande área de desafio de uma trilha).',
    'operacoes' => [
        [
            'nome' => 'Nome / Descrição longa',
            'como' => 'A descrição aceita texto mais longo, exibido na home ao expandir o tema.',
        ],
        [
            'nome' => 'Ícone',
            'como' => 'Escolha entre os ícones pré-definidos da lista — não é upload de imagem.',
        ],
        [
            'nome' => 'Ordem',
            'como' => 'Também pode ser ajustada por arraste na lista, sem abrir esta tela.',
        ],
    ],
    'conceitos' => [],
];
