<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Biblioteca de Mídia',
    'resumo' => 'Repositório global de imagens, PDFs e vídeos, reaproveitável em qualquer edição — mesmo conceito de banco global do FAQ.',
    'operacoes' => [
        [
            'nome' => 'Filtrar por tipo',
            'como' => 'Reduz a lista para imagem, PDF ou vídeo.',
        ],
        [
            'nome' => 'Abrir em nova aba',
            'como' => 'Visualiza o arquivo original.',
        ],
        [
            'nome' => 'Remover',
            'icone' => 'remover',
            'como' => 'Apaga o item.',
            'observacao' => 'Bloqueado com erro se o item ainda estiver em uso em algum lugar do sistema.',
        ],
        [
            'nome' => '+ Nova mídia',
            'como' => 'Tipo, arquivo, alt obrigatório para imagem, título/descrição, e edição de origem opcional (marca de qual concurso o item veio, sem restringir o uso).',
        ],
    ],
    'conceitos' => ['banco_global_vs_edicao'],
];
