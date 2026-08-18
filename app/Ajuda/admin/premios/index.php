<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Premiação',
    'resumo' => 'Lista de prêmios exibidos na home — o concurso escolhe entre um modo "geral" (prêmios únicos, para todo o concurso) ou "por trilha" (um conjunto de prêmios por trilha).',
    'operacoes' => [
        [
            'nome' => 'Prêmio geral / Prêmio por trilha',
            'como' => 'Radio com auto-envio — ao trocar, a lista muda de imediato para mostrar o modo escolhido.',
            'observacao' => 'Trocar o modo não apaga os prêmios já cadastrados no outro modo, só muda o que fica visível na lista.',
        ],
        [
            'nome' => '+ Novo prêmio',
            'como' => 'Geral, ou dentro do cartão de uma trilha específica (conforme o modo ativo).',
        ],
        [
            'nome' => 'Editar / Remover',
            'icone' => 'editar',
            'como' => 'Remover também apaga a imagem/ícone do prêmio.',
        ],
        [
            'nome' => 'Reordenar',
            'como' => 'Ver conceito "Reordenar por arraste" abaixo.',
        ],
    ],
    'conceitos' => ['reordenar_arraste'],
];
