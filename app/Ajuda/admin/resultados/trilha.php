<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Resultado Final da Trilha',
    'resumo' => 'Ranking final da trilha (mesmo dado calculado na tela Apuração), com opção de destacar cada equipe vencedora com um resumo e imagem.',
    'operacoes' => [
        [
            'nome' => 'Publicar / Reabrir',
            'icone' => 'publicar',
            'como' => 'Mesmo padrão do Resultado da Etapa e da Apuração.',
        ],
        [
            'nome' => 'Editar resumo/imagem de destaque',
            'como' => 'Disponível por linha, só depois de publicado.',
        ],
    ],
    'conceitos' => ['publicar_trava'],
];
