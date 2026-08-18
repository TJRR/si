<?php

if (!defined('SI_BOOT')) {
    http_response_code(403);
    exit('Acesso negado');
}

return [
    'titulo' => 'Tema — Cabeçalho',
    'resumo' => 'Aparência do cabeçalho da home — cadastrar uma imagem de fundo muda o cabeçalho de "barra fina sólida" para "alto e transparente sobre imagem".',
    'operacoes' => [
        [
            'nome' => 'Logo padrão',
            'como' => 'Usada quando não há imagem de fundo (ou como logo escura sobre a imagem, se não houver logo clara).',
        ],
        [
            'nome' => 'Imagem de fundo',
            'como' => '1920×800, opcional.',
            'observacao' => 'Vários outros campos desta tela (posição, transição, opacidade, efeito de entrada, logo clara) só têm efeito visível se houver imagem de fundo cadastrada.',
        ],
        [
            'nome' => 'Posição (grade 3×3)',
            'como' => 'Ponto de enquadramento da imagem de fundo.',
        ],
        [
            'nome' => 'Efeito de transição na base',
            'como' => 'Onda ou Diagonal — a forma que separa o cabeçalho do restante da página.',
        ],
        [
            'nome' => 'Logo clara alternativa',
            'como' => 'Só é usada quando há imagem de fundo — sobre a foto, a logo padrão pode ficar difícil de ler.',
        ],
        [
            'nome' => 'Título/slogan',
            'como' => 'Editor rico.',
        ],
    ],
    'conceitos' => [],
];
